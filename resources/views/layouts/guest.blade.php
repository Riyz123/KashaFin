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
        {{--
            Unlike layouts/app.blade.php and layouts/admin.blade.php, this
            layout opts OUT of Turbo Drive's soft (fetch-based) navigation
            entirely — `turbo-visit-control: reload` makes every visit TO
            a guest-layout page (tapping "¿Olvidaste tu contraseña?",
            "Regístrate aquí", browser back/forward, anything) a plain
            full browser navigation instead. Found the hard way: on the
            real InfinityFree deployment (reproducible on production,
            Chrome, not reproducible against local dev in any browser
            tested here), tapping between login/register/forgot-password
            sometimes left a blank white page that only a manual reload
            fixed. That's the signature of Turbo's own safety check —
            when a soft-fetched page's <head> doesn't byte-for-byte match
            what Turbo expected (stale HTML from InfinityFree's or the
            phone's own caching serving an old build's asset hashes
            instead of the current one), Turbo aborts the soft swap and
            is supposed to fall back to a reload on its own, but with
            InfinityFree's caching stacked on top that fallback can still
            land on another stale/inconsistent response. A plain reload
            on every visit sidesteps that whole class of problem instead
            of depending on Turbo's cache-matching logic working — at the
            cost of the instant SPA-like transition, which these specific
            pages don't need anyway (nobody's rapidly hopping between
            login and register).
        --}}
        <meta name="turbo-visit-control" content="reload">

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

        <div class="relative min-h-screen lg:flex">
            {{-- Brand panel. On mobile it's `absolute inset-0` against this
                 OUTER `relative` wrapper — not `fixed` — so it stretches to
                 the wrapper's actual full height, which grows with however
                 tall the page's real content is (register's form is much
                 taller than login's, forgot-password's shorter still).
                 `fixed` was the earlier choice here, and it's exactly what
                 caused the bug where switching between login/register/
                 forgot-password on mobile looked broken: a `fixed` element
                 only ever covers ONE viewport's worth of height, so on any
                 page taller than that the glow just stopped partway down
                 and the plain body background showed below it — a visible
                 seam, different on every page depending on its height,
                 which is exactly what reads as "se buguea" when flipping
                 between these three. `absolute` has no such limit; it
                 simply matches its positioned ancestor's real height,
                 covering the whole page every time, however long that is.

                 On desktop (`lg:`) it goes back to `fixed` — there the
                 panel is a column next to a form that scrolls on its own,
                 and staying pinned while that happens is the whole point.
                 Follows the site's light/dark toggle, like the rest of
                 the page — both logo files and both background tones
                 swap by theme. --}}
            <div class="auth-brand-panel pointer-events-none absolute inset-0 z-0 overflow-hidden bg-gradient-to-br from-white via-brand-100 to-brand-400 dark:from-black dark:via-gray-950 dark:to-emerald-950 lg:fixed lg:inset-y-0 lg:left-0 lg:right-auto lg:flex lg:w-[calc(42%+4rem)] lg:items-center lg:justify-center lg:p-12 xl:w-[calc(38%+4rem)]">
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_30%_20%,theme(colors.white/80%),transparent_55%)] dark:bg-[radial-gradient(circle_at_30%_20%,theme(colors.brand.500/40%),transparent_60%)]"></div>

                {{-- A few twinkling dots — see $auroraSparks above. --}}
                @foreach ($auroraSparks as [$top, $left, $size, $delay])
                    <span
                        class="aurora-spark absolute rounded-full bg-brand-700/80 shadow-[0_0_6px_rgba(21,128,61,0.6)] dark:bg-white dark:shadow-[0_0_8px_rgba(255,255,255,0.9)]"
                        style="top: {{ $top }}%; left: {{ $left }}%; width: {{ $size }}px; height: {{ $size }}px; animation-delay: {{ $delay }}s"
                    ></span>
                @endforeach

                {{-- Desktop-only: the big centered logo/title/tagline,
                     since this panel is its own side column there and has
                     the room to center something in. On mobile the panel
                     is just the full-screen glow background — the logo
                     lives in the form panel's own normal document flow
                     instead (below), so it can never overlap the card the
                     way centering both independently in the same fixed
                     viewport used to. --}}
                <div class="relative z-10 hidden max-w-sm flex-col items-center gap-4 text-center lg:flex">
                    <img src="{{ asset('images/logo-dark.png') }}" alt="KashaFin" class="h-24 w-24 object-contain dark:hidden">
                    <img src="{{ asset('images/logo-light.png') }}" alt="KashaFin" class="hidden h-24 w-24 object-contain dark:block">
                    <h1 class="text-5xl font-extrabold">
                        <span class="text-brand-dark dark:text-white">Kasha</span><span class="text-brand-600 dark:text-brand-light">Fin</span>
                    </h1>
                    <p class="text-lg text-gray-600 dark:text-gray-400">Tu libertad financiera, hoy.</p>
                </div>
            </div>

            {{-- Form panel. On mobile this is a transparent overlay sitting
                 ON TOP of the full-screen brand panel (`z-10`) — the logo
                 and the card both live in its own normal flow (next),
                 stacked one after another instead of each independently
                 centered in the full viewport, which is what overlapped
                 them before. On desktop it goes back to being an opaque
                 side-by-side column (lg:bg-white/lg:dark:bg-gray-900 on
                 the outer div), and the mobile logo block/card framing
                 both disappear since the desktop brand panel already has
                 its own centered logo and the form needs no frame there. --}}
            <div class="relative z-10 flex min-h-screen flex-1 flex-col lg:bg-white lg:dark:bg-gray-900 lg:ml-[42%] xl:ml-[38%]">
                <div class="flex items-center justify-end px-6 py-4">
                    <button
                        type="button"
                        x-data="{ dark: document.documentElement.classList.contains('dark') }"
                        @click="dark = !dark; window.toggleGuestThemeAnimated(dark, $event)"
                        class="rounded-md bg-white/70 p-2 text-gray-700 backdrop-blur-sm hover:bg-white dark:bg-gray-900/60 dark:text-gray-200 dark:hover:bg-gray-900/80 lg:bg-transparent lg:text-gray-500 lg:backdrop-blur-none lg:hover:bg-gray-100 lg:dark:bg-transparent lg:dark:text-gray-300 lg:dark:hover:bg-gray-700"
                        title="Cambiar tema"
                        aria-label="Cambiar entre tema claro y oscuro"
                    >
                        <x-icon x-show="!dark" name="sun" class="w-5 h-5" />
                        <x-icon x-show="dark" name="moon" class="w-5 h-5" x-cloak />
                    </button>
                </div>

                <div class="flex flex-1 flex-col items-center justify-center gap-6 px-6 pb-12">
                    {{-- Mobile-only logo block, in normal flow right above
                         the card — see the comment above for why this
                         isn't just reused from inside the fixed panel. --}}
                    <div class="flex flex-col items-center gap-2 text-center lg:hidden">
                        <img src="{{ asset('images/logo-dark.png') }}" alt="KashaFin" class="h-16 w-16 object-contain dark:hidden">
                        <img src="{{ asset('images/logo-light.png') }}" alt="KashaFin" class="hidden h-16 w-16 object-contain dark:block">
                        <h1 class="text-3xl font-extrabold">
                            <span class="text-brand-dark dark:text-white">Kasha</span><span class="text-brand-600 dark:text-brand-light">Fin</span>
                        </h1>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Tu libertad financiera, hoy.</p>
                    </div>

                    {{-- The "frame": a white/dark card on mobile (where it's
                         floating over the full-bleed glow), reset back to
                         plain unboxed content on desktop (where the form
                         panel itself already has that solid background). --}}
                    <div class="w-full max-w-md rounded-2xl bg-white/95 p-6 shadow-2xl backdrop-blur-sm dark:bg-gray-900/95 sm:p-8 lg:rounded-none lg:bg-transparent lg:p-0 lg:shadow-none lg:backdrop-blur-none lg:dark:bg-transparent">
                        {{ $slot }}
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
