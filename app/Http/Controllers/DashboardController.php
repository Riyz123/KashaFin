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
        ]);
    }
}
