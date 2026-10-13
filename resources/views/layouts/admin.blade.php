<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="{{ ($userSettings->theme ?? 'light') === 'dark' ? 'dark' : '' }} {{ $userSettings && $userSettings->font_size !== 'normal' ? 'text-scale-'.$userSettings->font_size : '' }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'KashaFin') }} &middot; Admin @isset($title) &middot; {{ $title }} @endisset</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-gray-100 dark:bg-gray-900 text-gray-900 dark:text-gray-100">
        <a href="#main-content" class="sr-only-focusable fixed left-2 top-2 z-[100] rounded-md bg-brand-600 px-4 py-2 text-sm font-semibold text-white">Saltar al contenido</a>

        <div x-data="{ open: false }" class="flex min-h-screen">
            <x-admin-sidebar />

            <div class="flex min-w-0 flex-1 flex-col">
                <x-topbar :title="$title ?? null" />

                @if (session('status'))
                    <div class="mx-4 mt-4 rounded-lg bg-brand-100 px-4 py-3 text-sm text-brand-800 dark:bg-brand-900/40 dark:text-brand-200 sm:mx-6" role="alert">
                        {{ session('status') }}
                    </div>
                @endif

                <main id="main-content" tabindex="-1" class="flex-1 px-4 py-6 sm:px-6 lg:px-8 focus:outline-none">
                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>
