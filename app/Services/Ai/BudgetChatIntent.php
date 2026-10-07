<?php

namespace App\Services\Ai;

use App\Models\Budget;
use App\Models\Category;
use App\Models\ChatState;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Deterministic slot-filling for "set a budget" via chat/voice, mirroring
 * ExpenseChatIntent: category and amount are parsed with fuzzy word
 * matching, never left to the AI to decide.
 */
class BudgetChatIntent
{
    // Deliberately doesn't include generic verbs like "quiero" — those show
    // up in unrelated questions too, and pairing them with just the noun
    // "presupuesto" would misfire too easily.
    private const ACTION_WORDS = [
        'agrega', 'agregar', 'registra', 'registrar', 'crea', 'crear',
        'establece', 'define', 'definir', 'pon', 'poner', 'nuevo', 'nueva',
    ];

    private const NOUN = 'presupuesto';

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
        return FuzzyMatch::hasWord($message, [self::NOUN])
            && FuzzyMatch::hasWord($message, self::ACTION_WORDS);
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
        $categories = Category::query()->forUser($user)->orderBy('name')->get();
        $bestName = FuzzyMatch::bestMatch($message, $categories->pluck('name')->all());

        return $bestName ? $categories->firstWhere('name', $bestName)?->id : null;
    }
}
