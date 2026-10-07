<?php

use App\Http\Controllers\Admin\AiProviderController as AdminAiProviderController;
use App\Http\Controllers\Admin\AiPromptController as AdminAiPromptController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\GoalContributionController;
use App\Http\Controllers\GoalController;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\IncomeController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectionController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check() && auth()->user()->isAdmin()) {
        return redirect()->route('admin.dashboard');
    }

    return redirect()->route('dashboard');
});

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('ingresos', IncomeController::class)
        ->parameters(['ingresos' => 'income'])
        ->names('incomes')
        ->except(['show']);

    Route::resource('gastos', ExpenseController::class)
        ->parameters(['gastos' => 'expense'])
        ->names('expenses')
        ->except(['show']);
    Route::post('gastos-categorias', [CategoryController::class, 'store'])->name('categories.store');
    Route::delete('gastos-categorias/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

    Route::resource('presupuesto', BudgetController::class)
        ->parameters(['presupuesto' => 'budget'])
        ->names('budgets')
        ->except(['show']);

    Route::get('proyecciones', [ProjectionController::class, 'index'])->name('projections.index');

    Route::resource('metas', GoalController::class)
        ->parameters(['metas' => 'goal'])
        ->names('goals')
        ->except(['show']);
    Route::get('metas-historial', [GoalController::class, 'history'])->name('goals.history');
    Route::post('metas/{goal}/aportes', [GoalContributionController::class, 'store'])->name('goals.contributions.store');

    Route::get('reportes', [ReportController::class, 'index'])->name('reports.index');
    Route::get('reportes/pdf', [ReportController::class, 'exportPdf'])->name('reports.pdf');
    Route::get('reportes/csv', [ReportController::class, 'exportCsv'])->name('reports.csv');

    Route::get('historial', [HistoryController::class, 'index'])->name('history.index');
    Route::post('historial/{type}/{id}/duplicar', [HistoryController::class, 'duplicate'])
        ->whereIn('type', ['ingreso', 'gasto'])
        ->name('history.duplicate');

    Route::get('notificaciones', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('notificaciones/enviar', [NotificationController::class, 'sendNow'])->name('notifications.send');

    Route::get('configuracion', [SettingsController::class, 'edit'])->name('settings.edit');
    Route::patch('configuracion', [SettingsController::class, 'update'])->name('settings.update');
    Route::patch('configuracion/tema', [SettingsController::class, 'updateTheme'])->name('settings.theme');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::post('asistente/mensaje', [ChatController::class, 'send'])->name('chat.send');
});

Route::prefix('admin')->name('admin.')->middleware(['auth', 'active', 'admin'])->group(function () {
    Route::get('dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

    Route::get('usuarios', [AdminUserController::class, 'index'])->name('users.index');
    Route::patch('usuarios/{user}/estado', [AdminUserController::class, 'toggleActive'])->name('users.toggle-active');
    Route::delete('usuarios/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');

    Route::get('categorias', [AdminCategoryController::class, 'index'])->name('categories.index');
    Route::post('categorias', [AdminCategoryController::class, 'store'])->name('categories.store');
    Route::patch('categorias/{category}', [AdminCategoryController::class, 'update'])->name('categories.update');
    Route::patch('categorias/{category}/estado', [AdminCategoryController::class, 'toggleActive'])->name('categories.toggle-active');

    Route::get('ia', [AdminAiProviderController::class, 'index'])->name('ai.index');
    Route::post('ia', [AdminAiProviderController::class, 'store'])->name('ai.store');
    Route::put('ia/{aiProvider}', [AdminAiProviderController::class, 'update'])->name('ai.update');
    Route::patch('ia/{aiProvider}/estado', [AdminAiProviderController::class, 'toggleActive'])->name('ai.toggle-active');
    Route::delete('ia/{aiProvider}', [AdminAiProviderController::class, 'destroy'])->name('ai.destroy');
    Route::put('ia-prompt', [AdminAiPromptController::class, 'update'])->name('ai.prompt.update');
    Route::delete('ia-prompt', [AdminAiPromptController::class, 'reset'])->name('ai.prompt.reset');
});

require __DIR__.'/auth.php';
