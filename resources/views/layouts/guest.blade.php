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

        @php
            // Fixed positions for the drifting spark dots in the brand
            // panel — hand-picked (not random) so they don't jump around
            // on every page load, just spread a bit unevenly like real
            // stars instead of a grid. [top%, left%, size px, delay s]
            $auroraSparks = [
                [12, 18, 3, 0], [22, 70, 2, 1.1], [34, 42, 2, 2.3], [48, 85, 3, 0.6],
                [58, 12, 2, 1.8], [66, 60, 3, 2.9], [76, 30, 2, 0.3], [84, 75, 2, 1.5],
                [18, 55, 2, 2.6], [90, 45, 3, 0.9],
            ];
        @endphp

        <div class="min-h-screen lg:flex">
            {{-- Brand panel — desktop only (the form needs the full width
                 on mobile). `lg:fixed` lets it extend past the width the
                 form panel reserves for it (`lg:ml-*` below), so the
                 jagged .auth-brand-panel edge can overlap and poke into
                 the form panel instead of just butting up against it.
                 Every color here — the gradient, the glow, the aurora
                 blobs, the logo mark itself — has a dark: variant, so
                 toggling the theme updates this whole panel instantly,
                 automatically, together with the rest of the page. --}}
            <div class="auth-brand-panel pointer-events-none fixed inset-y-0 left-0 z-10 hidden overflow-hidden lg:flex lg:w-[calc(42%+4rem)] xl:w-[calc(38%+4rem)] flex-col items-center justify-center gap-4 bg-gradient-to-br from-brand-100 via-brand-200 to-brand-300 p-12 dark:from-black dark:via-gray-950 dark:to-emerald-950">
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_30%_20%,theme(colors.brand.300/60%),transparent_60%)] dark:bg-[radial-gradient(circle_at_30%_20%,theme(colors.brand.500/25%),transparent_60%)]"></div>

                {{-- Aurora: three blurred, slow-drifting color blobs behind
                     the logo — a "northern lights" feel built with pure
                     CSS (see aurora-drift-* in app.css), no video/image
                     asset to ship. --}}
                <div class="aurora-blob aurora-blob-a -left-10 top-10 h-72 w-72 bg-brand-300/50 blur-3xl dark:bg-emerald-400/30"></div>
                <div class="aurora-blob aurora-blob-b -right-16 top-1/3 h-80 w-80 bg-emerald-200/50 blur-3xl dark:bg-teal-400/25"></div>
                <div class="aurora-blob aurora-blob-c bottom-0 left-1/4 h-64 w-64 bg-brand-400/40 blur-3xl dark:bg-brand-500/30"></div>

                @foreach ($auroraSparks as [$top, $left, $size, $delay])
                    <span
                        class="aurora-spark absolute rounded-full bg-brand-700/70 dark:bg-white/80"
                        style="top: {{ $top }}%; left: {{ $left }}%; width: {{ $size }}px; height: {{ $size }}px; animation-delay: {{ $delay }}s"
                    ></span>
                @endforeach

                <div class="relative z-10 flex max-w-sm flex-col items-center gap-3 text-center">
                    <img src="{{ asset('images/logo-dark.png') }}" alt="KashaFin" class="h-16 w-16 object-contain dark:hidden">
                    <img src="{{ asset('images/logo-light.png') }}" alt="KashaFin" class="hidden h-16 w-16 object-contain dark:block">
                    <h1 class="text-4xl font-extrabold">
                        <span class="text-brand-dark dark:text-white">Kasha</span><span class="text-brand-600 dark:text-brand-light">Fin</span>
                    </h1>
                    <p class="text-gray-600 dark:text-gray-400">Tu libertad financiera, hoy.</p>
                </div>
            </div>

            {{-- Form panel --}}
            <div class="flex min-h-screen flex-1 flex-col bg-white dark:bg-gray-900 lg:ml-[42%] xl:ml-[38%]">
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
