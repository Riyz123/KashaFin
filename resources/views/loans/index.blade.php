<x-app-layout title="Préstamos">
    <x-page-header title="Préstamos" subtitle="Dinero que prestaste o que te prestaron.">
        <a href="{{ route('loans.create') }}">
            <x-primary-button>
                <x-icon name="plus" class="w-4 h-4 mr-1" /> Nuevo préstamo
            </x-primary-button>
        </a>
    </x-page-header>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 mb-6">
        <x-card>
            <p class="text-sm text-gray-500 dark:text-gray-400">Me deben (pendiente)</p>
            <p class="text-2xl font-bold text-brand-600">S/ {{ number_format($totals['owed_to_me'], 2) }}</p>
        </x-card>
        <x-card>
            <p class="text-sm text-gray-500 dark:text-gray-400">Yo debo (pendiente)</p>
            <p class="text-2xl font-bold text-red-600">S/ {{ number_format($totals['i_owe'], 2) }}</p>
        </x-card>
    </div>

    <x-card>
        @if ($loans->isEmpty())
            <x-empty-state message="No tienes préstamos registrados todavía." />
        @else
            <div class="divide-y divide-gray-100 dark:divide-gray-700">
                @foreach ($loans as $loan)
                    <div class="flex flex-wrap items-center justify-between gap-3 py-3">
                        <div>
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">
                                {{ $loan->person_name }}
                                @if ($loan->person_user_id)
                                    <span title="Cuenta de KashaFin" class="text-brand-600">✓</span>
                                @endif
                                <span class="ml-1 rounded-full px-2 py-0.5 text-xs font-semibold {{ $loan->direction === 'prestado' ? 'bg-brand-100 text-brand-800 dark:bg-brand-900/40 dark:text-brand-200' : 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200' }}">
                                    {{ $loan->direction === 'prestado' ? 'Me debe' : 'Le debo' }}
                                </span>
                                <span class="ml-1 rounded-full px-2 py-0.5 text-xs font-semibold {{ $loan->isPending() ? 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300' }}">
                                    {{ $loan->isPending() ? 'Pendiente' : 'Pagado' }}
                                </span>
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                S/ {{ number_format($loan->amount, 2) }} &middot; {{ $loan->date->format('d/m/Y') }}
                                @if ($loan->due_date) &middot; vence {{ $loan->due_date->format('d/m/Y') }} @endif
                            </p>
                            @if ($loan->notes)
                                <p class="text-xs text-gray-400">{{ $loan->notes }}</p>
                            @endif
                        </div>

                        <div class="flex items-center gap-3 text-xs">
                            @if ($loan->isPending())
                                <form method="POST" action="{{ route('loans.mark-paid', $loan) }}">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="text-brand-600 hover:underline">Marcar pagado</button>
                                </form>
                            @endif
                            <a href="{{ route('loans.edit', $loan) }}" class="text-gray-500 hover:underline dark:text-gray-400">Editar</a>
                            <form method="POST" action="{{ route('loans.destroy', $loan) }}" onsubmit="return confirm('¿Eliminar este préstamo?');">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-600 hover:underline">Eliminar</button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </x-card>
</x-app-layout>
