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
                [12, 18, 3, 0], [22, 70, 3, 1.1], [34, 42, 2, 2.3], [48, 85, 3, 0.6],
                [58, 12, 3, 1.8], [66, 60, 4, 2.9], [76, 30, 2, 0.3], [84, 75, 3, 1.5],
                [18, 55, 2, 2.6], [90, 45, 3, 0.9], [8, 88, 2, 1.4], [40, 8, 3, 2.1],
                [70, 90, 2, 0.5], [95, 20, 2, 2.0],
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
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_30%_20%,theme(colors.brand.300/75%),transparent_60%)] dark:bg-[radial-gradient(circle_at_30%_20%,theme(colors.brand.500/40%),transparent_60%)]"></div>

                {{-- Aurora: four wide, heavily-blurred, slow-drifting/
                     recoloring color bands behind the logo — a "northern
                     lights" feel built with pure CSS (see aurora-drift-*
                     in app.css), no video/image asset to ship. Wide +
                     short + rotated reads as a drifting ribbon of light
                     instead of a lava-lamp bubble; the heavy blur (more
                     than Tailwind's built-in blur-3xl gives) is what
                     keeps it soft and misty even at this size instead of
                     a solid-edged disc. Screen blend in dark mode is what
                     makes overlapping colors brighten into each other
                     instead of just looking like flat tinted shapes; it's
                     dark:-only because on the light panel it would wash
                     everything out to white instead. --}}
                <div class="aurora-blob aurora-blob-a -left-24 top-10 h-40 w-[34rem] -rotate-[18deg] bg-brand-300/60 blur-[150px] dark:bg-emerald-400/50 dark:mix-blend-screen"></div>
                <div class="aurora-blob aurora-blob-b -right-24 top-1/4 h-36 w-[30rem] rotate-[22deg] bg-emerald-300/55 blur-[150px] dark:bg-teal-400/45 dark:mix-blend-screen"></div>
                <div class="aurora-blob aurora-blob-c -bottom-16 left-0 h-44 w-[32rem] rotate-[8deg] bg-brand-400/50 blur-[150px] dark:bg-brand-500/45 dark:mix-blend-screen"></div>
                <div class="aurora-blob aurora-blob-d right-0 top-0 h-32 w-[24rem] -rotate-[10deg] bg-emerald-200/45 blur-[130px] dark:bg-cyan-400/35 dark:mix-blend-screen"></div>

                {{-- Curtains: three tall bands that sway and have their
                     own bright streak sliding up and down inside them —
                     this is the part that actually reads as "real aurora
                     movement" rather than a glow drifting around (see
                     .aurora-curtain / aurora-sway-* / aurora-flow in
                     app.css). Dark-mode only, that's where it belongs. --}}
                <div class="aurora-curtain aurora-curtain-1 hidden left-[8%] text-emerald-400 opacity-40 blur-xl mix-blend-screen dark:block"></div>
                <div class="aurora-curtain aurora-curtain-2 hidden left-[38%] text-teal-300 opacity-35 blur-xl mix-blend-screen dark:block"></div>
                <div class="aurora-curtain aurora-curtain-3 hidden left-[64%] text-cyan-300 opacity-30 blur-xl mix-blend-screen dark:block"></div>

                @foreach ($auroraSparks as [$top, $left, $size, $delay])
                    <span
                        class="aurora-spark absolute rounded-full bg-brand-700/80 shadow-[0_0_6px_rgba(21,128,61,0.6)] dark:bg-white dark:shadow-[0_0_8px_rgba(255,255,255,0.9)]"
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
