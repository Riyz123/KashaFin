<x-admin-layout title="Asistente IA">
    <x-page-header title="Proveedores de IA" subtitle="Se usan en orden de prioridad: cuando uno se agota, el sistema sigue con el siguiente." />

    <x-card class="mb-6">
        <h3 class="mb-3 font-semibold text-gray-800 dark:text-gray-100">Prompt del asistente</h3>
        <p class="mb-3 text-xs text-gray-500 dark:text-gray-400">
            Esto define la personalidad y el tono del asistente. El manejo de idiomas (español/inglés/quechua) y las
            herramientas para registrar gastos, presupuestos e ingresos siguen funcionando sin importar este texto.
        </p>
        <form method="POST" action="{{ route('admin.ai.prompt.update') }}">
            @csrf @method('PUT')
            <textarea name="system_prompt" rows="5" class="block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-brand-500 focus:ring-brand-500" placeholder="{{ \App\Models\AiSetting::DEFAULT_PROMPT }}">{{ old('system_prompt', $aiSetting->system_prompt) }}</textarea>
            <x-input-error :messages="$errors->get('system_prompt')" class="mt-1" />
            <x-primary-button class="mt-3">Guardar prompt</x-primary-button>
        </form>
        <form method="POST" action="{{ route('admin.ai.prompt.reset') }}" class="mt-2" onsubmit="return confirm('¿Restaurar el prompt por defecto?');">
            @csrf @method('DELETE')
            <button type="submit" class="text-sm text-gray-500 hover:underline dark:text-gray-400">Restaurar por defecto</button>
        </form>
    </x-card>

    <x-card class="mb-6">
        <h3 class="mb-3 font-semibold text-gray-800 dark:text-gray-100">Agregar proveedor</h3>
        <form method="POST" action="{{ route('admin.ai.store') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @csrf
            <div>
                <x-input-label for="name" value="Nombre" />
                <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" placeholder="Groq - Llama 3.1" required />
            </div>
            <div>
                <x-input-label for="driver" value="Tipo" />
                <select id="driver" name="driver" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-brand-500 focus:ring-brand-500" required>
                    <option value="openai_compatible">Compatible con OpenAI (Groq, OpenRouter, etc.)</option>
                    <option value="gemini">Google Gemini</option>
                </select>
            </div>
            <div>
                <x-input-label for="base_url" value="URL base" />
                <x-text-input id="base_url" name="base_url" type="text" class="mt-1 block w-full" placeholder="https://api.groq.com/openai/v1" required />
            </div>
            <div>
                <x-input-label for="model" value="Modelo" />
                <x-text-input id="model" name="model" type="text" class="mt-1 block w-full" placeholder="llama-3.1-8b-instant" required />
            </div>
            <div class="sm:col-span-2">
                <x-input-label for="api_key" value="API key" />
                <x-text-input id="api_key" name="api_key" type="password" class="mt-1 block w-full" required />
            </div>
            <div>
                <x-input-label for="priority" value="Prioridad (menor = primero)" />
                <x-text-input id="priority" name="priority" type="number" min="0" class="mt-1 block w-full" value="0" required />
            </div>
            <div>
                <x-input-label for="quota_limit" value="Límite de solicitudes (opcional)" />
                <x-text-input id="quota_limit" name="quota_limit" type="number" min="1" class="mt-1 block w-full" placeholder="Ej: 14400" />
            </div>
            <div>
                <x-input-label for="quota_period_days" value="Cada cuántos días se reinicia" />
                <x-text-input id="quota_period_days" name="quota_period_days" type="number" min="1" class="mt-1 block w-full" value="1" required />
            </div>
            <div class="sm:col-span-2 lg:col-span-4">
                <x-input-error :messages="$errors->get('name')" class="mt-1" />
                <x-input-error :messages="$errors->get('base_url')" class="mt-1" />
                <x-input-error :messages="$errors->get('api_key')" class="mt-1" />
                <x-primary-button class="mt-2">Agregar proveedor</x-primary-button>
            </div>
        </form>
    </x-card>

    @if ($providers->isEmpty())
        <x-empty-state message="Todavía no configuraste ningún proveedor de IA. El chatbot responderá solo con el resumen de datos (sin IA) hasta que agregues uno." />
    @else
        <div class="space-y-4" x-data="aiTrafficPoll('{{ route('admin.ai.status') }}')">
            @foreach ($providers as $provider)
                <x-card x-data="{ editing: false }">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="font-semibold text-gray-800 dark:text-gray-100">{{ $provider->name }}</h3>
                                <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                    {{ $provider->driver === 'gemini' ? 'Gemini' : 'Compatible OpenAI' }}
                                </span>
                                <span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $provider->is_active ? 'bg-brand-100 text-brand-800 dark:bg-brand-900/40 dark:text-brand-200' : 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300' }}">
                                    {{ $provider->is_active ? 'Activo' : 'Inactivo' }}
                                </span>
                                @if ($provider->is_exhausted)
                                    <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-800 dark:bg-amber-900/40 dark:text-amber-200">Agotado</span>
                                @endif
                            </div>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                Modelo: {{ $provider->model }} &middot; Prioridad: {{ $provider->priority }} &middot; Key: {{ $provider->masked_api_key }}
                            </p>
                            @if ($provider->latestUsageLog)
                                <p class="mt-1 text-xs {{ $provider->latestUsageLog->status === 'success' ? 'text-gray-500 dark:text-gray-400' : 'text-red-600 dark:text-red-400' }}">
                                    Último uso ({{ $provider->latestUsageLog->created_at->format('d/m/Y H:i') }}):
                                    {{ match ($provider->latestUsageLog->status) {
                                        'success' => 'exitoso',
                                        'quota_exceeded' => 'cuota agotada',
                                        default => 'error',
                                    } }}
                                    @if ($provider->latestUsageLog->error_message)
                                        &middot; {{ $provider->latestUsageLog->error_message }}
                                    @endif
                                </p>
                            @else
                                <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">Todavía no se ha usado.</p>
                            @endif
                        </div>

                        <div class="w-full sm:w-56 rounded-lg border border-gray-100 p-3 dark:border-gray-700">
                            <div class="flex items-center justify-between">
                                <p class="text-xs font-semibold text-gray-600 dark:text-gray-300">Tráfico en tiempo real</p>
                                <span x-show="!loaded" class="text-xs text-gray-400">Cargando…</span>
                            </div>

                            <template x-if="loaded && trafficFor({{ $provider->id }})">
                                <div>
                                    <span class="mt-1 inline-flex items-center gap-1.5 text-xs font-semibold"
                                          :class="trafficFor({{ $provider->id }}).is_preferred ? 'text-brand-600 dark:text-brand-400' : (trafficFor({{ $provider->id }}).available ? 'text-gray-500 dark:text-gray-400' : 'text-red-600 dark:text-red-400')">
                                        <span class="h-2 w-2 rounded-full"
                                              :class="trafficFor({{ $provider->id }}).is_preferred ? 'bg-brand-500 animate-pulse' : (trafficFor({{ $provider->id }}).available ? 'bg-gray-400' : 'bg-red-500')"></span>
                                        <span x-text="trafficFor({{ $provider->id }}).is_preferred ? 'Se usaría ahora' : (trafficFor({{ $provider->id }}).available ? 'Disponible (en espera)' : (trafficFor({{ $provider->id }}).is_active ? 'No disponible' : 'Inactivo'))"></span>
                                    </span>

                                    <ul class="mt-2 space-y-1 max-h-28 overflow-y-auto">
                                        <template x-if="trafficFor({{ $provider->id }}).recent_logs.length === 0">
                                            <li class="text-xs text-gray-400 dark:text-gray-500">Sin llamadas registradas todavía.</li>
                                        </template>
                                        <template x-for="log in trafficFor({{ $provider->id }}).recent_logs" :key="log.at">
                                            <li class="flex items-start gap-1.5 text-xs">
                                                <span class="mt-0.5 h-1.5 w-1.5 flex-shrink-0 rounded-full"
                                                      :class="{ 'bg-brand-500': log.status === 'success', 'bg-amber-500': log.status === 'quota_exceeded', 'bg-red-500': log.status === 'error' }"></span>
                                                <span>
                                                    <span class="text-gray-400 dark:text-gray-500" x-text="new Date(log.at).toLocaleTimeString('es-PE', { hour: '2-digit', minute: '2-digit', second: '2-digit' })"></span>
                                                    <span :class="{ 'text-gray-600 dark:text-gray-300': log.status === 'success', 'text-amber-600 dark:text-amber-400': log.status === 'quota_exceeded', 'text-red-600 dark:text-red-400': log.status === 'error' }"
                                                          x-text="log.status === 'success' ? 'exitoso' : (log.status === 'quota_exceeded' ? 'cuota agotada' : 'error')"></span>
                                                </span>
                                            </li>
                                        </template>
                                    </ul>
                                </div>
                            </template>
                        </div>

                        <div class="flex flex-wrap gap-3 text-sm">
                            <button @click="editing = !editing" class="text-brand-600 hover:underline">Editar</button>
                            <form method="POST" action="{{ route('admin.ai.test', $provider) }}">
                                @csrf
                                <button type="submit" class="text-brand-600 hover:underline">Probar conexión</button>
                            </form>
                            @if ($provider->is_exhausted)
                                <form method="POST" action="{{ route('admin.ai.reactivate', $provider) }}" onsubmit="return confirm('¿Quitar el estado \'agotado\' y reiniciar su contador de uso?');">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="text-brand-600 hover:underline">Reactivar</button>
                                </form>
                            @endif
                            <form method="POST" action="{{ route('admin.ai.toggle-active', $provider) }}">
                                @csrf @method('PATCH')
                                <button type="submit" class="text-brand-600 hover:underline">{{ $provider->is_active ? 'Desactivar' : 'Activar' }}</button>
                            </form>
                            <form method="POST" action="{{ route('admin.ai.destroy', $provider) }}" onsubmit="return confirm('¿Eliminar este proveedor?');">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-600 hover:underline">Eliminar</button>
                            </form>
                        </div>
                    </div>

                    <div class="mt-3">
                        @if ($provider->quota_limit)
                            <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 mb-1">
                                <span>Uso: {{ $provider->requests_used }} / {{ $provider->quota_limit }} solicitudes ({{ $provider->usage_percent }}%)</span>
                                @if ($provider->period_reset_at)
                                    <span>Se reinicia: {{ $provider->period_reset_at->format('d/m/Y H:i') }}</span>
                                @endif
                            </div>
                            <x-progress-bar :percent="$provider->usage_percent" />
                        @else
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $provider->requests_used }} solicitudes realizadas &middot; sin límite configurado</p>
                        @endif
                    </div>

                    <form x-show="editing" x-cloak method="POST" action="{{ route('admin.ai.update', $provider) }}" class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 border-t border-gray-100 dark:border-gray-700 pt-4">
                        @csrf @method('PUT')
                        <div>
                            <x-input-label value="Nombre" />
                            <x-text-input name="name" type="text" class="mt-1 block w-full" :value="$provider->name" required />
                        </div>
                        <div>
                            <x-input-label value="Tipo" />
                            <select name="driver" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-brand-500 focus:ring-brand-500" required>
                                <option value="openai_compatible" @selected($provider->driver === 'openai_compatible')>Compatible con OpenAI</option>
                                <option value="gemini" @selected($provider->driver === 'gemini')>Google Gemini</option>
                            </select>
                        </div>
                        <div>
                            <x-input-label value="URL base" />
                            <x-text-input name="base_url" type="text" class="mt-1 block w-full" :value="$provider->base_url" required />
                        </div>
                        <div>
                            <x-input-label value="Modelo" />
                            <x-text-input name="model" type="text" class="mt-1 block w-full" :value="$provider->model" required />
                        </div>
                        <div class="sm:col-span-2">
                            <x-input-label value="API key (dejar en blanco para no cambiar)" />
                            <x-text-input name="api_key" type="password" class="mt-1 block w-full" placeholder="••••••••" />
                        </div>
                        <div>
                            <x-input-label value="Prioridad" />
                            <x-text-input name="priority" type="number" min="0" class="mt-1 block w-full" :value="$provider->priority" required />
                        </div>
                        <div>
                            <x-input-label value="Límite de solicitudes" />
                            <x-text-input name="quota_limit" type="number" min="1" class="mt-1 block w-full" :value="$provider->quota_limit" />
                        </div>
                        <div>
                            <x-input-label value="Días por periodo" />
                            <x-text-input name="quota_period_days" type="number" min="1" class="mt-1 block w-full" :value="$provider->quota_period_days" required />
                        </div>
                        <div class="sm:col-span-2 lg:col-span-4">
                            <x-primary-button>Guardar cambios</x-primary-button>
                        </div>
                    </form>
                </x-card>
            @endforeach
        </div>
    @endif
</x-admin-layout>
