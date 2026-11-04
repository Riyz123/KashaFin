<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="{{ auth()->check() && (auth()->user()->settings?->theme === 'dark') ? 'dark' : '' }}">
    <head>
        <meta charset="utf-8">

        @guest
            {{--
                Guests have no saved theme to render server-side (auth()->user()
                is null above), so this runs before anything paints and applies
                whatever was remembered in localStorage from a previous visit —
                or the OS preference the first time — avoiding a flash of the
                wrong theme. toggleGuestThemeAnimated() in theme-toggle.js
                writes to the same key. Both the form AND the brand panel
                react to this — the whole page shares one theme.
            --}}
            <script>
                (function () {
                    try {
                        var stored = localStorage.getItem('kashafin-guest-theme');
                        var prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                        if (stored === 'dark' || (stored === null && prefersDark)) {
                            document.documentElement.classList.add('dark');
                        }
                    } catch (e) {}
                })();
            </script>
        @endguest

        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="view-transition" content="same-origin">

        <title>{{ config('app.name', 'KashaFin') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div id="turbo-spinner" class="turbo-loading-spinner" aria-hidden="true"></div>

        @php
            // Fixed positions for a few twinkling dots in the brand
            // panel — hand-picked (not random) so they don't jump around
            // on every page load, just spread a bit unevenly like real
            // stars instead of a grid. [top%, left%, size px, delay s]
            $auroraSparks = [
                [12, 18, 3, 0], [22, 70, 3, 1.1], [58, 12, 3, 1.8], [66, 60, 4, 2.9],
                [76, 30, 2, 0.3], [84, 75, 3, 1.5], [18, 55, 2, 2.6], [90, 45, 3, 0.9],
                [40, 8, 3, 2.1], [70, 90, 2, 0.5], [44, 25, 3, 2.4], [15, 48, 2, 1.3],
            ];
        @endphp

        <div class="min-h-screen lg:flex">
            {{-- Brand panel — a shorter version stacks above the form on
                 mobile, side-by-side on desktop. `lg:fixed` on desktop
                 lets it extend past the 42% width the form panel
                 reserves for it (`lg:ml-*` below), so the jagged
                 .auth-brand-panel edge can overlap and poke into the
                 form panel instead of just butting up against it.
                 Follows the site's light/dark toggle, like the rest of
                 the page — both logo files and both background tones
                 swap by theme. --}}
            <div class="auth-brand-panel pointer-events-none relative flex h-[42vh] min-h-[320px] w-full flex-col items-center justify-center gap-4 overflow-hidden bg-gradient-to-br from-white via-brand-100 to-brand-400 p-8 dark:from-black dark:via-gray-950 dark:to-emerald-950 lg:fixed lg:inset-y-0 lg:left-0 lg:h-auto lg:min-h-0 lg:w-[calc(42%+4rem)] lg:p-12 xl:w-[calc(38%+4rem)]">
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_30%_20%,theme(colors.white/80%),transparent_55%)] dark:bg-[radial-gradient(circle_at_30%_20%,theme(colors.brand.500/40%),transparent_60%)]"></div>

                {{-- A few twinkling dots — see $auroraSparks above. --}}
                @foreach ($auroraSparks as [$top, $left, $size, $delay])
                    <span
                        class="aurora-spark absolute rounded-full bg-brand-700/80 shadow-[0_0_6px_rgba(21,128,61,0.6)] dark:bg-white dark:shadow-[0_0_8px_rgba(255,255,255,0.9)]"
                        style="top: {{ $top }}%; left: {{ $left }}%; width: {{ $size }}px; height: {{ $size }}px; animation-delay: {{ $delay }}s"
                    ></span>
                @endforeach

                <div class="relative z-10 flex max-w-sm flex-col items-center gap-4 text-center">
                    <img src="{{ asset('images/logo-dark.png') }}" alt="KashaFin" class="h-24 w-24 object-contain dark:hidden">
                    <img src="{{ asset('images/logo-light.png') }}" alt="KashaFin" class="hidden h-24 w-24 object-contain dark:block">
                    <h1 class="text-5xl font-extrabold">
                        <span class="text-brand-dark dark:text-white">Kasha</span><span class="text-brand-600 dark:text-brand-light">Fin</span>
                    </h1>
                    <p class="text-lg text-gray-600 dark:text-gray-400">Tu libertad financiera, hoy.</p>
                </div>
            </div>

            {{-- Form panel --}}
            <div class="flex min-h-screen flex-1 flex-col bg-white dark:bg-gray-900 lg:ml-[42%] xl:ml-[38%]">
                <div class="flex items-center justify-end px-6 py-4">
                    <button
                        type="button"
                        x-data="{ dark: document.documentElement.classList.contains('dark') }"
                        @click="dark = !dark; window.toggleGuestThemeAnimated(dark, $event)"
                        class="rounded-md p-2 text-gray-500 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700"
                        title="Cambiar tema"
                        aria-label="Cambiar entre tema claro y oscuro"
                    >
                        <x-icon x-show="!dark" name="sun" class="w-5 h-5" />
                        <x-icon x-show="dark" name="moon" class="w-5 h-5" x-cloak />
                    </button>
                </div>

                <div class="flex flex-1 items-center justify-center px-6 pb-12">
                    <div class="w-full max-w-md">
                        {{ $slot }}
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
