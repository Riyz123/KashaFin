<x-guest-layout>
    <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">
        Iniciaste sesión con una contraseña temporal. Antes de continuar, elige una contraseña propia.
    </p>

    <form method="POST" action="{{ route('password.force-update') }}">
        @csrf
        @method('PUT')

        <div>
            <x-input-label for="password" value="Nueva contraseña" />
            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autofocus autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password_confirmation" value="Confirmar contraseña" />
            <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button>Guardar y continuar</x-primary-button>
        </div>
    </form>
</x-guest-layout>
