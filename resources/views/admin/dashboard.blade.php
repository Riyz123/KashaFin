<x-admin-layout title="Panel">
    <x-page-header title="Panel de administración" subtitle="Métricas agregadas y anónimas del sistema — sin acceso al detalle financiero de ningún usuario." />

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4 mb-6">
        <x-stat-card label="Estudiantes registrados" :value="$totalUsers" icon="user" />
        <x-stat-card label="Cuentas activas" :value="$activeUsers" tone="positive" icon="user" />
        <x-stat-card label="Cuentas desactivadas" :value="$inactiveUsers" tone="negative" icon="user" />
        <x-stat-card label="Categorías globales" :value="$totalCategories" icon="wallet" />
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4 mb-6">
        <x-stat-card label="Ingresos registrados" :value="$totalIncomes" icon="income" />
        <x-stat-card label="Gastos registrados" :value="$totalExpenses" icon="expense" />
        <x-stat-card label="Presupuestos activos" :value="$totalBudgets" icon="chart" />
        <x-stat-card label="Metas de ahorro" :value="$totalGoals" icon="flag" />
    </div>

    <x-card>
        <div class="mb-4 flex items-center justify-between">
            <h3 class="font-semibold text-gray-800 dark:text-gray-100">Últimos estudiantes registrados</h3>
            <a href="{{ route('admin.users.index') }}" class="text-sm text-brand-600 hover:underline">Ver todos</a>
        </div>
        @if ($recentUsers->isEmpty())
            <x-empty-state message="Todavía no hay estudiantes registrados." />
        @else
            <div class="divide-y divide-gray-100 dark:divide-gray-700">
                @foreach ($recentUsers as $user)
                    <div class="flex items-center justify-between py-2 text-sm">
                        <div>
                            <p class="font-medium text-gray-800 dark:text-gray-100">{{ $user->name }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $user->email }}</p>
                        </div>
                        <span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $user->is_active ? 'bg-brand-100 text-brand-800 dark:bg-brand-900/40 dark:text-brand-200' : 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300' }}">
                            {{ $user->is_active ? 'Activa' : 'Desactivada' }}
                        </span>
                    </div>
                @endforeach
            </div>
        @endif
    </x-card>
</x-admin-layout>
