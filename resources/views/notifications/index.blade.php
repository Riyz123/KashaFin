<x-app-layout title="Notificaciones">
    <x-page-header title="Notificaciones" subtitle="Historial de alertas de liquidez generadas para tu cuenta.">
        <form method="POST" action="{{ route('notifications.send') }}">
            @csrf
            <x-secondary-button type="submit">Revisar ahora</x-secondary-button>
        </form>
    </x-page-header>

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
</x-app-layout>
