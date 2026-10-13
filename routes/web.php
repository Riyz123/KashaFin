<?php

use App\Http\Controllers\Admin\AiProviderController as AdminAiProviderController;
use App\Http\Controllers\Admin\AiPromptController as AdminAiPromptController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\FacultyController as AdminFacultyController;
use App\Http\Controllers\Admin\SupportRequestController as AdminSupportRequestController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ForcePasswordController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\GoalContributionController;
use App\Http\Controllers\GoalController;
use App\Http\Controllers\HelpController;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\IncomeController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectionController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SharedExpenseController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check() && auth()->user()->isAdmin()) {
        return redirect()->route('admin.dashboard');
    }

    if (auth()->check() && auth()->user()->isDean()) {
        return redirect()->route('admin.users.index');
    }

    return redirect()->route('dashboard');
});

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('cambiar-contrasena', [ForcePasswordController::class, 'edit'])->name('password.force-edit');
    Route::put('cambiar-contrasena', [ForcePasswordController::class, 'update'])->name('password.force-update');
});

Route::middleware(['auth', 'active', 'password-fresh', 'student-app'])->group(function () {
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

    Route::resource('prestamos', LoanController::class)
        ->parameters(['prestamos' => 'loan'])
        ->names('loans')
        ->except(['show']);
    Route::patch('prestamos/{loan}/pagado', [LoanController::class, 'markPaid'])->name('loans.mark-paid');
    Route::patch('prestamos/{loan}/aprobar', [LoanController::class, 'approve'])->name('loans.approve');
    Route::patch('prestamos/{loan}/rechazar', [LoanController::class, 'reject'])->name('loans.reject');
    Route::patch('prestamos/{loan}/confirmar-pago', [LoanController::class, 'confirmPayment'])->name('loans.confirm-payment');
    Route::patch('prestamos/{loan}/rechazar-pago', [LoanController::class, 'rejectPaymentConfirmation'])->name('loans.reject-payment');

    Route::get('gastos-compartidos', [SharedExpenseController::class, 'index'])->name('shared-expenses.index');
    Route::get('gastos-compartidos/nuevo', [SharedExpenseController::class, 'create'])->name('shared-expenses.create');
    Route::get('gastos-compartidos/buscar-usuarios', [SharedExpenseController::class, 'searchUsers'])->name('shared-expenses.search-users');
    Route::post('gastos-compartidos', [SharedExpenseController::class, 'store'])->name('shared-expenses.store');
    Route::delete('gastos-compartidos/{sharedExpense}', [SharedExpenseController::class, 'destroy'])->name('shared-expenses.destroy');
    Route::patch('gastos-compartidos-participantes/{participant}/aceptar', [SharedExpenseController::class, 'accept'])->name('shared-expenses.accept');
    Route::patch('gastos-compartidos-participantes/{participant}/rechazar', [SharedExpenseController::class, 'reject'])->name('shared-expenses.reject');
    Route::post('gastos-compartidos-participantes/{participant}/aportes', [SharedExpenseController::class, 'storeContribution'])->name('shared-expenses.contributions.store');

    Route::get('reportes', [ReportController::class, 'index'])->name('reports.index');
    Route::get('reportes/pdf', [ReportController::class, 'exportPdf'])->name('reports.pdf');
    Route::get('reportes/csv', [ReportController::class, 'exportCsv'])->name('reports.csv');

    Route::get('historial', [HistoryController::class, 'index'])->name('history.index');
    Route::get('historial/exportar', [HistoryController::class, 'exportCsv'])->name('history.export');
    Route::delete('historial', [HistoryController::class, 'clear'])->name('history.clear');
    Route::post('historial/{type}/{id}/duplicar', [HistoryController::class, 'duplicate'])
        ->whereIn('type', ['ingreso', 'gasto'])
        ->name('history.duplicate');

    Route::get('notificaciones', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('notificaciones/enviar', [NotificationController::class, 'sendNow'])->name('notifications.send');

    Route::get('ayuda', [HelpController::class, 'index'])->name('help.index');
    Route::post('ayuda', [HelpController::class, 'store'])->name('help.store');

    Route::get('configuracion', [SettingsController::class, 'edit'])->name('settings.edit');
    Route::patch('configuracion', [SettingsController::class, 'update'])->name('settings.update');
    Route::patch('configuracion/tema', [SettingsController::class, 'updateTheme'])->name('settings.theme');
    Route::patch('configuracion/onboarding', [SettingsController::class, 'completeOnboarding'])->name('settings.onboarding');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::post('asistente/mensaje', [ChatController::class, 'send'])->name('chat.send');
});

// Accesible por admin master Y por decanos (acotado a su propia facultad
// dentro de cada controller — ver User::canManage() y scopedStudents()).
Route::prefix('admin')->name('admin.')->middleware(['auth', 'active', 'password-fresh', 'staff'])->group(function () {
    Route::get('usuarios', [AdminUserController::class, 'index'])->name('users.index');
    Route::post('usuarios', [AdminUserController::class, 'store'])->name('users.store');
    Route::get('usuarios/exportar', [AdminUserController::class, 'export'])->name('users.export');
    Route::get('usuarios/importar', [AdminUserController::class, 'importForm'])->name('users.import-form');
    Route::post('usuarios/importar', [AdminUserController::class, 'import'])->name('users.import');
    Route::patch('usuarios/{user}/estado', [AdminUserController::class, 'toggleActive'])->name('users.toggle-active');
    Route::patch('usuarios/{user}/aprobar', [AdminUserController::class, 'approve'])->name('users.approve');
    Route::patch('usuarios/{user}/reenviar-contrasena', [AdminUserController::class, 'resendPassword'])->name('users.resend-password');
    Route::patch('usuarios/{user}/facultad', [AdminUserController::class, 'updateFaculty'])->name('users.update-faculty');
    Route::delete('usuarios/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');

    Route::get('soporte', [AdminSupportRequestController::class, 'index'])->name('support.index');
    Route::patch('soporte/{supportRequest}/atender', [AdminSupportRequestController::class, 'attend'])->name('support.attend');

    Route::get('categorias', [AdminCategoryController::class, 'index'])->name('categories.index');
    Route::post('categorias', [AdminCategoryController::class, 'store'])->name('categories.store');
    Route::patch('categorias/{category}', [AdminCategoryController::class, 'update'])->name('categories.update');
    Route::patch('categorias/{category}/estado', [AdminCategoryController::class, 'toggleActive'])->name('categories.toggle-active');
});

// Solo admin master: métricas globales, proveedores de IA, y gestión de
// facultades/decanos.
Route::prefix('admin')->name('admin.')->middleware(['auth', 'active', 'password-fresh', 'admin'])->group(function () {
    Route::get('dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

    Route::get('ia', [AdminAiProviderController::class, 'index'])->name('ai.index');
    Route::post('ia', [AdminAiProviderController::class, 'store'])->name('ai.store');
    Route::put('ia/{aiProvider}', [AdminAiProviderController::class, 'update'])->name('ai.update');
    Route::patch('ia/{aiProvider}/estado', [AdminAiProviderController::class, 'toggleActive'])->name('ai.toggle-active');
    Route::patch('ia/{aiProvider}/reactivar', [AdminAiProviderController::class, 'reactivate'])->name('ai.reactivate');
    Route::post('ia/{aiProvider}/probar', [AdminAiProviderController::class, 'testConnection'])->name('ai.test');
    Route::delete('ia/{aiProvider}', [AdminAiProviderController::class, 'destroy'])->name('ai.destroy');
    Route::put('ia-prompt', [AdminAiPromptController::class, 'update'])->name('ai.prompt.update');
    Route::delete('ia-prompt', [AdminAiPromptController::class, 'reset'])->name('ai.prompt.reset');

    Route::get('decanos', [AdminFacultyController::class, 'index'])->name('faculties.index');
    Route::post('decanos', [AdminFacultyController::class, 'store'])->name('faculties.store');
    Route::post('decanos/{faculty}/decano', [AdminFacultyController::class, 'storeDean'])->name('faculties.deans.store');
});

require __DIR__.'/auth.php';
