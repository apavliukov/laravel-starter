@props([
    'tabHeading' => null,
    'tabSubheading' => null,
])

<x-common.ui.tabs.layout
    :main-heading="__('Settings')"
    :main-subheading="__('Manage your profile and account settings')"
    :$tabHeading
    :$tabSubheading
>
    <x-slot:tabs>
        <flux:navlist>
            <flux:navlist.item :href="route('admin.settings.profile')" icon="user-circle" wire:navigate>{{ __('Profile') }}</flux:navlist.item>
            <flux:navlist.item :href="route('admin.settings.password')" icon="lock-closed" wire:navigate>{{ __('Password') }}</flux:navlist.item>
            <flux:navlist.item :href="route('admin.settings.appearance')" icon="sun" wire:navigate>{{ __('Appearance') }}</flux:navlist.item>
        </flux:navlist>
    </x-slot:tabs>

    <div class="w-full max-w-lg">
        {{ $slot }}
    </div>
</x-common.ui.tabs.layout>
