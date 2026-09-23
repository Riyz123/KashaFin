<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="{{ (auth()->user()?->settings?->theme ?? 'light') === 'dark' ? 'dark' : '' }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'KashaFin') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="min-h-screen flex flex-col sm:justify-center items-center py-8 px-4 bg-brand-surface dark:bg-gray-900">
            <div class="flex flex-col items-center gap-2 text-center">
                <a href="/" class="flex items-center gap-2 text-brand-dark dark:text-brand-light">
                    <x-application-logo class="w-12 h-12" />
                    <span class="text-2xl font-bold">KashaFin</span>
                </a>
                <p class="text-sm text-gray-500 dark:text-gray-400">Planifica hoy, tu mañana cuenta.</p>
            </div>

            <div class="w-full sm:max-w-md mt-6 px-6 py-6 bg-white dark:bg-gray-800 shadow-md overflow-hidden sm:rounded-xl">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
