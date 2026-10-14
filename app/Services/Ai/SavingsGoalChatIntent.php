<?php

namespace App\Services\Ai;

use App\Models\ChatState;
use App\Models\SavingsGoal;
use App\Models\User;

/**
 * Deterministic slot-filling for "add a savings goal" via chat/voice,
 * mirroring BudgetChatIntent: name is free text (no fuzzy matching needed,
 * any name is valid), amount is parsed the same way as the other intents.
 * target_date is deliberately not asked here — parsing a date out of free
 * text reliably is a job for the AI tool-call path, not a regex; a goal
 * created by chat can always get a deadline added later from the form.
 */
class SavingsGoalChatIntent
{
    // Deliberately doesn't include generic verbs like "quiero" — those show
    // up in unrelated questions too, and pairing them with just the noun
    // "meta" would misfire too easily.
    private const ACTION_WORDS = [
        'agrega', 'agregar', 'registra', 'registrar', 'crea', 'crear',
        'pon', 'poner', 'nueva', 'nuevo',
    ];

    private const NOUN = 'meta';

    public function handle(User $user, string $message): ?string
    {
        $state = ChatState::firstOrCreate(['user_id' => $user->id]);

        if ($state->pending_intent === 'create_goal') {
            return $this->continueGoal($user, $state, $message);
        }

        if ($this->looksLikeTrigger($message)) {
            $state->update(['pending_intent' => 'create_goal', 'pending_data' => []]);

            return '¿Cómo quieres llamar a la meta?';
        }

        return null;
    }

    private function looksLikeTrigger(string $message): bool
    {
        return FuzzyMatch::hasWord($message, [self::NOUN])
            && FuzzyMatch::hasWord($message, self::ACTION_WORDS);
    }

    private function continueGoal(User $user, ChatState $state, string $message): string
    {
        $data = $state->pending_data ?? [];

        if (! isset($data['name'])) {
            $name = trim($message);

            if ($name === '') {
                return '¿Cómo quieres llamar a la meta?';
            }

            $data['name'] = $name;
            $state->update(['pending_data' => $data]);

            return "Meta: \"{$name}\". ¿Cuánto quieres ahorrar en total?";
        }

        $amount = $this->extractAmount($message);

        if ($amount === null) {
            return 'No logré entender el monto. ¿Cuánto quieres ahorrar en total? Por ejemplo: "500".';
        }

        $data['amount'] = $amount;
        $state->update(['pending_data' => $data]);

        return $this->createGoal($user, $state, $data);
    }

    private function createGoal(User $user, ChatState $state, array $data): string
    {
        $reply = $this->persistGoal($user, $data['name'], $data['amount'], null);
        $state->clear();

        return $reply;
    }

    /**
     * Called by ChatService when the AI decides to invoke the "add_goal"
     * tool. Every value is re-validated here — never trusted blindly.
     */
    public function createFromToolCall(User $user, array $arguments): string
    {
        $name = is_string($arguments['name'] ?? null) ? trim($arguments['name']) : '';

        if ($name === '') {
            return '¿Cómo quieres llamar a la meta?';
        }

        $amount = $this->coerceAmount($arguments['target_amount'] ?? null);

        if ($amount === null) {
            return 'Necesito un monto válido (mayor a 0) para la meta.';
        }

        $targetDate = $this->coerceDate($arguments['target_date'] ?? null);

        return $this->persistGoal($user, $name, $amount, $targetDate);
    }

    private function persistGoal(User $user, string $name, float $amount, ?string $targetDate): string
    {
        $goal = SavingsGoal::create([
            'user_id' => $user->id,
            'name' => $name,
            'target_amount' => $amount,
            'target_date' => $targetDate,
            'current_amount' => 0,
            'status' => 'active',
        ]);

        $dateLabel = $goal->target_date ? ', con fecha límite '.$goal->target_date->format('d/m/Y') : '';

        return "✅ Meta creada: \"{$goal->name}\" por S/ ".number_format($amount, 2)."{$dateLabel}.";
    }

    private function coerceAmount(mixed $value): ?float
    {
        if (! is_numeric($value)) {
            return null;
        }

        $amount = (float) $value;

        return $amount > 0 ? $amount : null;
    }

    /**
     * The tool schema asks the AI for an ISO date (YYYY-MM-DD) — anything
     * else, or a date that isn't actually in the future, is dropped rather
     * than rejecting the whole goal over an optional field.
     */
    private function coerceDate(mixed $value): ?string
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        try {
            $date = \Illuminate\Support\Carbon::parse($value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }

        return $date->isFuture() ? $date->toDateString() : null;
    }

    private function extractAmount(string $message): ?float
    {
        if (preg_match('/(\d+(?:[.,]\d{1,2})?)/', $message, $matches)) {
            return (float) str_replace(',', '.', $matches[1]);
        }

        return null;
    }
}
