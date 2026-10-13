@php
$faqs = [
    ['q' => '¿Cómo registro un gasto o un ingreso?', 'a' => 'Ve a "Gastos" o "Ingresos" en el menú y usa el botón "Nuevo", o simplemente dile al asistente del chat "agrega un gasto de 20 en comida" o "agrega un ingreso de mi beca".'],
    ['q' => '¿Cómo cambio mi contraseña?', 'a' => 'Entra a "Configuración" → "Perfil" y usa el formulario de "Actualizar contraseña".'],
    ['q' => '¿Qué hago si olvidé mi contraseña?', 'a' => 'Contacta a tu decano o al administrador para que te genere una contraseña temporal nueva, o usa el botón "Comunicarme con un asesor" de esta página.'],
    ['q' => '¿Cómo cambio el idioma del asistente?', 'a' => 'En "Configuración" puedes elegir español, inglés o quechua — el asistente siempre te responderá en ese idioma.'],
    ['q' => '¿Qué pasa si ninguna IA está disponible?', 'a' => 'El asistente sigue funcionando: te muestra un resumen de tus datos (saldo, gastos, presupuestos) sin IA, y puedes seguir registrando gastos/ingresos/presupuestos por chat de todas formas.'],
    ['q' => '¿Cómo reporto un problema con la app?', 'a' => 'Usa el formulario de abajo, o dile al asistente del chat "quiero hablar con un asesor" — en ambos casos, un asesor (tu decano o el administrador) recibirá tu mensaje y se comunicará contigo.'],
];
@endphp

<x-app-layout title="Ayuda y soporte">
    <x-page-header title="Ayuda y soporte" subtitle="Preguntas frecuentes, y cómo reportar un problema o hablar con un asesor." />

    <div class="grid gap-6 lg:grid-cols-2">
        <x-card>
            <h3 class="mb-3 text-sm font-semibold text-gray-700 dark:text-gray-200">Preguntas frecuentes</h3>
            <div x-data="{ openIndex: null }" class="divide-y divide-gray-100 dark:divide-gray-700">
                @foreach ($faqs as $index => $faq)
                    <div class="py-2">
                        <button
                            type="button"
                            @click="openIndex = openIndex === {{ $index }} ? null : {{ $index }}"
                            class="flex w-full items-center justify-between gap-2 text-left text-sm font-medium text-gray-800 dark:text-gray-100"
                        >
                            {{ $faq['q'] }}
                            <span class="shrink-0 transition" :class="openIndex === {{ $index }} ? 'rotate-180' : ''">
                                <x-icon name="chevron-down" class="w-4 h-4" />
                            </span>
                        </button>
                        <p x-show="openIndex === {{ $index }}" x-cloak x-transition class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                            {{ $faq['a'] }}
                        </p>
                    </div>
                @endforeach
            </div>
        </x-card>

        <div class="space-y-6">
            <x-card>
                <h3 class="mb-3 text-sm font-semibold text-gray-700 dark:text-gray-200">Reportar un problema (incidencia)</h3>
                <p class="mb-3 text-sm text-gray-500 dark:text-gray-400">
                    Tu decano o el administrador recibirá una notificación inmediata y se comunicará contigo.
                </p>
                <form method="POST" action="{{ route('help.store') }}" class="space-y-3">
                    @csrf
                    <div>
                        <x-input-label for="message" value="Describe el problema" />
                        <textarea id="message" name="message" rows="4" required class="mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">{{ old('message') }}</textarea>
                        <x-input-error :messages="$errors->get('message')" class="mt-2" />
                    </div>
                    <x-primary-button type="submit">Reportar incidencia</x-primary-button>
                </form>
            </x-card>

            <x-card>
                <h3 class="mb-3 text-sm font-semibold text-gray-700 dark:text-gray-200">Tus solicitudes recientes</h3>
                @if ($myRequests->isEmpty())
                    <x-empty-state message="No has enviado ninguna solicitud todavía." />
                @else
                    <div class="space-y-2">
                        @foreach ($myRequests as $request)
                            <div class="flex items-center justify-between gap-2 border-b border-gray-100 pb-2 text-sm last:border-0 dark:border-gray-700">
                                <div>
                                    <p class="text-gray-800 dark:text-gray-100">{{ $request->type === 'asesor' ? 'Hablar con asesor' : 'Incidencia' }}: {{ \Illuminate\Support\Str::limit($request->message, 60) }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $request->created_at->format('d/m/Y H:i') }}</p>
                                </div>
                                <span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $request->isPending() ? 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300' : 'bg-brand-100 text-brand-800 dark:bg-brand-900/40 dark:text-brand-200' }}">
                                    {{ $request->isPending() ? 'Pendiente' : 'Atendido' }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-card>
        </div>
    </div>
</x-app-layout>
