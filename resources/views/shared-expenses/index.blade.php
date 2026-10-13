<x-app-layout title="Gastos compartidos">
    <x-page-header title="Gastos compartidos" subtitle="Gastos que pagaste tú pero se dividen con otras cuentas de KashaFin.">
        <a href="{{ route('shared-expenses.create') }}">
            <x-primary-button>
                <x-icon name="plus" class="w-4 h-4 mr-1" /> Nuevo gasto compartido
            </x-primary-button>
        </a>
    </x-page-header>

    @if ($pendingInvitations->isNotEmpty())
        <x-card class="mb-6">
            <h3 class="mb-3 font-semibold text-gray-800 dark:text-gray-100">Invitaciones pendientes</h3>
            <div class="space-y-3">
                @foreach ($pendingInvitations as $invite)
                    <div x-data="{ settingDaily: false }" class="rounded-lg border border-amber-200 bg-amber-50 p-3 dark:border-amber-800 dark:bg-amber-900/20">
                        <p class="text-sm text-gray-800 dark:text-gray-100">
                            <strong>{{ $invite->sharedExpense->user->name }}</strong> te agregó a
                            "<strong>{{ $invite->sharedExpense->description }}</strong>" — te corresponde
                            <strong>S/ {{ number_format($invite->amount_owed, 2) }}</strong>.
                        </p>

                        <form method="POST" action="{{ route('shared-expenses.accept', $invite) }}" class="mt-2 flex flex-wrap items-center gap-2">
                            @csrf @method('PATCH')
                            <label class="text-xs text-gray-600 dark:text-gray-300">
                                <input type="checkbox" x-model="settingDaily" class="rounded text-brand-600 focus:ring-brand-500">
                                Pagar en cuotas diarias
                            </label>
                            <input
                                x-show="settingDaily" x-cloak
                                type="number" step="0.01" min="0.01" name="daily_commitment"
                                value="{{ $invite->daily_commitment }}"
                                placeholder="S/ por día"
                                class="w-28 rounded-md border-gray-300 text-sm focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                            >
                            <x-primary-button type="submit">Aceptar</x-primary-button>
                        </form>
                        <form method="POST" action="{{ route('shared-expenses.reject', $invite) }}" class="mt-1 inline">
                            @csrf @method('PATCH')
                            <button type="submit" class="text-xs text-red-600 hover:underline">Rechazar</button>
                        </form>
                    </div>
                @endforeach
            </div>
        </x-card>
    @endif

    @if ($myCommitments->isNotEmpty())
        <x-card class="mb-6">
            <h3 class="mb-3 font-semibold text-gray-800 dark:text-gray-100">Lo que debo</h3>
            <div class="space-y-4">
                @foreach ($myCommitments as $commitment)
                    @php $timeline = $commitment->debtTimeline(); @endphp
                    <div x-data="{ paying: false, showDays: false }" class="rounded-lg border border-gray-100 p-3 dark:border-gray-700">
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <div>
                                <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">
                                    {{ $commitment->sharedExpense->description }}
                                    <span class="text-xs font-normal text-gray-500 dark:text-gray-400">de {{ $commitment->sharedExpense->user->name }}</span>
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    Pagado S/ {{ number_format($commitment->totalPaid(), 2) }} de S/ {{ number_format($commitment->amount_owed, 2) }}
                                    @if ($commitment->daily_commitment)
                                        &middot; S/ {{ number_format($commitment->daily_commitment, 2) }}/día
                                    @endif
                                </p>
                            </div>

                            @if ($commitment->isSettled())
                                <span class="rounded-full bg-brand-100 px-2 py-0.5 text-xs font-semibold text-brand-800 dark:bg-brand-900/40 dark:text-brand-200">Saldado</span>
                            @elseif ($timeline['shortfall'] > 0)
                                @php $daysAgo = $timeline['since'] ? max(0, $timeline['since']->diffInDays(today())) : null; @endphp
                                <span class="rounded-full bg-red-100 px-2 py-0.5 text-xs font-semibold text-red-700 dark:bg-red-900/40 dark:text-red-300">
                                    Debes S/ {{ number_format($timeline['shortfall'], 2) }}
                                    @if ($daysAgo === 0)
                                        desde hoy
                                    @elseif ($daysAgo !== null)
                                        desde hace {{ $daysAgo }} día(s)
                                    @endif
                                </span>
                            @else
                                <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-semibold text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                    Faltan S/ {{ number_format($commitment->remaining(), 2) }}
                                </span>
                            @endif
                        </div>

                        @if (! empty($timeline['days']))
                            <button @click="showDays = !showDays" class="mt-2 text-xs text-brand-600 hover:underline">
                                <span x-show="!showDays">Ver días con deuda ({{ count($timeline['days']) }})</span>
                                <span x-show="showDays" x-cloak>Ocultar días con deuda</span>
                            </button>
                            <div x-show="showDays" x-cloak class="mt-2 space-y-1">
                                @foreach ($timeline['days'] as $day)
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $day['date']->format('d/m/Y') }}: faltaron S/ {{ number_format($day['shortfall'], 2) }}
                                        (esperado S/ {{ number_format($day['expected'], 2) }}, pagado S/ {{ number_format($day['paid'], 2) }})
                                    </p>
                                @endforeach
                            </div>
                        @endif

                        @unless ($commitment->isSettled())
                            <button @click="paying = !paying" class="mt-2 text-xs font-semibold text-brand-600 hover:underline">
                                + Registrar un pago
                            </button>
                            <form x-show="paying" x-cloak method="POST" action="{{ route('shared-expenses.contributions.store', $commitment) }}" class="mt-2 flex flex-wrap items-end gap-2">
                                @csrf
                                <div>
                                    <label class="text-xs text-gray-500 dark:text-gray-400">Monto</label>
                                    <input type="number" step="0.01" min="0.01" name="amount" required class="mt-0.5 w-28 rounded-md border-gray-300 text-sm focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                                </div>
                                <div>
                                    <label class="text-xs text-gray-500 dark:text-gray-400">Fecha</label>
                                    <input type="date" name="date" value="{{ now()->toDateString() }}" required class="mt-0.5 rounded-md border-gray-300 text-sm focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                                </div>
                                <x-primary-button type="submit">Guardar</x-primary-button>
                            </form>
                        @endunless
                    </div>
                @endforeach
            </div>
        </x-card>
    @endif

    <x-card class="mb-6 max-w-xs">
        <p class="text-sm text-gray-500 dark:text-gray-400">Te deben en total (aceptado, pendiente de pago)</p>
        <p class="text-2xl font-bold text-brand-600">S/ {{ number_format($totalOwedToMe, 2) }}</p>
    </x-card>

    <h3 class="mb-3 font-semibold text-gray-800 dark:text-gray-100">Gastos que creaste</h3>

    <x-card class="mb-4">
        <form method="GET" action="{{ route('shared-expenses.index') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 items-end">
            <div class="lg:col-span-2">
                <x-input-label for="search" value="Descripción" />
                <x-text-input id="search" name="search" type="text" class="mt-1 block w-full" placeholder="Buscar por descripción..." :value="$search" />
            </div>
            <div>
                <x-input-label for="from" value="Desde" />
                <x-text-input id="from" name="from" type="date" class="mt-1 block w-full" :value="$from" />
            </div>
            <div>
                <x-input-label for="to" value="Hasta" />
                <x-text-input id="to" name="to" type="date" class="mt-1 block w-full" :value="$to" />
            </div>
            <div class="lg:col-span-4 flex items-center gap-4">
                <x-primary-button>Filtrar</x-primary-button>
                <a href="{{ route('shared-expenses.index') }}" class="text-sm text-gray-500 hover:underline dark:text-gray-400">Limpiar filtros</a>
                <span class="text-xs text-gray-400">Ordenar:
                    <a href="{{ request()->fullUrlWithQuery(['sort' => 'date', 'dir' => request('sort') === 'date' && request('dir') !== 'desc' ? 'desc' : 'asc']) }}" class="text-brand-600 hover:underline">Fecha</a>
                    &middot;
                    <a href="{{ request()->fullUrlWithQuery(['sort' => 'total_amount', 'dir' => request('sort') === 'total_amount' && request('dir') !== 'desc' ? 'desc' : 'asc']) }}" class="text-brand-600 hover:underline">Monto total</a>
                </span>
            </div>
        </form>
    </x-card>

    @if ($sharedExpenses->isEmpty())
        <x-empty-state message="No se encontraron gastos compartidos con esos filtros." />
    @else
        <div class="space-y-4">
            @foreach ($sharedExpenses as $shared)
                <x-card>
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="font-semibold text-gray-800 dark:text-gray-100">{{ $shared->description }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $shared->date->format('d/m/Y') }} &middot; Total S/ {{ number_format($shared->total_amount, 2) }}
                                &middot; Tu parte S/ {{ number_format($shared->my_share, 2) }}
                                @if ($shared->category) &middot; {{ $shared->category->name }} @endif
                            </p>
                        </div>
                        <form method="POST" action="{{ route('shared-expenses.destroy', $shared) }}" onsubmit="return confirm('¿Eliminar este gasto compartido? También se eliminará tu parte registrada como gasto.');">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-xs text-red-600 hover:underline">Eliminar</button>
                        </form>
                    </div>

                    <div class="mt-3 divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach ($shared->participants as $participant)
                            <div class="flex items-center justify-between gap-2 py-2 text-sm">
                                <span class="text-gray-700 dark:text-gray-300">{{ $participant->user?->name ?? $participant->name }}</span>
                                <div class="flex items-center gap-2">
                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                        S/ {{ number_format($participant->totalPaid(), 2) }} / {{ number_format($participant->amount_owed, 2) }}
                                    </span>
                                    @if ($participant->isPending())
                                        <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-800 dark:bg-amber-900/40 dark:text-amber-200">Invitación pendiente</span>
                                    @elseif ($participant->isRejected())
                                        <span class="rounded-full bg-red-100 px-2 py-0.5 text-xs font-semibold text-red-700 dark:bg-red-900/40 dark:text-red-300">Rechazó</span>
                                    @elseif ($participant->isSettled())
                                        <span class="rounded-full bg-brand-100 px-2 py-0.5 text-xs font-semibold text-brand-800 dark:bg-brand-900/40 dark:text-brand-200">Pagado</span>
                                    @else
                                        <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-semibold text-gray-600 dark:bg-gray-700 dark:text-gray-300">Pendiente de pago</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </x-card>
            @endforeach
        </div>
    @endif
</x-app-layout>
