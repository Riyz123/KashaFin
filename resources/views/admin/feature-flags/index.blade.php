@php
// Riesgo específico de cada sección al desactivarla — se muestra junto al
// interruptor para que la decisión se tome con el contexto delante, no a
// ciegas. Nada de esto borra datos: solo bloquea el acceso mientras está
// apagada (ver EnsureFeatureEnabled y FeatureFlag).
$risks = [
    'incomes' => 'El saldo actual y la proyección de liquidez seguirán contando los ingresos ya registrados — solo se bloquea agregar o ver nuevos.',
    'expenses' => 'Igual que ingresos: lo ya registrado sigue afectando el saldo. Si también desactivas "Préstamos", una amortización de deuda no podrá registrarse como gasto mientras tanto.',
    'budgets' => 'Los presupuestos ya definidos dejan de ser visibles para el estudiante, pero el cálculo de "% usado" sigue funcionando por dentro si vuelves a activarlo.',
    'savings_goals' => 'Las metas activas quedan en pausa: no se pueden crear nuevas ni aportar a las existentes hasta reactivarla.',
    'loans' => 'Si alguien tiene una confirmación o un pago/amortización pendiente de la otra persona, queda "congelado" hasta reactivar — ninguna de las dos partes podrá responder mientras tanto.',
    'shared_expenses' => 'Una invitación de gasto compartido sin responder queda igual de congelada — el invitado no podrá aceptar/rechazar hasta reactivarla.',
    'ai_chat' => 'Apaga TODO el chat/voz con IA, incluyendo sus acciones (agregar gasto, ingreso, préstamo, etc. por chat) — no solo una de ellas. El estudiante seguirá pudiendo usar los formularios normales de cada sección si están activas.',
];
@endphp

<x-admin-layout title="Apartados">
    <x-page-header title="Apartados del sistema" subtitle="Activa o desactiva secciones completas de la app para los estudiantes." />

    <x-card class="mb-6">
        <p class="text-sm text-gray-600 dark:text-gray-300">
            Desactivar una sección <strong>nunca borra información</strong>: lo que un estudiante ya tenga registrado
            ahí se queda guardado tal cual (incluida su trazabilidad en el log de actividad), solo que nadie puede
            verlo ni agregar nada nuevo hasta que la reactives. Mientras está apagada, el estudiante ve un aviso de
            "sección deshabilitada por el momento" en vez de la pantalla normal.
        </p>
    </x-card>

    <x-card>
        <div class="divide-y divide-gray-100 dark:divide-gray-700">
            @foreach ($flags as $flag)
                <div class="flex flex-col gap-3 py-4 sm:flex-row sm:items-start sm:justify-between">
                    <div class="flex-1">
                        <div class="flex items-center gap-2">
                            <p class="font-semibold text-gray-800 dark:text-gray-100">{{ $flag->label }}</p>
                            <span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $flag->is_enabled ? 'bg-brand-100 text-brand-800 dark:bg-brand-900/40 dark:text-brand-200' : 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300' }}">
                                {{ $flag->is_enabled ? 'Activo' : 'Deshabilitado' }}
                            </span>
                        </div>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $flag->description }}</p>
                        <p class="mt-1 text-xs text-amber-700 dark:text-amber-400">
                            <strong>Riesgo:</strong> {{ $risks[$flag->key] ?? 'Ninguno particular además de bloquear el acceso.' }}
                        </p>
                        @if (! $flag->is_enabled && $flag->disabled_at)
                            <p class="mt-1 text-xs text-gray-400">
                                Desactivado el {{ $flag->disabled_at->format('d/m/Y H:i') }}
                                @if ($flag->disabledBy) por {{ $flag->disabledBy->name }} @endif
                            </p>
                        @endif
                    </div>
                    <form method="POST" action="{{ route('admin.feature-flags.toggle', $flag) }}" onsubmit="return confirm('{{ $flag->is_enabled ? '¿Desactivar' : '¿Activar' }} \'{{ $flag->label }}\'?');">
                        @csrf @method('PATCH')
                        <button type="submit" class="rounded-md px-3 py-1.5 text-sm font-semibold {{ $flag->is_enabled ? 'bg-red-50 text-red-700 hover:bg-red-100 dark:bg-red-900/30 dark:text-red-300' : 'bg-brand-600 text-white hover:bg-brand-700' }}">
                            {{ $flag->is_enabled ? 'Desactivar' : 'Activar' }}
                        </button>
                    </form>
                </div>
            @endforeach
        </div>
    </x-card>
</x-admin-layout>
