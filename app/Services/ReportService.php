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
}
