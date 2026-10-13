<x-app-layout title="Proyecciones">
    <x-page-header title="Proyecciones de liquidez" subtitle="Visualiza cómo se verá tu situación financiera en el futuro." />

    <div class="mb-6 flex gap-2">
        <a href="{{ route('projections.index', ['horizon' => 7]) }}" class="rounded-full px-4 py-1.5 text-sm font-medium {{ $horizon === 7 ? 'bg-brand-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 border border-gray-200 dark:border-gray-700' }}">7 días</a>
        <a href="{{ route('projections.index', ['horizon' => 15]) }}" class="rounded-full px-4 py-1.5 text-sm font-medium {{ $horizon === 15 ? 'bg-brand-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 border border-gray-200 dark:border-gray-700' }}">15 días</a>
    </div>

    <x-card class="mb-6">
        <div class="h-72">
            <canvas id="liquidityChart"></canvas>
        </div>
        <script type="application/json" id="liquidity-series">{!! \Illuminate\Support\Js::encode($series) !!}</script>
    </x-card>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-stat-card label="Saldo actual" :value="'S/ ' . number_format($currentBalance, 2)" />
        <x-stat-card label="Punto más bajo proyectado" :value="'S/ ' . number_format($lowest['balance'], 2)" tone="negative">
            {{ \Illuminate\Support\Carbon::parse($lowest['date'])->format('d/m/Y') }}
        </x-stat-card>
        <x-stat-card label="Horizonte" :value="$horizon . ' días'" />
    </div>

    <x-card class="mt-6">
        <h3 class="mb-2 text-sm font-semibold text-gray-700 dark:text-gray-200">¿Cómo se calculó esto?</h3>
        <p class="mb-3 text-xs text-gray-500 dark:text-gray-400">
            Los ingresos variables y gastos se promedian por día usando tus últimos
            {{ $breakdown['window_days'] }} día(s) de historial
            @if ($breakdown['window_days'] < 28)
                (todavía menos de 28 porque tu cuenta es reciente, para no diluir el promedio con días sin datos)
            @endif
            . Los ingresos fijos se proyectan en su fecha exacta, no en este promedio.
        </p>
        <div class="grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
            <div>
                <p class="text-gray-500 dark:text-gray-400">Ingreso variable promedio/día</p>
                <p class="font-semibold text-brand-600">S/ {{ number_format($breakdown['variable_daily_rate'], 2) }}</p>
            </div>
            <div>
                <p class="text-gray-500 dark:text-gray-400">Gasto promedio/día</p>
                <p class="font-semibold text-red-600">S/ {{ number_format($breakdown['expense_daily_rate'], 2) }}</p>
            </div>
        </div>
        @if ($breakdown['upcoming_fixed']->isNotEmpty())
            <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">Próximos ingresos fijos dentro de este horizonte:</p>
            <ul class="mt-1 space-y-0.5 text-xs text-gray-600 dark:text-gray-300">
                @foreach ($breakdown['upcoming_fixed'] as $date => $amount)
                    <li>{{ \Illuminate\Support\Carbon::parse($date)->format('d/m/Y') }}: S/ {{ number_format($amount, 2) }}</li>
                @endforeach
            </ul>
        @endif
    </x-card>
</x-app-layout>
