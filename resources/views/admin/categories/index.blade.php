<x-admin-layout title="Categorías globales">
    <x-page-header title="Categorías globales de gasto" subtitle="Estas categorías están disponibles para todos los estudiantes." />

    <x-card class="mb-6 max-w-xl">
        <form method="POST" action="{{ route('admin.categories.store') }}" class="flex items-end gap-3">
            @csrf
            <div class="flex-1">
                <x-input-label for="name" value="Nueva categoría" />
                <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" required />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>
            <x-primary-button>Crear</x-primary-button>
        </form>
    </x-card>

    <x-card>
        @if ($categories->isEmpty())
            <x-empty-state message="No hay categorías globales todavía." />
        @else
            <div class="divide-y divide-gray-100 dark:divide-gray-700">
                @foreach ($categories as $category)
                    <div class="flex flex-wrap items-center justify-between gap-3 py-3">
                        <form method="POST" action="{{ route('admin.categories.update', $category) }}" class="flex items-center gap-2">
                            @csrf @method('PATCH')
                            <input type="text" name="name" value="{{ $category->name }}" class="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 text-sm focus:border-brand-500 focus:ring-brand-500">
                            <button type="submit" class="text-sm text-brand-600 hover:underline">Guardar</button>
                        </form>

                        <div class="flex items-center gap-3">
                            <span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $category->is_active ? 'bg-brand-100 text-brand-800 dark:bg-brand-900/40 dark:text-brand-200' : 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300' }}">
                                {{ $category->is_active ? 'Activa' : 'Desactivada' }}
                            </span>
                            <form method="POST" action="{{ route('admin.categories.toggle-active', $category) }}">
                                @csrf @method('PATCH')
                                <button type="submit" class="text-sm text-brand-600 hover:underline">{{ $category->is_active ? 'Desactivar' : 'Activar' }}</button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </x-card>
</x-admin-layout>
