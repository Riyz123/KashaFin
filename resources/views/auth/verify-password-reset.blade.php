<x-guest-layout>
    <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">
        Si <strong>{{ $email }}</strong> tiene una cuenta, le enviamos un código de 6 dígitos. Revisa tu bandeja (y
        spam) e ingrésalo aquí para continuar.
    </p>

    @if (session('status'))
        <p class="mb-4 text-sm font-medium text-brand-600">{{ session('status') }}</p>
    @endif

    <form method="POST" action="{{ route('password.verify.submit') }}">
        @csrf

        <div>
            <x-input-label for="code" value="Código de verificación" />
            <x-text-input
                id="code"
                class="block mt-1 w-full text-center text-2xl tracking-[0.5em]"
                type="text"
                inputmode="numeric"
                pattern="[0-9]*"
                maxlength="6"
                name="code"
                required
                autofocus
            />
            <x-input-error :messages="$errors->get('code')" class="mt-2" />
        </div>

        <div class="flex items-center justify-between mt-4">
            <a class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100" href="{{ route('password.request') }}">
                Usar otro correo
            </a>

            <x-primary-button>
                Verificar código
            </x-primary-button>
        </div>
    </form>

    <form method="POST" action="{{ route('password.verify.resend') }}" class="mt-4">
        @csrf
        <button type="submit" class="text-sm text-gray-500 hover:underline dark:text-gray-400">
            ¿No te llegó? Reenviar código
        </button>
    </form>
</x-guest-layout>
