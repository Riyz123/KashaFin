<x-app-layout title="Saldo actual">
    <x-page-header title="¿De dónde sale tu saldo actual?" subtitle="La fórmula exacta y cada número que la compone, a la fecha de hoy." />

    <x-card class="mb-6">
        <h3 class="mb-3 text-sm font-semibold text-gray-700 dark:text-gray-200">Fórmula</h3>
        <p class="font-mono text-sm text-gray-800 dark:text-gray-100 bg-gray-50 dark:bg-gray-900 rounded-md px-3 py-2 overflow-x-auto">
            Saldo actual = Saldo inicial + Ingresos − Gastos + Préstamos recibidos
        </p>
        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
            Suma todo lo registrado con fecha hasta hoy ({{ $asOf->format('d/m/Y') }}) — no solo este mes. Un ingreso o
            gasto con fecha futura todavía no cuenta aquí (sí aparece en las proyecciones).
        </p>
    </x-card>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4 mb-6">
        <x-stat-card label="Saldo inicial" :value="'S/ ' . number_format($startingBalance, 2)">
            <a href="{{ route('settings.edit') }}" class="text-brand-600 hover:underline">Configurado en Configuración</a>
        </x-stat-card>
        <x-stat-card label="+ Ingresos" :value="'S/ ' . number_format($incomeTotal, 2)" tone="positive">
            {{ $incomeCount }} registro(s) ·
            <a href="{{ route('incomes.index') }}" class="text-brand-600 hover:underline">Ver ingresos</a>
        </x-stat-card>
        <x-stat-card label="− Gastos" :value="'S/ ' . number_format($expenseTotal, 2)" tone="negative">
            {{ $expenseCount }} registro(s) ·
            <a href="{{ route('expenses.index') }}" class="text-brand-600 hover:underline">Ver gastos</a>
        </x-stat-card>
        <x-stat-card label="+ Préstamos recibidos" :value="'S/ ' . number_format($loanTotal, 2)">
            {{ $loanCount }} registro(s) ·
            <a href="{{ route('loans.index') }}" class="text-brand-600 hover:underline">Ver préstamos</a>
        </x-stat-card>
    </div>

    <x-card>
        <div class="flex items-center justify-between">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">Resultado</h3>
            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">S/ {{ number_format($currentBalance, 2) }}</p>
        </div>
        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
            {{ number_format($startingBalance, 2) }} + {{ number_format($incomeTotal, 2) }} − {{ number_format($expenseTotal, 2) }} + {{ number_format($loanTotal, 2) }}
            = {{ number_format($currentBalance, 2) }}
        </p>
        <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">
            Un préstamo que diste (no que recibiste) no resta aquí directamente — ni los pagos que haces de un
            préstamo recibido se restan aparte: ambos ya quedan reflejados como un gasto normal cuando los registras,
            así que no se cuentan dos veces.
        </p>
    </x-card>
</x-app-layout>
