@props([
    'tabHeading',
    'tabSubheading' => null,
])

@php
    $mainHeading = __('Settings');
    $pageTitle = "$mainHeading - $tabHeading";
@endphp

<x-admin.ui.layouts.main
    :page-title="$pageTitle"
    :main-heading="$mainHeading"
    :main-subheading="__('Manage your profile and account settings')"
    :$tabHeading
    :$tabSubheading
>
    <x-slot:tabs>
        <flux:navlist>
            <flux:navlist.item :href="route('admin.settings.profile')" :current="request()->routeIs('admin.settings.profile')" icon="user-circle" wire:navigate>
                {{ __('Profile') }}
            </flux:navlist.item>
            <flux:navlist.item :href="route('admin.settings.password')" :current="request()->routeIs('admin.settings.password')" icon="lock-closed" wire:navigate>
                {{ __('Password') }}
            </flux:navlist.item>
            <flux:navlist.item :href="route('admin.settings.appearance')" :current="request()->routeIs('admin.settings.appearance')" icon="sun" wire:navigate>
                {{ __('Appearance') }}
            </flux:navlist.item>
        </flux:navlist>
    </x-slot:tabs>

    <div class="w-full max-w-lg">
        {{ $slot }}
    </div>
</x-admin.ui.layouts.main>
