<x-app-layout title="Historial">
    <x-page-header title="Historial de movimientos" subtitle="Todos tus ingresos y gastos en un solo lugar." />

    <x-card class="mb-6">
        <form method="GET" action="{{ route('history.index') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5 items-end">
            <div class="lg:col-span-2">
                <x-input-label for="search" value="Buscar" />
                <x-text-input id="search" name="search" type="text" class="mt-1 block w-full" placeholder="Descripción..." :value="$search" />
            </div>
            <div>
                <x-input-label for="from" value="Desde" />
                <x-text-input id="from" name="from" type="date" class="mt-1 block w-full" :value="optional($from)->toDateString()" />
            </div>
            <div>
                <x-input-label for="to" value="Hasta" />
                <x-text-input id="to" name="to" type="date" class="mt-1 block w-full" :value="optional($to)->toDateString()" />
            </div>
            <div>
                <x-input-label for="category_id" value="Categoría" />
                <select id="category_id" name="category_id" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-brand-500 focus:ring-brand-500">
                    <option value="">Todas</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected($categoryId == $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="lg:col-span-5">
                <x-primary-button>Filtrar</x-primary-button>
                <a href="{{ route('history.index') }}" class="ml-2 text-sm text-gray-500 hover:underline dark:text-gray-400">Limpiar filtros</a>
            </div>
        </form>
    </x-card>

    <x-card>
        @if ($movements->isEmpty())
            <x-empty-state message="No se encontraron movimientos con esos filtros." />
        @else
            <div class="sm:hidden space-y-3">
                @foreach ($movements as $movement)
                    <div class="rounded-lg border border-gray-100 dark:border-gray-700 p-3">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="font-medium text-gray-800 dark:text-gray-100">{{ $movement['description'] ?: $movement['category'] }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $movement['date']->format('d/m/Y') }} &middot; {{ $movement['category'] }}</p>
                            </div>
                            <span class="font-semibold {{ $movement['type'] === 'ingreso' ? 'text-brand-700 dark:text-brand-400' : 'text-red-600 dark:text-red-400' }}">
                                {{ $movement['type'] === 'ingreso' ? '+' : '-' }} S/ {{ number_format($movement['amount'], 2) }}
                            </span>
                        </div>
                        <form method="POST" action="{{ route('history.duplicate', ['type' => $movement['type'] === 'ingreso' ? 'ingreso' : 'gasto', 'id' => $movement['id']]) }}" class="mt-2">
                            @csrf
                            <button type="submit" class="inline-flex items-center gap-1 text-xs text-brand-600 hover:underline">
                                <x-icon name="duplicate" class="w-3.5 h-3.5" /> Duplicar
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>

            <div class="hidden sm:block overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-700">
                            <th class="py-2 pr-4">Fecha</th>
                            <th class="py-2 pr-4">Tipo</th>
                            <th class="py-2 pr-4">Categoría</th>
                            <th class="py-2 pr-4">Descripción</th>
                            <th class="py-2 pr-4">Monto</th>
                            <th class="py-2 pr-4 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach ($movements as $movement)
                            <tr>
                                <td class="py-2 pr-4 text-gray-600 dark:text-gray-300">{{ $movement['date']->format('d/m/Y') }}</td>
                                <td class="py-2 pr-4 text-gray-600 dark:text-gray-300 capitalize">{{ $movement['type'] }}</td>
                                <td class="py-2 pr-4 text-gray-600 dark:text-gray-300">{{ $movement['category'] }}</td>
                                <td class="py-2 pr-4 text-gray-800 dark:text-gray-100">{{ $movement['description'] ?: '—' }}</td>
                                <td class="py-2 pr-4 font-semibold {{ $movement['type'] === 'ingreso' ? 'text-brand-700 dark:text-brand-400' : 'text-red-600 dark:text-red-400' }}">
                                    {{ $movement['type'] === 'ingreso' ? '+' : '-' }} S/ {{ number_format($movement['amount'], 2) }}
                                </td>
                                <td class="py-2 pr-4 text-right">
                                    <form method="POST" action="{{ route('history.duplicate', ['type' => $movement['type'] === 'ingreso' ? 'ingreso' : 'gasto', 'id' => $movement['id']]) }}">
                                        @csrf
                                        <button type="submit" class="inline-flex items-center gap-1 text-xs text-brand-600 hover:underline">
                                            <x-icon name="duplicate" class="w-3.5 h-3.5" /> Duplicar
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-card>
</x-app-layout>
