<x-app-layout title="Sección deshabilitada">
    <div class="flex min-h-[60vh] items-center justify-center">
        <div class="max-w-md text-center opacity-60">
            <x-icon name="lock" class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" />
            <h2 class="mt-4 text-lg font-semibold text-gray-700 dark:text-gray-200">{{ $message }}</h2>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                Si ya tenías información registrada aquí, sigue guardada tal cual — no se pierde ni se borra,
                solo queda en espera hasta que un administrador vuelva a activar esta sección.
            </p>
            <a href="{{ route('dashboard') }}" class="mt-5 inline-block text-sm font-semibold text-brand-600 hover:underline">
                Volver al inicio
            </a>
        </div>
    </div>
</x-app-layout>
