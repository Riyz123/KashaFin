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
            {{-- Brand panel — always visible, on every breakpoint (a
                 shorter version of the real panel stacks above the form
                 on mobile, not a plain compact logo bar). `lg:fixed` on
                 desktop lets it extend past the 55% width the form panel
                 reserves for it (`lg:ml-*` below), so the jagged
                 .auth-brand-panel edge can overlap and poke into the
                 form panel instead of just butting up against it.

                 Back to following the site's light/dark toggle like the
                 rest of the page (an earlier draft pinned this panel
                 permanently dark; both logo files and both background
                 tones are back in use here, swapped by theme). --}}
            <div class="auth-brand-panel pointer-events-none relative flex h-[46vh] min-h-[340px] w-full flex-col items-center justify-center gap-5 overflow-hidden bg-gradient-to-br from-white via-brand-100 to-brand-300 p-8 dark:from-black dark:via-gray-950 dark:to-[#031a10] lg:fixed lg:inset-y-0 lg:left-0 lg:h-auto lg:min-h-0 lg:w-[calc(55%+4rem)] lg:p-16">
                {{-- Aureolas: 3 giant CSS blur blobs (no canvas, no
                     library — see .aurora-blob / aurora-float-* in
                     app.css), drifting and rescaling on a slow loop to
                     read as slow-moving smoke/northern lights. Neon
                     green/mint + mix-blend-screen in dark (overlapping
                     light brightens into itself); deeper, more saturated
                     tones + mix-blend-multiply in light (screen on a
                     pale background would just bleach it white). --}}
                <div class="aurora-blob aurora-blob-a -left-32 -top-20 h-[380px] w-[380px] bg-emerald-400 mix-blend-multiply blur-[140px] dark:bg-[#00ff88] dark:mix-blend-screen lg:h-[600px] lg:w-[600px]"></div>
                <div class="aurora-blob aurora-blob-b -right-24 top-1/4 h-[340px] w-[340px] bg-teal-400 mix-blend-multiply blur-[140px] dark:bg-[#00cc66] dark:mix-blend-screen lg:h-[600px] lg:w-[600px]"></div>
                <div class="aurora-blob aurora-blob-c -bottom-24 left-1/4 h-[320px] w-[320px] bg-brand-500 mix-blend-multiply blur-[140px] dark:bg-[#00ff88] dark:mix-blend-screen lg:h-[600px] lg:w-[600px]"></div>

                {{-- "Estrellas": tiny white dots at scattered opacity —
                     see $auroraSparks above. --}}
                @foreach ($auroraSparks as [$top, $left, $size, $delay])
                    <span
                        class="aurora-spark absolute rounded-full bg-brand-800 shadow-[0_0_6px_rgba(6,78,59,0.5)] dark:bg-white dark:shadow-[0_0_8px_rgba(255,255,255,0.9)]"
                        style="top: {{ $top }}%; left: {{ $left }}%; width: {{ $size }}px; height: {{ $size }}px; animation-delay: {{ $delay }}s"
                    ></span>
                @endforeach

                {{-- Ecualizador / gráfico financiero de fondo — barras
                     verticales muy tenues en la esquina superior derecha. --}}
                <div class="pointer-events-none absolute right-6 top-6 flex items-end gap-1 opacity-10 dark:opacity-20 lg:right-10 lg:top-12" style="height: 6rem">
                    @foreach ($auroraCandles as [$height, $isUp])
                        <span class="w-1 rounded-sm lg:w-1.5 {{ $isUp ? 'bg-emerald-500 dark:bg-emerald-400' : 'bg-brand-700' }}" style="height: {{ $height }}%"></span>
                    @endforeach
                </div>

                {{-- drop-shadow (not a plain Tailwind color, since the
                     giant blobs drift behind this text and can get
                     bright enough right here to eat the contrast of
                     whatever's on top of them) keeps the wordmark and
                     tagline readable at every point in the loop, not
                     just the frame these screenshots happened to land
                     on. --}}
                <div class="relative z-10 flex max-w-lg flex-col items-center gap-3 text-center drop-shadow-[0_2px_8px_rgba(0,0,0,0.55)] lg:gap-5">
                    <img src="{{ asset('images/logo-dark.png') }}" alt="KashaFin" class="h-20 w-20 object-contain dark:hidden lg:h-32 lg:w-32">
                    <img src="{{ asset('images/logo-light.png') }}" alt="KashaFin" class="hidden h-20 w-20 object-contain dark:block lg:h-32 lg:w-32">
                    <h1 class="text-5xl font-bold lg:text-7xl">
                        <span class="text-brand-dark dark:text-white">Kasha</span><span class="text-brand-600 dark:text-brand-light">Fin</span>
                    </h1>
                    <p class="text-lg text-gray-600 dark:text-gray-300 lg:text-2xl">Tu libertad financiera, hoy.</p>
                </div>
            </div>

            {{-- Form panel --}}
            <div class="flex min-h-screen flex-1 flex-col bg-white dark:bg-[#0f172a] lg:ml-[55%]">
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
