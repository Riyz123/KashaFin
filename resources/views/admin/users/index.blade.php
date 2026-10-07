<x-admin-layout title="Usuarios">
    <x-page-header title="Cuentas de estudiantes" subtitle="Busca, aprueba solicitudes, activa, desactiva o elimina cuentas.">
        <a href="{{ route('admin.users.import-form') }}" class="inline-flex items-center rounded-md border border-gray-300 dark:border-gray-700 px-3 py-2 text-sm font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800">
            Importar CSV
        </a>
        <a href="{{ route('admin.users.export', request()->query()) }}" class="inline-flex items-center rounded-md border border-gray-300 dark:border-gray-700 px-3 py-2 text-sm font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800">
            Exportar CSV
        </a>
    </x-page-header>

    <x-card class="mb-6">
        <form method="GET" action="{{ route('admin.users.index') }}" class="flex flex-wrap items-end gap-4">
            <div class="flex-1 min-w-[200px]">
                <x-input-label for="search" value="Buscar" />
                <x-text-input id="search" name="search" type="text" class="mt-1 block w-full" placeholder="Nombre o correo..." :value="$search" />
            </div>
            <div>
                <x-input-label for="status" value="Estado" />
                <select id="status" name="status" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-brand-500 focus:ring-brand-500">
                    <option value="">Todos</option>
                    <option value="pending" @selected($status === 'pending')>Pendientes de aprobación</option>
                    <option value="active" @selected($status === 'active')>Activas</option>
                    <option value="inactive" @selected($status === 'inactive')>Desactivadas</option>
                </select>
            </div>
            <x-primary-button>Filtrar</x-primary-button>
            <a href="{{ route('admin.users.index') }}" class="text-sm text-gray-500 hover:underline dark:text-gray-400">Limpiar</a>
        </form>
    </x-card>

    <x-card>
        @if ($users->isEmpty())
            <x-empty-state message="No se encontraron estudiantes con esos filtros." />
        @else
            @php
                $badge = function ($user) {
                    if ($user->isPendingApproval()) {
                        return ['Pendiente', 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200'];
                    }

                    return $user->is_active
                        ? ['Activa', 'bg-brand-100 text-brand-800 dark:bg-brand-900/40 dark:text-brand-200']
                        : ['Desactivada', 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300'];
                };
            @endphp

            <div class="sm:hidden space-y-3">
                @foreach ($users as $user)
                    @php([$label, $classes] = $badge($user))
                    <div class="rounded-lg border border-gray-100 dark:border-gray-700 p-3">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="font-medium text-gray-800 dark:text-gray-100">{{ $user->name }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $user->email }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $user->incomes_count }} ingresos &middot; {{ $user->expenses_count }} gastos</p>
                            </div>
                            <span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $classes }}">{{ $label }}</span>
                        </div>
                        <div class="mt-2 flex gap-3 text-sm">
                            @if ($user->isPendingApproval())
                                <form method="POST" action="{{ route('admin.users.approve', $user) }}">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="text-brand-600 hover:underline">Aprobar</button>
                                </form>
                                <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('¿Rechazar esta solicitud?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:underline">Rechazar</button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('admin.users.toggle-active', $user) }}">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="text-brand-600 hover:underline">{{ $user->is_active ? 'Desactivar' : 'Activar' }}</button>
                                </form>
                                <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('¿Eliminar esta cuenta y todos sus datos? Esta acción no se puede deshacer.');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:underline">Eliminar</button>
                                </form>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="hidden sm:block overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-700">
                            <th class="py-2 pr-4">Nombre</th>
                            <th class="py-2 pr-4">Correo</th>
                            @if (auth()->user()->isAdmin())
                                <th class="py-2 pr-4">Facultad</th>
                            @endif
                            <th class="py-2 pr-4">Movimientos</th>
                            <th class="py-2 pr-4">Estado</th>
                            <th class="py-2 pr-4 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach ($users as $user)
                            @php([$label, $classes] = $badge($user))
                            <tr>
                                <td class="py-3 pr-4 text-gray-800 dark:text-gray-100">{{ $user->name }}</td>
                                <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $user->email }}</td>
                                @if (auth()->user()->isAdmin())
                                    <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $user->faculty?->name ?? '—' }}</td>
                                @endif
                                <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $user->incomes_count }} ingresos &middot; {{ $user->expenses_count }} gastos</td>
                                <td class="py-3 pr-4">
                                    <span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $classes }}">{{ $label }}</span>
                                </td>
                                <td class="py-3 pr-4 text-right space-x-3">
                                    @if ($user->isPendingApproval())
                                        <form method="POST" action="{{ route('admin.users.approve', $user) }}" class="inline">
                                            @csrf @method('PATCH')
                                            <button type="submit" class="text-brand-600 hover:underline">Aprobar</button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="inline" onsubmit="return confirm('¿Rechazar esta solicitud?');">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:underline">Rechazar</button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('admin.users.toggle-active', $user) }}" class="inline">
                                            @csrf @method('PATCH')
                                            <button type="submit" class="text-brand-600 hover:underline">{{ $user->is_active ? 'Desactivar' : 'Activar' }}</button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="inline" onsubmit="return confirm('¿Eliminar esta cuenta y todos sus datos? Esta acción no se puede deshacer.');">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:underline">Eliminar</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">{{ $users->links() }}</div>
        @endif
    </x-card>
</x-admin-layout>
