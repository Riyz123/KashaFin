<x-app-layout title="Configuración">
    <x-page-header title="Configuración general" subtitle="Personaliza moneda, tema, umbral de liquidez y más." />

    <x-card class="max-w-xl">
        <form method="POST" action="{{ route('settings.update') }}" class="space-y-5">
            @csrf
            @method('PATCH')

            <div>
                <x-input-label for="currency" value="Moneda" />
                <select id="currency" name="currency" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-brand-500 focus:ring-brand-500">
                    <option value="PEN" @selected($settings->currency === 'PEN')>Soles (S/)</option>
                    <option value="USD" @selected($settings->currency === 'USD')>Dólares ($)</option>
                </select>
                <x-input-error :messages="$errors->get('currency')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="theme" value="Tema" />
                <select id="theme" name="theme" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-brand-500 focus:ring-brand-500">
                    <option value="light" @selected($settings->theme === 'light')>Claro</option>
                    <option value="dark" @selected($settings->theme === 'dark')>Oscuro</option>
                </select>
                <x-input-error :messages="$errors->get('theme')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="language" value="Idioma del asistente" />
                <select id="language" name="language" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-brand-500 focus:ring-brand-500">
                    <option value="es" @selected($settings->language === 'es')>Español</option>
                    <option value="en" @selected($settings->language === 'en')>English</option>
                    <option value="qu" @selected($settings->language === 'qu')>Runasimi (quechua)</option>
                </select>
                <x-input-error :messages="$errors->get('language')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="week_start_day" value="Día de inicio de semana" />
                <select id="week_start_day" name="week_start_day" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-brand-500 focus:ring-brand-500">
                    <option value="0" @selected($settings->week_start_day === 0)>Domingo</option>
                    <option value="1" @selected($settings->week_start_day === 1)>Lunes</option>
                </select>
                <x-input-error :messages="$errors->get('week_start_day')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="liquidity_threshold" value="Umbral mínimo de liquidez" />
                <x-text-input id="liquidity_threshold" name="liquidity_threshold" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('liquidity_threshold', $settings->liquidity_threshold)" required />
                <x-input-error :messages="$errors->get('liquidity_threshold')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="starting_balance" value="Saldo inicial de referencia" />
                <x-text-input id="starting_balance" name="starting_balance" type="number" step="0.01" class="mt-1 block w-full" :value="old('starting_balance', $settings->starting_balance)" required />
                <x-input-error :messages="$errors->get('starting_balance')" class="mt-2" />
            </div>

            <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                <input type="checkbox" name="notify_low_liquidity_by_email" value="1" @checked($settings->notify_low_liquidity_by_email) class="rounded text-brand-600 focus:ring-brand-500">
                Enviarme alertas de liquidez por correo
            </label>

            <x-primary-button>Guardar configuración</x-primary-button>
        </form>
    </x-card>
</x-app-layout>
