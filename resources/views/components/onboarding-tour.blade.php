@php
$steps = [
    ['title' => '¡Bienvenido a KashaFin!', 'body' => 'Te ayudamos a llevar el control de tus finanzas como estudiante: ingresos, gastos, presupuesto, metas de ahorro y proyecciones, todo en un solo lugar.'],
    ['title' => 'Registra ingresos y gastos', 'body' => 'Desde "Ingresos" y "Gastos" puedes anotar cada movimiento. Los ingresos fijos (como una beca) se repiten solos cada periodo.'],
    ['title' => 'Presupuesto y metas', 'body' => 'En "Presupuesto" define un tope mensual por categoría, y en "Metas" crea objetivos de ahorro con fecha límite — verás tu progreso en todo momento.'],
    ['title' => 'Habla con el asistente', 'body' => 'Usa la burbuja de chat (abajo a la derecha) para pedir recomendaciones, registrar un gasto hablando, o escribir "hablar con un asesor" si necesitas ayuda humana.'],
];
@endphp

<div
    x-data="{ open: true, step: 0, total: {{ count($steps) }} }"
    x-show="open"
    x-cloak
    class="fixed inset-0 z-[60] flex items-center justify-center bg-black/50 px-4"
>
    <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl dark:bg-gray-800">
        @foreach ($steps as $index => $step)
            <div x-show="step === {{ $index }}">
                <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ $step['title'] }}</h3>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">{{ $step['body'] }}</p>
            </div>
        @endforeach

        <div class="mt-6 flex items-center justify-between">
            <div class="flex gap-1">
                @foreach ($steps as $index => $step)
                    <span class="h-1.5 w-6 rounded-full" :class="step === {{ $index }} ? 'bg-brand-600' : 'bg-gray-200 dark:bg-gray-600'"></span>
                @endforeach
            </div>
            <div class="flex items-center gap-3">
                <button
                    type="button"
                    @click="open = false; fetch('{{ route('settings.onboarding') }}', { method: 'PATCH', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } });"
                    class="text-sm text-gray-500 hover:underline dark:text-gray-400"
                >
                    Saltar
                </button>
                <button
                    type="button"
                    x-show="step < total - 1"
                    @click="step++"
                    class="rounded-md bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700"
                >
                    Siguiente
                </button>
                <button
                    type="button"
                    x-show="step === total - 1"
                    x-cloak
                    @click="open = false; fetch('{{ route('settings.onboarding') }}', { method: 'PATCH', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } });"
                    class="rounded-md bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700"
                >
                    Empezar
                </button>
            </div>
        </div>
    </div>
</div>
