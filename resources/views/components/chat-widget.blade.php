<div x-data="chatWidget()" x-init="init()" class="fixed bottom-4 right-4 z-50">
    <script type="application/json" id="chat-history">{!! \Illuminate\Support\Js::encode($recentChatMessages->map(fn ($m) => ['role' => $m->role, 'content' => $m->content])->values()) !!}</script>
    <script>window.chatSendUrl = @js(route('chat.send'));</script>

    <button
        @click="open = !open"
        class="flex h-14 w-14 items-center justify-center rounded-full bg-brand-600 text-white shadow-lg hover:bg-brand-700 transition"
        aria-label="Abrir asistente"
    >
        <x-icon name="chat" class="w-7 h-7" x-show="!open" />
        <x-icon name="close" class="w-7 h-7" x-show="open" x-cloak />
    </button>

    <div
        x-show="open"
        x-cloak
        x-transition
        class="absolute bottom-16 right-0 flex h-[28rem] w-80 flex-col overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xl dark:border-gray-700 dark:bg-gray-800 sm:w-96"
    >
        <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3 dark:border-gray-700">
            <div>
                <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">Asistente KashaFin</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">Pregúntame, o di &quot;agrega un gasto&quot;, &quot;un ingreso&quot; o &quot;un presupuesto&quot;</p>
            </div>
            <button @click="speechEnabled = !speechEnabled" title="Leer respuestas en voz alta" class="text-gray-400 hover:text-brand-600">
                <x-icon name="speaker" class="w-5 h-5" x-show="speechEnabled" />
                <x-icon name="speaker-off" class="w-5 h-5" x-show="!speechEnabled" x-cloak />
            </button>
        </div>

        <div x-ref="scrollArea" class="flex-1 space-y-3 overflow-y-auto px-4 py-3">
            <template x-if="messages.length === 0">
                <p class="text-sm text-gray-500 dark:text-gray-400">¡Hola! Puedo darte recomendaciones, análisis de tus finanzas, o ayudarte a registrar un gasto, un ingreso o un presupuesto. Prueba escribiendo "agrega un gasto", "agrega un ingreso" o "crea un presupuesto".</p>
            </template>
            <template x-for="(msg, index) in messages" :key="index">
                <div :class="msg.role === 'user' ? 'text-right' : 'text-left'">
                    <span
                        class="inline-block max-w-[85%] whitespace-pre-line rounded-lg px-3 py-2 text-sm"
                        :class="msg.role === 'user' ? 'bg-brand-600 text-white' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-100'"
                        x-text="msg.content"
                    ></span>
                </div>
            </template>
            <p x-show="loading" x-cloak class="text-xs text-gray-400">Pensando...</p>
        </div>

        <form @submit.prevent="send()" class="flex items-center gap-2 border-t border-gray-100 px-3 py-2 dark:border-gray-700">
            <button
                type="button"
                @click="toggleListening()"
                title="Hablar"
                :class="listening ? 'text-red-600' : 'text-gray-400 hover:text-brand-600'"
            >
                <x-icon name="microphone" class="w-5 h-5" />
            </button>
            <input
                type="text"
                x-model="draft"
                placeholder="Escribe o habla..."
                class="flex-1 rounded-md border-gray-300 text-sm focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
            >
            <button type="submit" class="text-brand-600 hover:text-brand-700">
                <x-icon name="send" class="w-5 h-5" />
            </button>
        </form>
    </div>
</div>
