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
                                <span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ match (true) {
                                    $request->isPending() => 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
                                    $request->isBeingAttendedLive() => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200',
                                    default => 'bg-brand-100 text-brand-800 dark:bg-brand-900/40 dark:text-brand-200',
                                } }}">
                                    {{ match (true) {
                                        $request->isPending() => 'Pendiente',
                                        $request->isBeingAttendedLive() => 'En atención',
                                        default => 'Atendido',
                                    } }}
                                </span>
                            </div>
                            <p class="mt-2 text-sm font-semibold text-gray-800 dark:text-gray-100">
                                {{ $request->user->name }} <span class="font-normal text-gray-500 dark:text-gray-400">({{ $request->user->email }})</span>
                            </p>
                            <p class="mt-1 whitespace-pre-line text-sm text-gray-600 dark:text-gray-300">{{ $request->message }}</p>
                            <p class="mt-2 text-xs text-gray-400">
                                {{ $request->created_at->format('d/m/Y H:i') }}
                                @if ($request->attendedBy)
                                    · {{ $request->isBeingAttendedLive() ? 'en vivo por' : 'atendido por' }} {{ $request->attendedBy->name }} el {{ $request->attended_at->format('d/m/Y H:i') }}
                                @endif
                            </p>
                        </div>

                        <div class="flex gap-2">
                            @if ($request->isPending())
                                @if ($request->type === 'asesor')
                                    <form method="POST" action="{{ route('admin.support.start-live', $request) }}">
                                        @csrf @method('PATCH')
                                        <x-primary-button type="submit">Atender en vivo</x-primary-button>
                                    </form>
                                @endif
                                <form method="POST" action="{{ route('admin.support.attend', $request) }}">
                                    @csrf @method('PATCH')
                                    <x-secondary-button type="submit">Marcar como atendido</x-secondary-button>
                                </form>
                            @endif
                        </div>
                    </div>

                    @if ($request->isBeingAttendedLive())
                        <div
                            x-data="liveSupportThread({{ $request->chatMessages->max('id') ?? 0 }}, '{{ route('admin.support.messages', $request) }}')"
                            class="mb-4 rounded-lg border border-brand-200 bg-brand-50 p-3 dark:border-brand-800 dark:bg-brand-900/10"
                        >
                            <p class="mb-2 text-xs font-semibold text-brand-700 dark:text-brand-300">Atención en vivo — {{ $request->user->name }}</p>

                            <div class="mb-3 max-h-56 space-y-2 overflow-y-auto">
                                @foreach ($request->chatMessages as $msg)
                                    <div class="{{ $msg->role === 'advisor' ? 'text-right' : 'text-left' }}">
                                        <span class="inline-block max-w-[85%] whitespace-pre-line rounded-lg px-3 py-1.5 text-xs {{ $msg->role === 'advisor' ? 'bg-brand-600 text-white' : 'bg-white text-gray-800 dark:bg-gray-800 dark:text-gray-100' }}">{{ $msg->content }}</span>
                                    </div>
                                @endforeach
                                <template x-for="msg in incoming" :key="msg.id">
                                    <div class="text-left">
                                        <span class="inline-block max-w-[85%] whitespace-pre-line rounded-lg bg-white px-3 py-1.5 text-xs text-gray-800 dark:bg-gray-800 dark:text-gray-100" x-text="msg.content"></span>
                                    </div>
                                </template>
                            </div>

                            <form method="POST" action="{{ route('admin.support.reply', $request) }}" class="flex gap-2">
                                @csrf
                                <label for="reply-{{ $request->id }}" class="sr-only">Responder</label>
                                <input id="reply-{{ $request->id }}" type="text" name="content" required maxlength="1000" placeholder="Escribe tu respuesta..." class="flex-1 rounded-md border-gray-300 text-sm focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                                <x-primary-button type="submit">Enviar</x-primary-button>
                            </form>
                            <form method="POST" action="{{ route('admin.support.finalize', $request) }}" class="mt-2">
                                @csrf @method('PATCH')
                                <button type="submit" class="text-xs text-red-600 hover:underline">Finalizar atención</button>
                            </form>
                        </div>
                    @endif
                @endforeach
            </div>

            <div class="mt-4">{{ $requests->links() }}</div>
        @endif
    </x-card>
</x-admin-layout>
