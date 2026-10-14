<x-app-layout title="Préstamos">
    <x-page-header title="Préstamos" subtitle="Dinero que prestaste o que te prestaron.">
        <a href="{{ route('loans.create') }}">
            <x-primary-button>
                <x-icon name="plus" class="w-4 h-4 mr-1" /> Nuevo préstamo
            </x-primary-button>
        </a>
    </x-page-header>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 mb-6">
        <x-card>
            <p class="text-sm text-gray-500 dark:text-gray-400">Me deben (pendiente)</p>
            <p class="text-2xl font-bold text-brand-600">S/ {{ number_format($totals['owed_to_me'], 2) }}</p>
        </x-card>
        <x-card>
            <p class="text-sm text-gray-500 dark:text-gray-400">Yo debo (pendiente)</p>
            <p class="text-2xl font-bold text-red-600">S/ {{ number_format($totals['i_owe'], 2) }}</p>
        </x-card>
    </div>

    @if ($pendingConfirmations->isNotEmpty())
        <x-card class="mb-6">
            <div class="mb-3 flex items-center justify-between">
                <h3 class="font-semibold text-gray-800 dark:text-gray-100">Confirmaciones pendientes</h3>
                @if ($pendingConfirmations->count() > 1)
                    <form method="POST" action="{{ route('loans.approve-all') }}" onsubmit="return confirm('¿Aceptar las {{ $pendingConfirmations->count() }} confirmaciones pendientes?');">
                        @csrf @method('PATCH')
                        <button type="submit" class="text-xs font-semibold text-brand-600 hover:underline">Aceptar todas</button>
                    </form>
                @endif
            </div>
            <div class="space-y-3">
                @foreach ($pendingConfirmations as $pending)
                    <div class="rounded-lg border border-amber-200 bg-amber-50 p-3 dark:border-amber-800 dark:bg-amber-900/20">
                        <p class="text-sm text-gray-800 dark:text-gray-100">
                            <strong>{{ $pending->user->name }}</strong>
                            {{ $pending->direction === 'prestado' ? 'dice que te prestó' : 'dice que le prestaste' }}
                            <strong>S/ {{ number_format($pending->amount, 2) }}</strong>
                            el {{ $pending->date->format('d/m/Y') }}.
                        </p>
                        <div class="mt-2 flex gap-2">
                            <form method="POST" action="{{ route('loans.approve', $pending) }}">
                                @csrf @method('PATCH')
                                <x-primary-button type="submit">Confirmar</x-primary-button>
                            </form>
                            <form method="POST" action="{{ route('loans.reject', $pending) }}">
                                @csrf @method('PATCH')
                                <button type="submit" class="text-xs text-red-600 hover:underline">Rechazar</button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-card>
    @endif

    @if ($paymentsToConfirm->isNotEmpty())
        <x-card class="mb-6">
            <div class="mb-3 flex items-center justify-between">
                <h3 class="font-semibold text-gray-800 dark:text-gray-100">Pagos por confirmar</h3>
                @if ($paymentsToConfirm->count() > 1)
                    <form method="POST" action="{{ route('loans.confirm-all-payments') }}" onsubmit="return confirm('¿Confirmar los {{ $paymentsToConfirm->count() }} pagos pendientes?');">
                        @csrf @method('PATCH')
                        <button type="submit" class="text-xs font-semibold text-brand-600 hover:underline">Confirmar todos</button>
                    </form>
                @endif
            </div>
            <div class="space-y-3">
                @foreach ($paymentsToConfirm as $payment)
                    <div class="rounded-lg border border-amber-200 bg-amber-50 p-3 dark:border-amber-800 dark:bg-amber-900/20">
                        <p class="text-sm text-gray-800 dark:text-gray-100">
                            <strong>{{ $payment->person_name }}</strong> dice que ya te pagó
                            <strong>S/ {{ number_format($payment->amount, 2) }}</strong>.
                        </p>
                        <div class="mt-2 flex gap-2">
                            <form method="POST" action="{{ route('loans.confirm-payment', $payment) }}">
                                @csrf @method('PATCH')
                                <x-primary-button type="submit">Confirmar que lo recibí</x-primary-button>
                            </form>
                            <form method="POST" action="{{ route('loans.reject-payment', $payment) }}">
                                @csrf @method('PATCH')
                                <button type="submit" class="text-xs text-red-600 hover:underline">No lo recibí</button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-card>
    @endif

    <x-card class="mb-6">
        <form method="GET" action="{{ route('loans.index') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 items-end">
            <div class="lg:col-span-2">
                <x-input-label for="search" value="Persona" />
                <x-text-input id="search" name="search" type="text" class="mt-1 block w-full" placeholder="Buscar por nombre..." :value="$search" />
            </div>
            <div>
                <x-input-label for="direction" value="Dirección" />
                <select id="direction" name="direction" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-brand-500 focus:ring-brand-500">
                    <option value="">Todas</option>
                    <option value="prestado" @selected($direction === 'prestado')>Me deben</option>
                    <option value="recibido" @selected($direction === 'recibido')>Le debo</option>
                </select>
            </div>
            <div>
                <x-input-label for="status" value="Estado" />
                <select id="status" name="status" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-brand-500 focus:ring-brand-500">
                    <option value="">Todos</option>
                    <option value="pendiente_confirmacion" @selected($status === 'pendiente_confirmacion')>Esperando confirmación</option>
                    <option value="pendiente" @selected($status === 'pendiente')>Pendiente</option>
                    <option value="pago_pendiente_confirmacion" @selected($status === 'pago_pendiente_confirmacion')>Pago por confirmar</option>
                    <option value="pagado" @selected($status === 'pagado')>Pagado</option>
                    <option value="rechazado" @selected($status === 'rechazado')>Rechazado</option>
                </select>
            </div>
            <div class="lg:col-span-4 flex items-center gap-4">
                <x-primary-button>Filtrar</x-primary-button>
                <a href="{{ route('loans.index') }}" class="text-sm text-gray-500 hover:underline dark:text-gray-400">Limpiar filtros</a>
                <span class="text-xs text-gray-400">Ordenar:
                    <a href="{{ request()->fullUrlWithQuery(['sort' => 'date', 'dir' => request('sort') === 'date' && request('dir') !== 'desc' ? 'desc' : 'asc']) }}" class="text-brand-600 hover:underline">Fecha</a>
                    &middot;
                    <a href="{{ request()->fullUrlWithQuery(['sort' => 'amount', 'dir' => request('sort') === 'amount' && request('dir') !== 'desc' ? 'desc' : 'asc']) }}" class="text-brand-600 hover:underline">Monto</a>
                </span>
            </div>
        </form>
    </x-card>

    <x-card>
        @if ($loans->isEmpty())
            <x-empty-state message="No se encontraron préstamos con esos filtros." />
        @else
            <div class="divide-y divide-gray-100 dark:divide-gray-700">
                @foreach ($loans as $loan)
                    <div class="flex flex-wrap items-center justify-between gap-3 py-3">
                        <div>
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">
                                {{ $loan->person_name }}
                                @if ($loan->person_user_id)
                                    <span title="Cuenta de KashaFin" class="text-brand-600">✓</span>
                                @endif
                                <span class="ml-1 rounded-full px-2 py-0.5 text-xs font-semibold {{ $loan->direction === 'prestado' ? 'bg-brand-100 text-brand-800 dark:bg-brand-900/40 dark:text-brand-200' : 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200' }}">
                                    {{ $loan->direction === 'prestado' ? 'Me debe' : 'Le debo' }}
                                </span>
                                <span class="ml-1 rounded-full px-2 py-0.5 text-xs font-semibold {{ match (true) {
                                    $loan->isPaid() => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
                                    $loan->isRejected() => 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
                                    default => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200',
                                } }}">
                                    {{ match (true) {
                                        $loan->isPendingConfirmation() => 'Esperando confirmación',
                                        $loan->isRejected() => 'Rechazado',
                                        $loan->isPendingPaymentConfirmation() => 'Pago por confirmar',
                                        $loan->isPaid() => 'Pagado',
                                        default => 'Pendiente',
                                    } }}
                                </span>
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                S/ {{ number_format($loan->amount, 2) }} &middot; {{ $loan->date->format('d/m/Y') }}
                                @if ($loan->due_date) &middot; vence {{ $loan->due_date->format('d/m/Y') }} @endif
                            </p>
                            @if ($loan->notes)
                                <p class="text-xs text-gray-400">{{ $loan->notes }}</p>
                            @endif
                        </div>

                        <div class="flex items-center gap-3 text-xs">
                            @if ($loan->isPending())
                                <form method="POST" action="{{ route('loans.mark-paid', $loan) }}">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="text-brand-600 hover:underline">{{ $loan->person_user_id ? 'Ya pagué' : 'Marcar pagado' }}</button>
                                </form>
                            @endif
                            <a href="{{ route('loans.edit', $loan) }}" class="text-gray-500 hover:underline dark:text-gray-400">Editar</a>
                            <form method="POST" action="{{ route('loans.destroy', $loan) }}" onsubmit="return confirm('¿Eliminar este préstamo?');">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-600 hover:underline">Eliminar</button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </x-card>
</x-app-layout>
