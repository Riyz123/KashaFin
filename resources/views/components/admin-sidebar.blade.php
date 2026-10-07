@php
$navItems = [
    ['route' => 'admin.dashboard', 'label' => 'Panel', 'icon' => 'home'],
    ['route' => 'admin.users.index', 'label' => 'Usuarios', 'icon' => 'user'],
    ['route' => 'admin.categories.index', 'label' => 'Categorías globales', 'icon' => 'wallet'],
];
@endphp

<aside
    x-cloak
    :class="open ? 'translate-x-0' : '-translate-x-full'"
    class="fixed inset-y-0 left-0 z-40 w-64 transform bg-gray-900 text-white transition-transform duration-200 ease-in-out md:static md:translate-x-0 md:shrink-0 flex flex-col"
>
    <div class="flex items-center gap-2 px-5 py-5">
        <x-application-logo class="w-9 h-9 text-brand-light" />
        <div>
            <p class="text-lg font-bold leading-tight">KashaFin</p>
            <p class="text-[11px] text-gray-400 leading-tight">Panel de administración</p>
        </div>
    </div>

    <nav class="flex-1 overflow-y-auto px-3 py-2 space-y-1">
        @foreach ($navItems as $item)
            <a
                href="{{ route($item['route']) }}"
                @click="open = false"
                class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition {{ request()->routeIs($item['route'])
                    ? 'bg-brand-600 text-white'
                    : 'text-gray-300 hover:bg-white/10 hover:text-white' }}"
            >
                <x-icon :name="$item['icon']" class="w-5 h-5 shrink-0" />
                {{ $item['label'] }}
            </a>
        @endforeach
    </nav>

    <div class="px-3 py-4 border-t border-white/10 space-y-1">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-gray-300 hover:bg-white/10 hover:text-white transition">
            <x-icon name="home" class="w-5 h-5" />
            Ver app de estudiante
        </a>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-gray-300 hover:bg-white/10 hover:text-white transition">
                <x-icon name="logout" class="w-5 h-5" />
                Cerrar sesión
            </button>
        </form>
    </div>
</aside>

<div x-show="open" x-cloak @click="open = false" x-transition.opacity class="fixed inset-0 z-30 bg-black/40 md:hidden"></div>
