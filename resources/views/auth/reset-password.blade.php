<x-guest-layout>
    <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">
        Código verificado. Elige tu nueva contraseña.
    </p>

    <form method="POST" action="{{ route('password.store') }}">
        @csrf

        <div>
            <x-input-label for="password" value="Nueva contraseña" />
            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="new-password" autofocus />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password_confirmation" value="Confirmar contraseña" />
            <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button>
                Cambiar contraseña
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
