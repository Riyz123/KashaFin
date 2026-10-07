<?php

namespace App\Services\Ai;

use App\Models\Budget;
use App\Models\Category;
use App\Models\ChatState;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Deterministic slot-filling for "set a budget" via chat/voice, mirroring
 * ExpenseChatIntent: category and amount are parsed with string/regex
 * matching, never left to the AI to decide.
 */
class BudgetChatIntent
{
    private const TRIGGERS = [
        'agrega un presupuesto', 'agregar un presupuesto', 'agregar presupuesto',
        'crea un presupuesto', 'crear un presupuesto', 'crear presupuesto',
        'establece un presupuesto', 'define un presupuesto', 'definir presupuesto',
        'nuevo presupuesto', 'pon un presupuesto', 'poner un presupuesto',
        'quiero crear un presupuesto', 'quiero agregar un presupuesto',
    ];

    public function handle(User $user, string $message): ?string
    {
        $state = ChatState::firstOrCreate(['user_id' => $user->id]);

        if ($state->pending_intent === 'create_budget') {
            return $this->continueBudget($user, $state, $message);
        }

        if ($this->looksLikeTrigger($message)) {
            $categories = Category::query()->forUser($user)->orderBy('name')->pluck('name')->implode(', ');
            $state->update(['pending_intent' => 'create_budget', 'pending_data' => []]);

            return "¿Para qué categoría? ({$categories})";
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

    private function continueBudget(User $user, ChatState $state, string $message): string
    {
        $data = $state->pending_data ?? [];

        if (! isset($data['category_id'])) {
            $categoryId = $this->extractCategory($user, $message);

            if ($categoryId === null) {
                $categories = Category::query()->forUser($user)->orderBy('name')->pluck('name')->implode(', ');

                return "No reconocí esa categoría. ¿Cuál de estas? ({$categories})";
            }

            $data['category_id'] = $categoryId;
            $state->update(['pending_data' => $data]);

            $categoryName = Category::find($categoryId)?->name;

            return "Perfecto, presupuesto para {$categoryName}. ¿Cuánto quieres presupuestar este mes?";
        }

        $amount = $this->extractAmount($message);

        if ($amount === null) {
            return 'No logré entender el monto. ¿Cuánto quieres presupuestar? Por ejemplo: "200".';
        }

        $data['amount'] = $amount;
        $state->update(['pending_data' => $data]);

        return $this->createBudget($user, $state, $data);
    }

    private function createBudget(User $user, ChatState $state, array $data): string
    {
        $reply = $this->persistBudget($user, $data['category_id'], $data['amount']);
        $state->clear();

        return $reply;
    }

    /**
     * Called by ChatService when the AI decides to invoke the "add_budget"
     * tool. The amount is still validated here, never trusted blindly.
     */
    public function createFromToolCall(User $user, array $arguments): string
    {
        $amount = $this->coerceAmount($arguments['amount'] ?? null);

        if ($amount === null) {
            return 'Necesito un monto válido (mayor a 0) para el presupuesto.';
        }

        // The tool's "category" parameter is constrained to an enum of this
        // student's real category names, so an exact match is expected.
        $categoryId = Category::query()->forUser($user)
            ->where('name', $arguments['category'] ?? null)
            ->value('id');

        if ($categoryId === null) {
            $categories = Category::query()->forUser($user)->orderBy('name')->pluck('name')->implode(', ');

            return "No reconocí esa categoría. ¿Cuál de estas? ({$categories})";
        }

        return $this->persistBudget($user, $categoryId, $amount);
    }

    private function persistBudget(User $user, int $categoryId, float $amount): string
    {
        $periodMonth = Carbon::now()->startOfMonth();

        $budget = Budget::query()
            ->where('user_id', $user->id)
            ->where('category_id', $categoryId)
            ->whereDate('period_month', $periodMonth->toDateString())
            ->first();

        if ($budget) {
            $budget->update(['amount' => $amount]);
        } else {
            $budget = Budget::create([
                'user_id' => $user->id,
                'category_id' => $categoryId,
                'period_month' => $periodMonth,
                'amount' => $amount,
            ]);
        }

        $categoryName = $budget->category->name;

        return "✅ Presupuesto guardado: S/ ".number_format($amount, 2)." para {$categoryName} este mes.";
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
}
