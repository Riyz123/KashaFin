<x-app-layout title="Metas">
    <x-page-header title="Mis metas" subtitle="Establece tus objetivos y hazlos realidad.">
        <a href="{{ route('goals.history') }}" class="text-sm text-brand-600 hover:underline self-center">Ver historial</a>
        <a href="{{ route('goals.create') }}">
            <x-primary-button>
                <x-icon name="plus" class="w-4 h-4 mr-1" /> Nueva meta
            </x-primary-button>
        </a>
    </x-page-header>

    @if ($goals->isEmpty())
        <x-empty-state message="Todavía no tienes metas activas." />
    @else
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @php $warning = session('goalWarning'); @endphp
            @foreach ($goals as $goal)
                <div x-data="{ showForm: {{ $warning && $warning['goal_id'] === $goal->id ? 'true' : 'false' }} }" class="rounded-xl bg-white dark:bg-gray-800 shadow-sm border border-gray-100 dark:border-gray-700 p-4 sm:p-5">
                    <div class="flex items-start justify-between">
                        <h3 class="font-semibold text-gray-800 dark:text-gray-100">{{ $goal->name }}</h3>
                        <div class="flex gap-2 text-xs">
                            <a href="{{ route('goals.edit', $goal) }}" class="text-brand-600 hover:underline">Editar</a>
                            <form method="POST" action="{{ route('goals.destroy', $goal) }}" onsubmit="return confirm('¿Eliminar esta meta?');">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-600 hover:underline">Eliminar</button>
                            </form>
                        </div>
                    </div>

                    <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">
                        S/ {{ number_format($goal->current_amount, 2) }} de S/ {{ number_format($goal->target_amount, 2) }}
                    </p>
                    <div class="mt-1">
                        <x-progress-bar :percent="$goal->progress_percent" />
                    </div>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $goal->progress_percent }}% completado</p>

                    @if ($goal->target_date)
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Fecha objetivo: {{ $goal->target_date->format('d/m/Y') }}</p>
                    @endif

                    @if ($warning && $warning['goal_id'] === $goal->id)
                        <x-alert-banner tone="warning" class="mt-3">
                            {{ $warning['message'] }}
                        </x-alert-banner>
                    @endif

                    <button @click="showForm = !showForm" class="mt-3 text-sm font-medium text-brand-600 hover:underline">
                        Aportar a esta meta
                    </button>

                    <form x-show="showForm" x-cloak method="POST" action="{{ route('goals.contributions.store', $goal) }}" class="mt-3 space-y-2">
                        @csrf
                        <input type="number" step="0.01" min="0.01" name="amount" placeholder="Monto" value="{{ old('amount') }}" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 text-sm focus:border-brand-500 focus:ring-brand-500" required>
                        <input type="date" name="date" value="{{ old('date', now()->toDateString()) }}" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 text-sm focus:border-brand-500 focus:ring-brand-500" required>
                        <input type="text" name="note" placeholder="Nota (opcional)" value="{{ old('note') }}" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 text-sm focus:border-brand-500 focus:ring-brand-500">
                        @if ($warning && $warning['goal_id'] === $goal->id)
                            <input type="hidden" name="confirmed" value="1">
                            <button type="submit" class="w-full rounded-md bg-amber-500 px-3 py-1.5 text-sm font-semibold text-white hover:bg-amber-600">Confirmar de todas formas</button>
                        @else
                            <button type="submit" class="w-full rounded-md bg-brand-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-brand-700">Guardar aporte</button>
                        @endif
                    </form>
                </div>
            @endforeach
        </div>
    @endif
</x-app-layout>
