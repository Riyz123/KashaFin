<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Budget;
use App\Models\Category;
use App\Models\Expense;
use App\Models\Income;
use App\Models\SavingsGoal;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        return view('admin.dashboard', [
            'totalUsers' => User::where('role', 'estudiante')->count(),
            'activeUsers' => User::where('role', 'estudiante')->where('is_active', true)->count(),
            'inactiveUsers' => User::where('role', 'estudiante')->where('is_active', false)->count(),
            'totalIncomes' => Income::count(),
            'totalExpenses' => Expense::count(),
            'totalBudgets' => Budget::count(),
            'totalGoals' => SavingsGoal::count(),
            'totalCategories' => Category::count(),
            'recentUsers' => User::where('role', 'estudiante')->latest()->take(5)->get(),
        ]);
    }
}
