<x-app-layout title="Presupuesto">
    <x-page-header title="Presupuesto" :subtitle="'Define límites de gasto y mantén el control · ' . $periodMonth->translatedFormat('F Y')">
        <a href="{{ route('budgets.create') }}">
            <x-primary-button>
                <x-icon name="plus" class="w-4 h-4 mr-1" /> Nuevo presupuesto
            </x-primary-button>
        </a>
    </x-page-header>

    <x-card class="mb-6">
        <form method="GET" action="{{ route('budgets.index') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-3 items-end">
            <div>
                <x-input-label for="search" value="Categoría" />
                <x-text-input id="search" name="search" type="text" class="mt-1 block w-full" placeholder="Buscar por categoría..." :value="$search" />
            </div>
            <div>
                <x-input-label for="month" value="Mes" />
                <input id="month" name="month" type="month" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-brand-500 focus:ring-brand-500" value="{{ $periodMonth->format('Y-m') }}">
            </div>
            <div>
                <x-primary-button>Filtrar</x-primary-button>
                <a href="{{ route('budgets.index') }}" class="ml-2 text-sm text-gray-500 hover:underline dark:text-gray-400">Limpiar filtros</a>
            </div>
        </form>
    </x-card>

    <x-card>
        @if ($budgets->isEmpty())
            <x-empty-state message="No se encontraron presupuestos con esos filtros." />
        @else
            <div class="space-y-5">
                @foreach ($budgets as $budget)
                    <div class="border-b border-gray-100 dark:border-gray-700 pb-4 last:border-0 last:pb-0">
                        <div class="flex flex-wrap items-center justify-between gap-2 mb-1">
                            <div class="flex items-center gap-2">
                                <span class="font-medium text-gray-800 dark:text-gray-100">{{ $budget->category->name }}</span>
                                @if ($budget->is_over_warning_threshold)
                                    <span class="rounded-full bg-red-100 px-2 py-0.5 text-xs font-semibold text-red-700 dark:bg-red-900/40 dark:text-red-300">¡Cerca del límite!</span>
                                @endif
                            </div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                S/ {{ number_format($budget->spentAmount(), 2) }} de S/ {{ number_format($budget->amount, 2) }}
                                ({{ $budget->percent_consumed }}%)
                            </div>
                        </div>
                        <x-progress-bar :percent="$budget->percent_consumed" />
                        <div class="mt-2 flex gap-3 text-xs">
                            <a href="{{ route('budgets.edit', $budget) }}" class="text-brand-600 hover:underline">Editar</a>
                            <form method="POST" action="{{ route('budgets.destroy', $budget) }}" onsubmit="return confirm('¿Eliminar este presupuesto?');">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-600 hover:underline">Eliminar</button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </x-card>

    <x-activity-log logName="budget" />
</x-app-layout>
