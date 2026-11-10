<?php

namespace App\Http\Controllers;

use App\Services\LiquidityAlertService;
use App\Services\LiquidityProjectionService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(
        Request $request,
        LiquidityProjectionService $projection,
        LiquidityAlertService $alerts
    ) {
        $user = $request->user();

        $alerts->checkAndNotify($user);

        $series = $projection->dailySeries($user, 15);
        $sevenDay = $series->take(7);
        $lowest = $projection->lowestPoint($sevenDay);

        $now = now();
        $incomeThisMonth = $user->incomes()
            ->whereYear('date', $now->year)
            ->whereMonth('date', $now->month)
            ->sum('amount');
        $expenseThisMonth = $user->expenses()
            ->whereYear('date', $now->year)
            ->whereMonth('date', $now->month)
            ->sum('amount');

        $goals = $user->savingsGoals()->where('status', 'active')->latest()->take(3)->get();

        return view('dashboard.index', [
            'currentBalance' => $projection->currentBalance($user),
            'incomeThisMonth' => (float) $incomeThisMonth,
            'expenseThisMonth' => (float) $expenseThisMonth,
            'series' => $series,
            'lowest' => $lowest,
            'isBelowThreshold' => $projection->isBelowThreshold($user),
            'goals' => $goals,
            'showOnboarding' => $user->settings->onboarding_completed_at === null,
        ]);
    }

    /**
     * "¿De dónde sale el saldo actual?" — the exact same query shape as
     * LiquidityProjectionService::currentBalance(), just broken into its
     * four terms with counts, so a student can check it against their own
     * numbers instead of trusting a single total.
     */
    public function balance(Request $request)
    {
        $user = $request->user();
        $today = now()->startOfDay();

        $startingBalance = (float) $user->settings->starting_balance;

        $incomes = $user->incomes()->whereDate('date', '<=', $today);
        $incomeTotal = (float) (clone $incomes)->sum('amount');
        $incomeCount = (clone $incomes)->count();

        $expenses = $user->expenses()->whereDate('date', '<=', $today);
        $expenseTotal = (float) (clone $expenses)->sum('amount');
        $expenseCount = (clone $expenses)->count();

        $loans = $user->loans()
            ->where('direction', 'recibido')
            ->whereNotIn('status', ['pendiente_confirmacion', 'rechazado'])
            ->whereDate('date', '<=', $today);
        $loanTotal = (float) (clone $loans)->sum('amount');
        $loanCount = (clone $loans)->count();

        $currentBalance = $startingBalance + $incomeTotal - $expenseTotal + $loanTotal;

        return view('dashboard.balance', [
            'startingBalance' => $startingBalance,
            'incomeTotal' => $incomeTotal,
            'incomeCount' => $incomeCount,
            'expenseTotal' => $expenseTotal,
            'expenseCount' => $expenseCount,
            'loanTotal' => $loanTotal,
            'loanCount' => $loanCount,
            'currentBalance' => $currentBalance,
            'asOf' => $today,
        ]);
    }
}
