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
</x-app-layout>
