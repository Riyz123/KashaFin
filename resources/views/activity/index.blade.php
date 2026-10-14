<x-app-layout title="Trazabilidad">
    <x-page-header title="Trazabilidad" subtitle="Todo lo que creaste o eliminaste en tus secciones principales." />

    <x-card class="mb-6">
        <form method="GET" action="{{ route('activity.index') }}" class="flex flex-wrap items-end gap-4">
            <div>
                <x-input-label for="section" value="Sección" />
                <select id="section" name="section" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-brand-500 focus:ring-brand-500">
                    <option value="">Todas</option>
                    <option value="income" @selected($section === 'income')>Ingresos</option>
                    <option value="expense" @selected($section === 'expense')>Gastos</option>
                    <option value="budget" @selected($section === 'budget')>Presupuesto</option>
                    <option value="goal" @selected($section === 'goal')>Metas</option>
                    <option value="loan" @selected($section === 'loan')>Préstamos</option>
                    <option value="shared_expense" @selected($section === 'shared_expense')>Gastos compartidos</option>
                </select>
            </div>
            <x-primary-button>Filtrar</x-primary-button>
            <a href="{{ route('activity.index') }}" class="text-sm text-gray-500 hover:underline dark:text-gray-400">Limpiar</a>
        </form>
    </x-card>

    <x-card>
        @if ($activities->isEmpty())
            <x-empty-state message="Todavía no hay nada registrado." />
        @else
            <div class="space-y-3">
                @foreach ($activities as $activity)
                    <div class="flex items-start justify-between gap-3 border-b border-gray-100 dark:border-gray-700 pb-3 last:border-0 last:pb-0">
                        <p class="text-sm text-gray-800 dark:text-gray-100">{{ $activity->description }}</p>
                        <p class="shrink-0 text-xs text-gray-500 dark:text-gray-400">{{ $activity->created_at->format('d/m/Y H:i') }}</p>
                    </div>
                @endforeach
            </div>
            <div class="mt-4">{{ $activities->links() }}</div>
        @endif
    </x-card>
</x-app-layout>
