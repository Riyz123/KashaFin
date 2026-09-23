@php
$navItems = [
    ['route' => 'dashboard', 'label' => 'Inicio', 'icon' => 'home'],
    ['route' => 'incomes.index', 'label' => 'Ingresos', 'icon' => 'income'],
    ['route' => 'expenses.index', 'label' => 'Gastos', 'icon' => 'expense'],
    ['route' => 'budgets.index', 'label' => 'Presupuesto', 'icon' => 'wallet'],
    ['route' => 'projections.index', 'label' => 'Proyecciones', 'icon' => 'chart'],
    ['route' => 'goals.index', 'label' => 'Metas', 'icon' => 'flag'],
    ['route' => 'reports.index', 'label' => 'Reportes', 'icon' => 'document'],
    ['route' => 'history.index', 'label' => 'Historial', 'icon' => 'search'],
    ['route' => 'notifications.index', 'label' => 'Notificaciones', 'icon' => 'bell'],
    ['route' => 'settings.edit', 'label' => 'Configuración', 'icon' => 'cog'],
];
@endphp

<aside
    x-cloak
    :class="open ? 'translate-x-0' : '-translate-x-full'"
    class="fixed inset-y-0 left-0 z-40 w-64 transform bg-brand-dark text-white transition-transform duration-200 ease-in-out md:static md:translate-x-0 md:shrink-0 flex flex-col"
>
    <div class="flex items-center gap-2 px-5 py-5">
        <x-application-logo class="w-9 h-9 text-brand-light" />
        <div>
            <p class="text-lg font-bold leading-tight">KashaFin</p>
            <p class="text-[11px] text-brand-light/80 leading-tight">Planifica hoy, tu mañana cuenta</p>
        </div>
    </div>

    <nav class="flex-1 overflow-y-auto px-3 py-2 space-y-1">
        @foreach ($navItems as $item)
            <a
                href="{{ route($item['route']) }}"
                @click="open = false"
                class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition {{ request()->routeIs($item['route']) || request()->routeIs(str_replace('.index', '.*', $item['route']))
                    ? 'bg-brand-600 text-white'
                    : 'text-brand-light/90 hover:bg-white/10 hover:text-white' }}"
            >
                <x-icon :name="$item['icon']" class="w-5 h-5 shrink-0" />
                {{ $item['label'] }}
            </a>
        @endforeach
    </nav>

    <div class="px-3 py-4 border-t border-white/10">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-brand-light/90 hover:bg-white/10 hover:text-white transition">
                <x-icon name="logout" class="w-5 h-5" />
                Cerrar sesión
            </button>
        </form>
    </div>
</aside>

<div x-show="open" x-cloak @click="open = false" x-transition.opacity class="fixed inset-0 z-30 bg-black/40 md:hidden"></div>
