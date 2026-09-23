<x-app-layout title="Historial de metas">
    <x-page-header title="Historial de metas completadas" subtitle="Aquí puedes ver todas las metas que ya alcanzaste.">
        <a href="{{ route('goals.index') }}" class="text-sm text-brand-600 hover:underline self-center">Volver a metas activas</a>
    </x-page-header>

    @if ($goals->isEmpty())
        <x-empty-state message="Aún no completaste ninguna meta." />
    @else
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($goals as $goal)
                <x-card>
                    <h3 class="font-semibold text-gray-800 dark:text-gray-100">{{ $goal->name }}</h3>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">S/ {{ number_format($goal->target_amount, 2) }}</p>
                    <p class="mt-1 text-xs text-brand-600 dark:text-brand-400">Completada el {{ $goal->completed_at->format('d/m/Y') }}</p>
                </x-card>
            @endforeach
        </div>
    @endif
</x-app-layout>
