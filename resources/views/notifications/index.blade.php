<x-dynamic-component :component="$layout" title="Notificaciones">
    <x-page-header title="Notificaciones" :subtitle="$isStaff ? 'Avisos de solicitudes que necesitan tu atención.' : 'Historial de alertas de liquidez generadas para tu cuenta.'">
        @unless ($isStaff)
            <form method="POST" action="{{ route('notifications.send') }}">
                @csrf
                <x-secondary-button type="submit">Revisar ahora</x-secondary-button>
            </form>
        @endunless
    </x-page-header>

    @unless ($isStaff)
        <h3 class="mb-2 text-sm font-semibold text-gray-700 dark:text-gray-200">Alertas de liquidez</h3>
        <x-card>
            @if ($alerts->isEmpty())
                <x-empty-state message="No tienes alertas registradas todavía." />
            @else
                <div class="space-y-3">
                    @foreach ($alerts as $alert)
                        <div class="flex items-start gap-3 border-b border-gray-100 dark:border-gray-700 pb-3 last:border-0 last:pb-0">
                            <x-icon name="bell" class="w-5 h-5 text-amber-500 mt-0.5 shrink-0" />
                            <div>
                                <p class="text-sm text-gray-800 dark:text-gray-100">
                                    Proyección de S/ {{ number_format($alert->projected_balance, 2) }}, por debajo del umbral de S/ {{ number_format($alert->threshold, 2) }}
                                    <span class="text-xs text-gray-500 dark:text-gray-400">({{ $alert->channel === 'email' ? 'enviado por correo' : 'panel' }})</span>
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $alert->sent_at->format('d/m/Y H:i') }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="mt-4">{{ $alerts->links() }}</div>
            @endif
        </x-card>
    @endunless

    <h3 class="mb-2 mt-6 text-sm font-semibold text-gray-700 dark:text-gray-200">Recordatorios</h3>
    <x-card>
        @if ($reminders->isEmpty())
            <x-empty-state message="No tienes recordatorios todavía." />
        @else
            <div class="space-y-3">
                @foreach ($reminders as $reminder)
                    @php($link = \App\Support\NotificationLink::for($reminder->data))
                    <div class="flex items-start gap-3 border-b border-gray-100 dark:border-gray-700 pb-3 last:border-0 last:pb-0">
                        <x-icon name="bell" class="w-5 h-5 text-brand-500 mt-0.5 shrink-0" />
                        <div class="flex-1">
                            <p class="text-sm text-gray-800 dark:text-gray-100">{{ $reminder->data['message'] ?? 'Notificación' }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $reminder->created_at->format('d/m/Y H:i') }}</p>
                            @if ($link)
                                <a href="{{ $link['url'] }}" class="mt-1 inline-block text-xs font-semibold text-brand-600 hover:underline">{{ $link['label'] }} &rarr;</a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="mt-4">{{ $reminders->links() }}</div>
        @endif
    </x-card>
</x-dynamic-component>
