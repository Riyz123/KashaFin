<x-app-layout title="Inicio">
    <div class="mb-6">
        <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">¡Hola, {{ auth()->user()->name }}!</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400">Tu disciplina hoy, construye tu mañana.</p>
    </div>

    @if ($isBelowThreshold)
        <x-alert-banner tone="danger" class="mb-6">
            Tu proyección de liquidez a 7 días está por debajo de tu umbral configurado. Revisa tus
            <a href="{{ route('projections.index') }}" class="font-semibold underline">proyecciones</a>
            para más detalle.
        </x-alert-banner>
    @endif

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4 mb-6">
        <x-stat-card label="Saldo actual" :value="'S/ ' . number_format($currentBalance, 2)" icon="wallet" />
        <x-stat-card label="Ingresos del mes" :value="'S/ ' . number_format($incomeThisMonth, 2)" tone="positive" icon="income" />
        <x-stat-card label="Gastos del mes" :value="'S/ ' . number_format($expenseThisMonth, 2)" tone="negative" icon="expense" />
        <x-stat-card label="Liquidez disponible (7 días)" :value="'S/ ' . number_format($lowest['balance'], 2)" icon="chart" />
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <x-card class="xl:col-span-2">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="font-semibold text-gray-800 dark:text-gray-100">Proyección de liquidez (15 días)</h3>
                <a href="{{ route('projections.index') }}" class="text-sm text-brand-600 hover:underline">Ver detalle</a>
            </div>
            <div class="h-64">
                <canvas id="liquidityChart"></canvas>
            </div>
            <script type="application/json" id="liquidity-series">{!! \Illuminate\Support\Js::encode($series) !!}</script>
        </x-card>

        <div class="space-y-6">
            <x-card>
                <h3 class="mb-3 font-semibold text-gray-800 dark:text-gray-100">Mis metas</h3>
                @forelse ($goals as $goal)
                    <div class="mb-4 last:mb-0">
                        <div class="flex items-center justify-between text-sm mb-1">
                            <span class="font-medium text-gray-700 dark:text-gray-200">{{ $goal->name }}</span>
                            <span class="text-gray-500 dark:text-gray-400">{{ $goal->progress_percent }}%</span>
                        </div>
                        <x-progress-bar :percent="$goal->progress_percent" />
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            S/ {{ number_format($goal->current_amount, 2) }} / S/ {{ number_format($goal->target_amount, 2) }}
                        </p>
                    </div>
                @empty
                    <x-empty-state message="Aún no tienes metas activas." />
                @endforelse
                <a href="{{ route('goals.index') }}" class="mt-2 inline-block text-sm text-brand-600 hover:underline">Ver todas las metas</a>
            </x-card>

            <x-card class="bg-brand-50 dark:bg-brand-900/20 border-brand-100 dark:border-brand-800">
                <h3 class="mb-1 font-semibold text-brand-800 dark:text-brand-200">Consejo de hoy</h3>
                <p class="text-sm text-brand-700 dark:text-brand-300">Un pequeño ahorro hoy puede ser una gran oportunidad mañana.</p>
            </x-card>
        </div>
    </div>
</x-app-layout>
