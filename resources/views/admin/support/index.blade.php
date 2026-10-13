<x-admin-layout title="Soporte">
    <x-page-header title="Solicitudes de soporte" subtitle="Pedidos de hablar con un asesor o incidencias reportadas por los estudiantes." />

    <x-card>
        @if ($requests->isEmpty())
            <x-empty-state message="No hay solicitudes de soporte todavía." />
        @else
            <div class="divide-y divide-gray-100 dark:divide-gray-700">
                @foreach ($requests as $request)
                    <div class="flex flex-wrap items-start justify-between gap-3 py-4">
                        <div class="max-w-xl">
                            <div class="flex items-center gap-2">
                                <span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $request->type === 'asesor' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-200' : 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200' }}">
                                    {{ $request->type === 'asesor' ? 'Hablar con asesor' : 'Incidencia' }}
                                </span>
                                <span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $request->isPending() ? 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300' : 'bg-brand-100 text-brand-800 dark:bg-brand-900/40 dark:text-brand-200' }}">
                                    {{ $request->isPending() ? 'Pendiente' : 'Atendido' }}
                                </span>
                            </div>
                            <p class="mt-2 text-sm font-semibold text-gray-800 dark:text-gray-100">
                                {{ $request->user->name }} <span class="font-normal text-gray-500 dark:text-gray-400">({{ $request->user->email }})</span>
                            </p>
                            <p class="mt-1 whitespace-pre-line text-sm text-gray-600 dark:text-gray-300">{{ $request->message }}</p>
                            <p class="mt-2 text-xs text-gray-400">
                                {{ $request->created_at->format('d/m/Y H:i') }}
                                @if ($request->attendedBy)
                                    · atendido por {{ $request->attendedBy->name }} el {{ $request->attended_at->format('d/m/Y H:i') }}
                                @endif
                            </p>
                        </div>

                        @if ($request->isPending())
                            <form method="POST" action="{{ route('admin.support.attend', $request) }}">
                                @csrf @method('PATCH')
                                <x-primary-button type="submit">Marcar como atendido</x-primary-button>
                            </form>
                        @endif
                    </div>
                @endforeach
            </div>

            <div class="mt-4">{{ $requests->links() }}</div>
        @endif
    </x-card>
</x-admin-layout>
