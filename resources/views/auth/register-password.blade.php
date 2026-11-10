<x-guest-layout>
    <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Crea tu contraseña</h2>
    <p class="mt-1 mb-4 text-sm text-gray-600 dark:text-gray-400">
        Verificamos <strong>{{ $email }}</strong> y tu cuenta ya está activa. Te enviamos una contraseña temporal por
        correo por si acaso, pero puedes elegir la tuya aquí mismo.
    </p>

    <form method="POST" action="{{ route('register.password.store') }}">
        @csrf

        <div>
            <x-input-label for="password" value="Contraseña" />
            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autofocus autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password_confirmation" value="Confirma tu contraseña" />
            <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button>
                Crear cuenta
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
