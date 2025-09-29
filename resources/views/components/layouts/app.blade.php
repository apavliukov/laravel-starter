<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <x-common.ui.navigation.layouts.sidebar />

        <flux:main>
            {{ $slot }}
        </flux:main>

        <x-common.ui.toast />

        @vite(['resources/js/main.js'])
        @fluxScripts
    </body>
</html>
