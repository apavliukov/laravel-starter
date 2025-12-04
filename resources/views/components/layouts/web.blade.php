<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head.web')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:main>
            {{ $slot }}
        </flux:main>

        @vite(['resources/js/web/web.js'])
        @fluxScripts
        @livewireScriptConfig
    </body>
</html>
