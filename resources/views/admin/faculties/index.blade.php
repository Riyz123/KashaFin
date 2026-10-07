<x-admin-layout title="Facultades y decanos">
    <x-page-header title="Facultades y decanos" subtitle="Cada decano administra solo a los estudiantes de su propia facultad." />

    <x-card class="mb-6">
        <h3 class="mb-3 font-semibold text-gray-800 dark:text-gray-100">Nueva facultad</h3>
        <form method="POST" action="{{ route('admin.faculties.store') }}" class="flex flex-wrap items-end gap-4">
            @csrf
            <div class="flex-1 min-w-[200px]">
                <x-input-label for="name" value="Nombre de la facultad" />
                <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" placeholder="Ingeniería" required />
                <x-input-error :messages="$errors->get('name')" class="mt-1" />
            </div>
            <x-primary-button>Crear facultad</x-primary-button>
        </form>
    </x-card>

    @if ($faculties->isEmpty())
        <x-empty-state message="Todavía no creaste ninguna facultad." />
    @else
        <div class="space-y-4">
            @foreach ($faculties as $faculty)
                @php($dean = $faculty->users->first())
                <x-card x-data="{ inviting: false }">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h3 class="font-semibold text-gray-800 dark:text-gray-100">{{ $faculty->name }}</h3>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $faculty->students_count }} estudiante(s)</p>
                        </div>
                        @if (! $dean)
                            <button @click="inviting = !inviting" class="text-sm text-brand-600 hover:underline">Asignar decano</button>
                        @endif
                    </div>

                    @if ($dean)
                        <div class="mt-3 flex flex-wrap items-center justify-between gap-3 border-t border-gray-100 dark:border-gray-700 pt-3">
                            <div>
                                <p class="text-sm text-gray-800 dark:text-gray-100">Decano: {{ $dean->name }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $dean->email }} &middot; {{ $dean->is_active ? 'Activo' : 'Desactivado' }}</p>
                            </div>
                            <div class="flex gap-3 text-sm">
                                <form method="POST" action="{{ route('admin.users.toggle-active', $dean) }}">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="text-brand-600 hover:underline">{{ $dean->is_active ? 'Desactivar' : 'Activar' }}</button>
                                </form>
                                <form method="POST" action="{{ route('admin.users.destroy', $dean) }}" onsubmit="return confirm('¿Eliminar esta cuenta de decano?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:underline">Eliminar</button>
                                </form>
                            </div>
                        </div>
                    @else
                        <form x-show="inviting" x-cloak method="POST" action="{{ route('admin.faculties.deans.store', $faculty) }}" class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-3 border-t border-gray-100 dark:border-gray-700 pt-4">
                            @csrf
                            <div>
                                <x-input-label value="Nombre" />
                                <x-text-input name="name" type="text" class="mt-1 block w-full" required />
                            </div>
                            <div>
                                <x-input-label value="Correo" />
                                <x-text-input name="email" type="email" class="mt-1 block w-full" required />
                            </div>
                            <div>
                                <x-input-label value="Contraseña temporal" />
                                <x-text-input name="password" type="password" class="mt-1 block w-full" required />
                            </div>
                            <div class="sm:col-span-3">
                                <x-primary-button>Crear cuenta de decano</x-primary-button>
                            </div>
                        </form>
                    @endif
                </x-card>
            @endforeach
        </div>
    @endif
</x-admin-layout>
