@php
    $role = auth()->user()->app_role;
    $layoutType = $role->layout();
    $menu = "admin.$layoutType.navigation.menu";
@endphp

<flux:sidebar sticky stashable class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
    <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />

    <a href="{{ route('admin.dashboard') }}" class="me-5 flex items-center space-x-2 rtl:space-x-reverse" wire:navigate>
        <x-app-logo />
    </a>

    <x-dynamic-component :component="$menu" />

    <flux:spacer />

    <x-admin.ui.navigation.user-menu />
</flux:sidebar>
