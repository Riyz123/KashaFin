<x-app-layout title="Reportes">
    <x-page-header title="Reportes y visualización financiera" subtitle="Analiza tus gastos e ingresos por periodo." />

    <x-card class="mb-6">
        <form method="GET" action="{{ route('reports.index') }}" class="flex flex-wrap items-end gap-4">
            <div>
                <x-input-label for="from" value="Desde" />
                <x-text-input id="from" name="from" type="date" class="mt-1" :value="$from->toDateString()" />
            </div>
            <div>
                <x-input-label for="to" value="Hasta" />
                <x-text-input id="to" name="to" type="date" class="mt-1" :value="$to->toDateString()" />
            </div>
            <x-primary-button>Aplicar</x-primary-button>
            <div class="ml-auto flex gap-2">
                <a href="{{ route('reports.pdf', ['from' => $from->toDateString(), 'to' => $to->toDateString()]) }}" class="inline-flex items-center gap-1 rounded-md border border-gray-300 dark:border-gray-600 px-3 py-2 text-xs font-semibold text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700">
                    <x-icon name="download" class="w-4 h-4" /> PDF
                </a>
                <a href="{{ route('reports.csv', ['from' => $from->toDateString(), 'to' => $to->toDateString()]) }}" class="inline-flex items-center gap-1 rounded-md border border-gray-300 dark:border-gray-600 px-3 py-2 text-xs font-semibold text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700">
                    <x-icon name="download" class="w-4 h-4" /> Excel/CSV
                </a>
            </div>
        </form>
    </x-card>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2 mb-6">
        <x-card>
            <h3 class="mb-3 font-semibold text-gray-800 dark:text-gray-100">Gastos por categoría</h3>
            @if ($byCategory->isEmpty())
                <x-empty-state message="No hay gastos en este periodo." />
            @else
                <div class="h-64">
                    <canvas id="categoryChart"></canvas>
                </div>
                <script type="application/json" id="category-series">{!! \Illuminate\Support\Js::encode($byCategory) !!}</script>
            @endif
        </x-card>

        <x-card>
            <h3 class="mb-3 font-semibold text-gray-800 dark:text-gray-100">Ingresos vs. gastos</h3>
            <div class="h-64">
                <canvas id="comparisonChart"></canvas>
            </div>
            <script type="application/json" id="comparison-series">{!! \Illuminate\Support\Js::encode($summary) !!}</script>
            <div class="mt-4 grid grid-cols-3 gap-2 text-center text-sm">
                <div><p class="text-gray-500 dark:text-gray-400">Ingresos</p><p class="font-semibold text-brand-600">S/ {{ number_format($summary['income'], 2) }}</p></div>
                <div><p class="text-gray-500 dark:text-gray-400">Gastos</p><p class="font-semibold text-red-600">S/ {{ number_format($summary['expense'], 2) }}</p></div>
                <div><p class="text-gray-500 dark:text-gray-400">Balance</p><p class="font-semibold {{ $summary['balance'] >= 0 ? 'text-brand-600' : 'text-red-600' }}">S/ {{ number_format($summary['balance'], 2) }}</p></div>
            </div>
        </x-card>
    </div>

    <x-card>
        <h3 class="mb-3 font-semibold text-gray-800 dark:text-gray-100">Detalle por categoría</h3>
        @if ($byCategory->isEmpty())
            <x-empty-state message="No hay gastos en este periodo." />
        @else
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-700">
                        <th class="py-2 pr-4">Categoría</th>
                        <th class="py-2 pr-4">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach ($byCategory as $row)
                        <tr>
                            <td class="py-2 pr-4 text-gray-800 dark:text-gray-100">{{ $row['category'] }}</td>
                            <td class="py-2 pr-4 font-semibold text-gray-700 dark:text-gray-200">S/ {{ number_format($row['total'], 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </x-card>
</x-app-layout>
