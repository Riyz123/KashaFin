<?php

namespace App\Services\Ai;

use App\Models\Category;
use App\Models\ChatState;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Deterministic slot-filling for "add an expense" via chat/voice. Amount and
 * category are parsed with regex/string matching — never handed to the AI —
 * so a money-creating action can never be hallucinated.
 */
class ExpenseChatIntent
{
    private const TRIGGERS = [
        'agrega un gasto', 'agregar un gasto', 'agregar gasto',
        'registra un gasto', 'registrar un gasto', 'registrar gasto',
        'anota un gasto', 'anotar un gasto', 'anotar gasto',
        'nuevo gasto', 'quiero agregar un gasto', 'quiero registrar un gasto',
    ];

    /**
     * Returns the reply text if this message was handled as part of the
     * expense-creation flow, or null so the caller falls through to the AI.
     */
    public function handle(User $user, string $message): ?string
    {
        $state = ChatState::firstOrCreate(['user_id' => $user->id]);

        if ($state->pending_intent === 'create_expense') {
            return $this->continueExpense($user, $state, $message);
        }

        if ($this->looksLikeTrigger($message)) {
            $state->update(['pending_intent' => 'create_expense', 'pending_data' => []]);

            return '¿De cuánto fue el gasto?';
        }

        return null;
    }

    private function looksLikeTrigger(string $message): bool
    {
        $message = Str::lower($message);

        foreach (self::TRIGGERS as $trigger) {
            if (str_contains($message, $trigger)) {
                return true;
            }
        }

        return false;
    }

    private function continueExpense(User $user, ChatState $state, string $message): string
    {
        $data = $state->pending_data ?? [];

        if (! isset($data['amount'])) {
            $amount = $this->extractAmount($message);

            if ($amount === null) {
                return 'No logré entender el monto. ¿Cuánto fue el gasto? Por ejemplo: "25.50".';
            }

            $data['amount'] = $amount;

            $categoryId = $this->extractCategory($user, $message);

            if ($categoryId !== null || $this->mentionsNoCategory($message)) {
                $data['category_id'] = $categoryId;
                $state->update(['pending_data' => $data]);

                return $this->createExpense($user, $state, $data);
            }

            $state->update(['pending_data' => $data]);

            $categories = Category::query()->forUser($user)->orderBy('name')->pluck('name')->implode(', ');

            return "Anotado: S/ {$amount}. ¿En qué categoría? ({$categories}, o di \"ninguna\")";
        }

        $data['category_id'] = $this->extractCategory($user, $message);
        $state->update(['pending_data' => $data]);

        return $this->createExpense($user, $state, $data);
    }

    private function createExpense(User $user, ChatState $state, array $data): string
    {
        $reply = $this->persistExpense($user, $data['amount'], $data['category_id'] ?? null, null);
        $state->clear();

        return $reply;
    }

    /**
     * Called by ChatService when the AI decides to invoke the "add_expense"
     * tool. The amount is still validated here — the tool schema asking the
     * model for a number is not a guarantee, so we never trust it blindly.
     */
    public function createFromToolCall(User $user, array $arguments): string
    {
        $amount = $this->coerceAmount($arguments['amount'] ?? null);

        if ($amount === null) {
            return 'Necesito un monto válido (mayor a 0) para registrar el gasto.';
        }

        // The tool's "category" parameter is constrained to an enum of this
        // student's real category names, so an exact match is expected —
        // no fuzzy/substring matching needed here (unlike the regex path).
        $categoryId = null;

        if (! empty($arguments['category'])) {
            $categoryId = Category::query()->forUser($user)
                ->where('name', $arguments['category'])
                ->value('id');
        }

        $description = is_string($arguments['description'] ?? null) ? trim($arguments['description']) : null;

        return $this->persistExpense($user, $amount, $categoryId, $description ?: null);
    }

    private function persistExpense(User $user, float $amount, ?int $categoryId, ?string $description): string
    {
        $expense = Expense::create([
            'user_id' => $user->id,
            'category_id' => $categoryId,
            'amount' => $amount,
            'date' => now()->toDateString(),
            'description' => $description ?: 'Agregado por el asistente',
        ]);

        $categoryName = $expense->category?->name ?? 'sin categoría';

        return "✅ Gasto registrado: S/ ".number_format($amount, 2)." en {$categoryName}, con fecha de hoy.";
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

    private function extractCategory(User $user, string $message): ?int
    {
        // Normalize accents too: voice-to-text and casual typing often drop
        // them ("alimentacion" should still match "Alimentación").
        $normalized = Str::lower(Str::ascii($message));

        foreach (Category::query()->forUser($user)->get() as $category) {
            if (str_contains($normalized, Str::lower(Str::ascii($category->name)))) {
                return $category->id;
            }
        }

        return null;
    }

    private function mentionsNoCategory(string $message): bool
    {
        $normalized = Str::lower(Str::ascii($message));

        return str_contains($normalized, 'ningun') || str_contains($normalized, 'sin categor');
    }
}
