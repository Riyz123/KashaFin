<x-app-layout title="Gastos compartidos">
    <x-page-header title="Gastos compartidos" subtitle="Gastos que pagaste tú pero se dividen con otras personas.">
        <a href="{{ route('shared-expenses.create') }}">
            <x-primary-button>
                <x-icon name="plus" class="w-4 h-4 mr-1" /> Nuevo gasto compartido
            </x-primary-button>
        </a>
    </x-page-header>

    <x-card class="mb-6 max-w-xs">
        <p class="text-sm text-gray-500 dark:text-gray-400">Te deben en total (pendiente)</p>
        <p class="text-2xl font-bold text-brand-600">S/ {{ number_format($totalOwedToMe, 2) }}</p>
    </x-card>

    @if ($sharedExpenses->isEmpty())
        <x-empty-state message="No tienes gastos compartidos registrados todavía." />
    @else
        <div class="space-y-4">
            @foreach ($sharedExpenses as $shared)
                <x-card>
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="font-semibold text-gray-800 dark:text-gray-100">{{ $shared->description }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $shared->date->format('d/m/Y') }} &middot; Total S/ {{ number_format($shared->total_amount, 2) }}
                                &middot; Tu parte S/ {{ number_format($shared->my_share, 2) }}
                                @if ($shared->category) &middot; {{ $shared->category->name }} @endif
                            </p>
                        </div>
                        <form method="POST" action="{{ route('shared-expenses.destroy', $shared) }}" onsubmit="return confirm('¿Eliminar este gasto compartido? También se eliminará tu parte registrada como gasto.');">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-xs text-red-600 hover:underline">Eliminar</button>
                        </form>
                    </div>

                    <div class="mt-3 divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach ($shared->participants as $participant)
                            <div class="flex items-center justify-between gap-2 py-2 text-sm">
                                <span class="text-gray-700 dark:text-gray-300">{{ $participant->name }}</span>
                                <div class="flex items-center gap-2">
                                    <span class="font-medium {{ $participant->is_paid ? 'text-gray-400 line-through' : 'text-gray-800 dark:text-gray-100' }}">
                                        S/ {{ number_format($participant->amount_owed, 2) }}
                                    </span>
                                    <form method="POST" action="{{ route('shared-expenses.toggle-paid', $participant) }}">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $participant->is_paid ? 'bg-brand-100 text-brand-800 dark:bg-brand-900/40 dark:text-brand-200' : 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300' }}">
                                            {{ $participant->is_paid ? 'Pagado' : 'Pendiente' }}
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </x-card>
            @endforeach
        </div>
    @endif
</x-app-layout>
