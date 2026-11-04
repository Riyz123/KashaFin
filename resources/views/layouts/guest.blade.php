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
                writes to the same key. This only affects the FORM side of the
                page — the brand panel below is always the same dark theme on
                purpose, it doesn't follow this toggle.
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
            {{-- Brand panel — always visible now, on every breakpoint
                 (earlier drafts hid it below `lg` and showed a plain
                 compact logo bar instead; this one stacks a shorter
                 version of the real panel above the form on mobile).
                 `lg:fixed` on desktop lets it extend past the width the
                 form panel reserves for it (`lg:ml-*` below), so the
                 jagged .auth-brand-panel edge can overlap and poke into
                 the form panel instead of just butting up against it —
                 that trick only applies once it's actually fixed, so
                 mobile (plain flow) doesn't need or get it.

                 This panel is ALWAYS the same dark theme, independent of
                 the light/dark toggle in the form panel below/beside it
                 — a deliberate choice (the brand side is a fixed brand
                 moment, not a themed UI surface), not an oversight. --}}
            <div class="auth-brand-panel pointer-events-none relative flex h-[42vh] min-h-[320px] w-full flex-col items-center justify-center gap-4 overflow-hidden bg-gradient-to-br from-black via-gray-950 to-emerald-950 p-8 lg:fixed lg:inset-y-0 lg:left-0 lg:h-auto lg:min-h-0 lg:w-[calc(42%+4rem)] lg:p-12 xl:w-[calc(38%+4rem)]">
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_30%_20%,theme(colors.brand.500/40%),transparent_60%)]"></div>

                {{-- Aureolas: plain CSS blur blobs that float and rescale
                     on a slow loop (see .aurora-blob / aurora-float-* in
                     app.css) — no canvas, no WebGL, no library. --}}
                <div class="aurora-blob aurora-blob-a -left-16 top-4 h-56 w-56 bg-emerald-500/50 blur-[100px] lg:h-72 lg:w-72"></div>
                <div class="aurora-blob aurora-blob-b -right-12 top-1/3 h-52 w-52 bg-brand-400/45 blur-[100px] lg:h-64 lg:w-64"></div>
                <div class="aurora-blob aurora-blob-c -bottom-10 left-1/4 h-48 w-48 bg-teal-400/40 blur-[100px] lg:h-60 lg:w-60"></div>
                <div class="aurora-blob aurora-blob-d right-0 bottom-0 h-40 w-40 bg-emerald-300/35 blur-[100px] lg:h-52 lg:w-52"></div>

                {{-- The "data flow" dust: tsParticles (see
                     resources/js/aurora-particles.js, loaded via a
                     dynamic import in app.js so the library only
                     downloads on this page), dots drifting slowly upward
                     with faint connecting lines between nearby ones. --}}
                <div id="aurora-particles" class="absolute inset-0"></div>

                {{-- Faint candlestick chart in the background — fits a
                     finance app's branding. Static, just ambient texture. --}}
                <div class="pointer-events-none absolute right-6 top-6 flex items-end gap-1 opacity-20 lg:right-8 lg:top-10" style="height: 5rem">
                    @foreach ($auroraCandles as [$height, $isUp])
                        <span class="w-1 rounded-sm lg:w-1.5 {{ $isUp ? 'bg-emerald-400' : 'bg-brand-700' }}" style="height: {{ $height }}%"></span>
                    @endforeach
                </div>

                @foreach ($auroraSparks as [$top, $left, $size, $delay])
                    <span
                        class="aurora-spark absolute rounded-full bg-white shadow-[0_0_8px_rgba(255,255,255,0.9)]"
                        style="top: {{ $top }}%; left: {{ $left }}%; width: {{ $size }}px; height: {{ $size }}px; animation-delay: {{ $delay }}s"
                    ></span>
                @endforeach

                <div class="relative z-10 flex max-w-sm flex-col items-center gap-2 text-center lg:gap-3">
                    <img src="{{ asset('images/logo-light.png') }}" alt="KashaFin" class="h-12 w-12 object-contain lg:h-16 lg:w-16">
                    <h1 class="text-3xl font-extrabold lg:text-4xl">
                        <span class="text-white">Kasha</span><span class="text-brand-light">Fin</span>
                    </h1>
                    <p class="text-sm text-gray-400 lg:text-base">Tu libertad financiera, hoy.</p>
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
