<?php

namespace App\Services\Ai;

use App\Models\ChatState;
use App\Models\Income;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Deterministic slot-filling for "add an income" via chat/voice, mirroring
 * ExpenseChatIntent/BudgetChatIntent: amount, type and frequency are parsed
 * with fuzzy word matching, never left to the AI to decide.
 */
class IncomeChatIntent
{
    // Deliberately doesn't include generic verbs like "quiero" — those show
    // up in unrelated questions too, and pairing them with just the noun
    // "ingreso" would misfire too easily.
    private const ACTION_WORDS = [
        'agrega', 'agregar', 'registra', 'registrar', 'anota', 'anotar',
        'pon', 'poner', 'nuevo', 'nueva',
    ];

    private const NOUN = 'ingreso';

    private const FREQUENCIES = ['semanal', 'quincenal', 'mensual'];

    public function handle(User $user, string $message): ?string
    {
        $state = ChatState::firstOrCreate(['user_id' => $user->id]);

        if ($state->pending_intent === 'create_income') {
            return $this->continueIncome($user, $state, $message);
        }

        if ($this->looksLikeTrigger($message)) {
            $state->update(['pending_intent' => 'create_income', 'pending_data' => []]);

            return '¿De cuánto fue el ingreso?';
        }

        return null;
    }

    private function looksLikeTrigger(string $message): bool
    {
        return FuzzyMatch::hasWord($message, [self::NOUN])
            && FuzzyMatch::hasWord($message, self::ACTION_WORDS);
    }

    private function continueIncome(User $user, ChatState $state, string $message): string
    {
        $data = $state->pending_data ?? [];

        if (! isset($data['amount'])) {
            $amount = $this->extractAmount($message);

            if ($amount === null) {
                return 'No logré entender el monto. ¿Cuánto fue el ingreso? Por ejemplo: "150".';
            }

            $data['amount'] = $amount;

            $type = $this->extractType($message);

            if ($type !== null) {
                return $this->advanceAfterType($user, $state, $data, $type, $message);
            }

            $state->update(['pending_data' => $data]);

            return "Anotado: S/ {$amount}. ¿Es un ingreso fijo o variable?";
        }

        if (! isset($data['type'])) {
            $type = $this->extractType($message);

            if ($type === null) {
                return '¿Es un ingreso fijo o variable?';
            }

            return $this->advanceAfterType($user, $state, $data, $type, $message);
        }

        // Only reachable for "fijo" incomes still waiting on a frequency.
        $frequency = $this->extractFrequency($message);

        if ($frequency === null) {
            return 'No reconocí esa frecuencia. ¿Semanal, quincenal o mensual?';
        }

        $data['frequency'] = $frequency;
        $state->update(['pending_data' => $data]);

        return $this->createIncome($user, $state, $data);
    }

    private function advanceAfterType(User $user, ChatState $state, array $data, string $type, string $message): string
    {
        $data['type'] = $type;

        if ($type === 'variable') {
            $state->update(['pending_data' => $data]);

            return $this->createIncome($user, $state, $data);
        }

        $frequency = $this->extractFrequency($message);

        if ($frequency !== null) {
            $data['frequency'] = $frequency;
            $state->update(['pending_data' => $data]);

            return $this->createIncome($user, $state, $data);
        }

        $state->update(['pending_data' => $data]);

        return '¿Con qué frecuencia? (semanal, quincenal o mensual)';
    }

    private function createIncome(User $user, ChatState $state, array $data): string
    {
        $reply = $this->persistIncome($user, $data['amount'], $data['type'], $data['frequency'] ?? null, null);
        $state->clear();

        return $reply;
    }

    /**
     * Called by ChatService when the AI decides to invoke the "add_income"
     * tool. Every value is re-validated here — never trusted blindly.
     */
    public function createFromToolCall(User $user, array $arguments): string
    {
        $amount = $this->coerceAmount($arguments['amount'] ?? null);

        if ($amount === null) {
            return 'Necesito un monto válido (mayor a 0) para registrar el ingreso.';
        }

        $type = $arguments['type'] ?? null;

        if (! in_array($type, ['fijo', 'variable'], true)) {
            return '¿Es un ingreso fijo o variable?';
        }

        $frequency = $arguments['frequency'] ?? null;

        if ($type === 'fijo' && ! in_array($frequency, self::FREQUENCIES, true)) {
            return '¿Con qué frecuencia? (semanal, quincenal o mensual)';
        }

        $description = is_string($arguments['description'] ?? null) ? trim($arguments['description']) : null;

        return $this->persistIncome($user, $amount, $type, $type === 'fijo' ? $frequency : null, $description ?: null);
    }

    private function persistIncome(User $user, float $amount, string $type, ?string $frequency, ?string $description): string
    {
        $date = Carbon::now();
        $nextOccurrence = null;

        if ($type === 'fijo') {
            $template = new Income(['frequency' => $frequency]);
            $nextOccurrence = $template->nextOccurrenceAfter($date)->toDateString();
        }

        $income = Income::create([
            'user_id' => $user->id,
            'amount' => $amount,
            'date' => $date->toDateString(),
            'description' => $description ?: 'Agregado por el asistente',
            'type' => $type,
            'frequency' => $type === 'fijo' ? $frequency : null,
            'next_occurrence_date' => $nextOccurrence,
        ]);

        $typeLabel = $type === 'fijo' ? "fijo ({$frequency})" : 'variable';

        return "✅ Ingreso registrado: S/ ".number_format((float) $income->amount, 2)." ({$typeLabel}), con fecha de hoy.";
    }

    private function coerceAmount(mixed $value): ?float
    {
        if (! is_numeric($value)) {
            return null;
        }

        $amount = (float) $value;

        return $amount > 0 ? $amount : null;
    }

    private function extractAmount(string $message): ?float
    {
        if (preg_match('/(\d+(?:[.,]\d{1,2})?)/', $message, $matches)) {
            return (float) str_replace(',', '.', $matches[1]);
        }

        return null;
    }

    private function extractType(string $message): ?string
    {
        if (FuzzyMatch::hasWord($message, ['variable'])) {
            return 'variable';
        }

        if (FuzzyMatch::hasWord($message, ['fijo', 'fija'])) {
            return 'fijo';
        }

        return null;
    }

    private function extractFrequency(string $message): ?string
    {
        foreach (self::FREQUENCIES as $frequency) {
            if (FuzzyMatch::hasWord($message, [$frequency], 2)) {
                return $frequency;
            }
        }

        return null;
    }
}
