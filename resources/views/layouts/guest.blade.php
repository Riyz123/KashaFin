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
                [70, 90, 2, 0.5], [95, 20, 2, 2.0], [5, 35, 2, 0.8], [28, 92, 2, 1.7],
                [44, 25, 3, 2.4], [52, 65, 2, 0.4], [62, 38, 2, 1.9], [78, 55, 3, 0.2],
                [88, 15, 2, 2.7], [15, 48, 2, 1.3], [97, 60, 2, 0.7], [36, 78, 2, 2.2],
            ];

            // Faint candlestick-chart bars in the background, like the
            // reference video has — an upward trend, fitting "libertad
            // financiera". [height %, is the bar an "up" candle]
            $auroraCandles = [
                [25, true], [40, false], [22, true], [55, true], [35, false], [65, true],
                [48, false], [75, true], [58, false], [85, true], [68, false], [55, true],
                [80, true], [62, false], [92, true], [72, false],
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
            <div class="auth-brand-panel pointer-events-none fixed inset-y-0 left-0 z-10 hidden overflow-hidden lg:flex lg:w-[calc(42%+4rem)] xl:w-[calc(38%+4rem)] flex-col items-center justify-center gap-4 bg-gradient-to-br from-white via-brand-100 to-brand-400 p-12 dark:from-black dark:via-gray-950 dark:to-emerald-950">
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_30%_20%,theme(colors.white/80%),transparent_55%)] dark:bg-[radial-gradient(circle_at_30%_20%,theme(colors.brand.500/40%),transparent_60%)]"></div>

                {{-- Aurora: four wide, heavily-blurred, slow-drifting color
                     bands behind the logo (see aurora-drift-* in app.css).
                     Wide + short + rotated reads as a drifting ribbon of
                     light instead of a lava-lamp bubble; the heavy blur
                     (more than Tailwind's built-in blur-3xl gives) is what
                     keeps it soft and misty even at this size instead of a
                     solid-edged disc. Blend mode flips with the theme:
                     screen in dark brightens overlapping colors into each
                     other, multiply in light deepens them into pools of
                     green instead of washing the panel one flat tint. --}}
                <div class="aurora-blob aurora-blob-a -left-24 top-10 h-40 w-[34rem] -rotate-[18deg] bg-brand-400/70 blur-[150px] mix-blend-multiply dark:bg-emerald-400/50 dark:mix-blend-screen"></div>
                <div class="aurora-blob aurora-blob-b -right-24 top-1/4 h-36 w-[30rem] rotate-[22deg] bg-emerald-400/60 blur-[150px] mix-blend-multiply dark:bg-teal-400/45 dark:mix-blend-screen"></div>
                <div class="aurora-blob aurora-blob-c -bottom-16 left-0 h-44 w-[32rem] rotate-[8deg] bg-brand-500/55 blur-[150px] mix-blend-multiply dark:bg-brand-500/45 dark:mix-blend-screen"></div>
                <div class="aurora-blob aurora-blob-d right-0 top-0 h-32 w-[24rem] -rotate-[10deg] bg-teal-300/55 blur-[130px] mix-blend-multiply dark:bg-cyan-400/35 dark:mix-blend-screen"></div>

                {{-- The actual moving aurora: simplex-noise-driven ribbons
                     drawn on a <canvas>, not a fixed CSS/SVG keyframe —
                     see resources/js/aurora-canvas.js (registerAuroraCanvas,
                     wired up in app.js on turbo:load). Noise-driven motion
                     never repeats on a visible cycle the way a hand-tuned
                     wave animation does, each ribbon moves independently,
                     and it resizes itself to the panel on every resize —
                     genuinely responsive, not just "looks fine at common
                     widths". It reads the current theme (light/dark) and
                     prefers-reduced-motion itself, so no dark: classes are
                     needed here. --}}
                <canvas id="aurora-canvas" class="pointer-events-none absolute inset-0 h-full w-full" aria-hidden="true"></canvas>

                {{-- Faint candlestick chart in the background — fits a
                     finance app's branding. Static, just ambient texture. --}}
                <div class="pointer-events-none absolute right-8 top-10 flex items-end gap-1 opacity-[0.15] dark:opacity-20" style="height: 9rem">
                    @foreach ($auroraCandles as [$height, $isUp])
                        <span class="w-1.5 rounded-sm {{ $isUp ? 'bg-emerald-400' : 'bg-brand-700' }}" style="height: {{ $height }}%"></span>
                    @endforeach
                </div>

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
