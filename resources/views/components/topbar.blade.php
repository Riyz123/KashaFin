@props(['title' => null])

<header class="sticky top-0 z-20 flex items-center justify-between gap-3 border-b border-gray-200 bg-white px-4 py-3 dark:border-gray-700 dark:bg-gray-800 sm:px-6">
    <div class="flex items-center gap-3 min-w-0">
        <button @click="open = true" class="rounded-md p-2 text-gray-500 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 md:hidden">
            <x-icon name="menu" class="w-6 h-6" />
        </button>
        <h1 class="truncate text-lg font-semibold text-gray-800 dark:text-gray-100">{{ $title ?? 'KashaFin' }}</h1>
    </div>

    <div class="flex items-center gap-2 sm:gap-4">
        <button
            type="button"
            x-data="{ dark: document.documentElement.classList.contains('dark') }"
            @click="
                dark = !dark;
                document.documentElement.classList.toggle('dark', dark);
                fetch('{{ route('settings.theme') }}', {
                    method: 'PATCH',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify({ theme: dark ? 'dark' : 'light' }),
                });
            "
            class="rounded-md p-2 text-gray-500 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700"
            title="Cambiar tema"
        >
            <x-icon x-show="!dark" name="sun" class="w-5 h-5" />
            <x-icon x-show="dark" name="moon" class="w-5 h-5" x-cloak />
        </button>

        <x-dropdown align="right" width="48">
            <x-slot name="trigger">
                <button class="flex items-center gap-2 rounded-full text-sm text-gray-700 dark:text-gray-200 focus:outline-none">
                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-brand-100 text-brand-800 dark:bg-brand-900 dark:text-brand-100 font-semibold">
                        {{ strtoupper(substr(auth()->user()->name ?? '?', 0, 1)) }}
                    </span>
                    <span class="hidden sm:inline">{{ auth()->user()->name }}</span>
                    <x-icon name="chevron-down" class="w-4 h-4 hidden sm:inline" />
                </button>
            </x-slot>
            <x-slot name="content">
                @if (auth()->user()->isAdmin())
                    <x-dropdown-link :href="route('admin.dashboard')">Panel de administración</x-dropdown-link>
                @endif
                <x-dropdown-link :href="route('profile.edit')">Mi perfil</x-dropdown-link>
                <x-dropdown-link :href="route('settings.edit')">Configuración</x-dropdown-link>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">
                        Cerrar sesión
                    </x-dropdown-link>
                </form>
            </x-slot>
        </x-dropdown>
    </div>
</header>
