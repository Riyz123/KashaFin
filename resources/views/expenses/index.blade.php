<x-app-layout title="Gastos">
    <x-page-header title="Mis gastos" subtitle="Registra y clasifica tus gastos para tener un mejor control.">
        <a href="{{ route('expenses.create') }}">
            <x-primary-button>
                <x-icon name="plus" class="w-4 h-4 mr-1" /> Nuevo gasto
            </x-primary-button>
        </a>
    </x-page-header>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3 mb-6">
        <x-stat-card label="Total del mes" :value="'S/ ' . number_format($totalMonth, 2)" tone="negative" />
    </div>

    <x-card>
        @if ($expenses->isEmpty())
            <x-empty-state message="Todavía no registraste ningún gasto." />
        @else
            <div class="sm:hidden space-y-3">
                @foreach ($expenses as $expense)
                    <div class="rounded-lg border border-gray-100 dark:border-gray-700 p-3">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="font-medium text-gray-800 dark:text-gray-100">{{ $expense->description ?: ($expense->category?->name ?? 'Gasto') }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $expense->date->format('d/m/Y') }} &middot; {{ $expense->category?->name ?? 'Sin categoría' }}</p>
                            </div>
                            <span class="font-semibold text-red-600 dark:text-red-400">S/ {{ number_format($expense->amount, 2) }}</span>
                        </div>
                        <div class="mt-2 flex gap-3 text-sm">
                            <a href="{{ route('expenses.edit', $expense) }}" class="text-brand-600 hover:underline">Editar</a>
                            <form method="POST" action="{{ route('expenses.destroy', $expense) }}" onsubmit="return confirm('¿Eliminar este gasto?');">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-600 hover:underline">Eliminar</button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="hidden sm:block overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-700">
                            <th class="py-2 pr-4">Fecha</th>
                            <th class="py-2 pr-4">Descripción</th>
                            <th class="py-2 pr-4">Categoría</th>
                            <th class="py-2 pr-4">Monto</th>
                            <th class="py-2 pr-4 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach ($expenses as $expense)
                            <tr>
                                <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $expense->date->format('d/m/Y') }}</td>
                                <td class="py-3 pr-4 text-gray-800 dark:text-gray-100">{{ $expense->description ?: '—' }}</td>
                                <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $expense->category?->name ?? 'Sin categoría' }}</td>
                                <td class="py-3 pr-4 font-semibold text-red-600 dark:text-red-400">S/ {{ number_format($expense->amount, 2) }}</td>
                                <td class="py-3 pr-4 text-right space-x-3">
                                    <a href="{{ route('expenses.edit', $expense) }}" class="text-brand-600 hover:underline">Editar</a>
                                    <form method="POST" action="{{ route('expenses.destroy', $expense) }}" class="inline" onsubmit="return confirm('¿Eliminar este gasto?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:underline">Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">{{ $expenses->links() }}</div>
        @endif
    </x-card>
</x-app-layout>
