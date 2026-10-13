<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ReportService
{
    public function expensesByCategory(User $user, Carbon $from, Carbon $to): Collection
    {
        return $user->expenses()
            ->with('category')
            ->between($from, $to)
            ->get()
            ->groupBy(fn ($expense) => $expense->category?->name ?? 'Sin categoría')
            ->map(fn ($expenses, $name) => [
                'category' => $name,
                'total' => round((float) $expenses->sum('amount'), 2),
            ])
            ->values()
            ->sortByDesc('total')
            ->values();
    }

    public function incomeVsExpense(User $user, Carbon $from, Carbon $to): array
    {
        $income = (float) $user->incomes()->between($from, $to)->sum('amount');
        $expense = (float) $user->expenses()->between($from, $to)->sum('amount');

        return [
            'income' => round($income, 2),
            'expense' => round($expense, 2),
            'balance' => round($income - $expense, 2),
        ];
    }

    /**
     * RF44: current period vs. the immediately preceding period of the same
     * length (e.g. this month vs. last month, this week vs. last week).
     */
    public function periodComparison(User $user, Carbon $from, Carbon $to): array
    {
        $days = $from->startOfDay()->diffInDays($to->copy()->startOfDay()) + 1;
        $previousTo = $from->copy()->subDay()->endOfDay();
        $previousFrom = $previousTo->copy()->subDays($days - 1)->startOfDay();

        $current = $this->incomeVsExpense($user, $from, $to);
        $previous = $this->incomeVsExpense($user, $previousFrom, $previousTo);

        return [
            'previous_from' => $previousFrom,
            'previous_to' => $previousTo,
            'current' => $current,
            'previous' => $previous,
            'income_change_percent' => $this->percentChange($previous['income'], $current['income']),
            'expense_change_percent' => $this->percentChange($previous['expense'], $current['expense']),
        ];
    }

    /**
     * Null means "not comparable" (previous period was zero and current
     * isn't) — showing a fabricated "+infinity%" would be misleading.
     */
    private function percentChange(float $previous, float $current): ?float
    {
        if ($previous === 0.0) {
            return $current === 0.0 ? 0.0 : null;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }
}
