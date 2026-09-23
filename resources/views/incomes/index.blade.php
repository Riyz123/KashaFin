<x-app-layout title="Ingresos">
    <x-page-header title="Mis ingresos" subtitle="Registra y gestiona todas tus fuentes de ingreso.">
        <a href="{{ route('incomes.create') }}">
            <x-primary-button>
                <x-icon name="plus" class="w-4 h-4 mr-1" /> Nuevo ingreso
            </x-primary-button>
        </a>
    </x-page-header>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3 mb-6">
        <x-stat-card label="Total del mes" :value="'S/ ' . number_format($totalMonth, 2)" />
        <x-stat-card label="Ingresos fijos" :value="'S/ ' . number_format($fixedMonth, 2)" tone="positive" />
        <x-stat-card label="Ingresos variables" :value="'S/ ' . number_format($variableMonth, 2)" />
    </div>

    <x-card>
        @if ($incomes->isEmpty())
            <x-empty-state message="Todavía no registraste ningún ingreso." />
        @else
            <div class="sm:hidden space-y-3">
                @foreach ($incomes as $income)
                    <div class="rounded-lg border border-gray-100 dark:border-gray-700 p-3">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="font-medium text-gray-800 dark:text-gray-100">{{ $income->description }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $income->date->format('d/m/Y') }} &middot; {{ $income->type === 'fijo' ? 'Fijo' : 'Variable' }}</p>
                            </div>
                            <span class="font-semibold text-brand-700 dark:text-brand-400">S/ {{ number_format($income->amount, 2) }}</span>
                        </div>
                        <div class="mt-2 flex gap-3 text-sm">
                            <a href="{{ route('incomes.edit', $income) }}" class="text-brand-600 hover:underline">Editar</a>
                            <form method="POST" action="{{ route('incomes.destroy', $income) }}" onsubmit="return confirm('¿Eliminar este ingreso?');">
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
                            <th class="py-2 pr-4">Tipo</th>
                            <th class="py-2 pr-4">Monto</th>
                            <th class="py-2 pr-4 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach ($incomes as $income)
                            <tr>
                                <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $income->date->format('d/m/Y') }}</td>
                                <td class="py-3 pr-4 text-gray-800 dark:text-gray-100">{{ $income->description }}</td>
                                <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $income->type === 'fijo' ? 'Fijo' : 'Variable' }}</td>
                                <td class="py-3 pr-4 font-semibold text-brand-700 dark:text-brand-400">S/ {{ number_format($income->amount, 2) }}</td>
                                <td class="py-3 pr-4 text-right space-x-3">
                                    <a href="{{ route('incomes.edit', $income) }}" class="text-brand-600 hover:underline">Editar</a>
                                    <form method="POST" action="{{ route('incomes.destroy', $income) }}" class="inline" onsubmit="return confirm('¿Eliminar este ingreso?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:underline">Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">{{ $incomes->links() }}</div>
        @endif
    </x-card>
</x-app-layout>
