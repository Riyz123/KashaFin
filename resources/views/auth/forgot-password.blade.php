<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600 dark:text-gray-400">
        ¿Olvidaste tu contraseña? Escribe tu correo y te mandamos un código de 6 dígitos para recuperar el acceso.
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <div>
            <x-input-label for="email" value="Correo" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="flex items-center justify-between mt-4">
            <a class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100" href="{{ route('login') }}">
                Volver a iniciar sesión
            </a>

            <x-primary-button>
                Enviar código
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
