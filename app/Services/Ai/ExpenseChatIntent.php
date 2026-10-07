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
        $expense = Expense::create([
            'user_id' => $user->id,
            'category_id' => $data['category_id'] ?? null,
            'amount' => $data['amount'],
            'date' => now()->toDateString(),
            'description' => 'Agregado por el asistente',
        ]);

        $state->clear();

        $categoryName = $expense->category?->name ?? 'sin categoría';

        return "✅ Gasto registrado: S/ ".number_format((float) $expense->amount, 2)." en {$categoryName}, con fecha de hoy.";
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
        $lower = Str::lower($message);

        foreach (Category::query()->forUser($user)->get() as $category) {
            if (str_contains($lower, Str::lower($category->name))) {
                return $category->id;
            }
        }

        return null;
    }

    private function mentionsNoCategory(string $message): bool
    {
        $lower = Str::lower($message);

        return str_contains($lower, 'ningun') || str_contains($lower, 'sin categor');
    }
}
