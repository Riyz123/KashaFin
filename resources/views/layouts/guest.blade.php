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
                writes to the same key.
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

        <div class="min-h-screen lg:flex">
            {{-- Brand panel — desktop only (the form needs the full width on
                 mobile). Its colors AND the logo mark itself swap with the
                 theme: both logo variants are always in the DOM, Tailwind's
                 dark: classes just toggle which one is visible, so the
                 change is instant and automatic the moment the <html> class
                 flips — no JS image-swapping logic needed. --}}
            <div class="relative hidden overflow-hidden lg:flex lg:w-[42%] xl:w-[38%] flex-col items-center justify-center gap-4 bg-gradient-to-br from-brand-50 via-brand-100 to-brand-200 p-12 dark:from-black dark:via-gray-950 dark:to-emerald-950">
                <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_30%_20%,theme(colors.brand.300/50%),transparent_60%)] dark:bg-[radial-gradient(circle_at_30%_20%,theme(colors.brand.500/25%),transparent_60%)]"></div>

                <div class="relative z-10 flex max-w-sm flex-col items-center gap-3 text-center">
                    <img src="{{ asset('images/logo-dark.png') }}" alt="KashaFin" class="h-16 w-16 object-contain dark:hidden">
                    <img src="{{ asset('images/logo-light.png') }}" alt="KashaFin" class="hidden h-16 w-16 object-contain dark:block">
                    <h1 class="text-4xl font-extrabold text-brand-dark dark:text-brand-light">KashaFin</h1>
                    <p class="text-gray-600 dark:text-gray-400">Planifica hoy, tu mañana cuenta.</p>
                </div>
            </div>

            {{-- Form panel --}}
            <div class="flex min-h-screen flex-1 flex-col bg-white dark:bg-gray-900">
                <div class="flex items-center justify-between px-6 py-4 lg:justify-end">
                    <a href="/" class="flex items-center gap-2 text-brand-dark dark:text-brand-light lg:hidden">
                        <img src="{{ asset('images/logo-dark.png') }}" alt="KashaFin" class="h-8 w-8 object-contain dark:hidden">
                        <img src="{{ asset('images/logo-light.png') }}" alt="KashaFin" class="hidden h-8 w-8 object-contain dark:block">
                        <span class="text-lg font-bold">KashaFin</span>
                    </a>

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
