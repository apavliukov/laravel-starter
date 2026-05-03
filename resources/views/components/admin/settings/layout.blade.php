@props([
    'tabHeading' => null,
    'tabSubheading' => null,
])

<x-ui.layouts.main
    :main-heading="__('Settings')"
    :main-subheading="__('Manage your profile and account settings')"
    :$tabHeading
    :$tabSubheading
>
    <x-slot:tabs>
        <flux:navlist>
            <flux:navlist.item :href="route('settings.profile')" :current="request()->routeIs('settings.profile')" icon="user-circle" wire:navigate>
                {{ __('Profile') }}
            </flux:navlist.item>
            <flux:navlist.item :href="route('settings.password')" :current="request()->routeIs('settings.password')" icon="lock-closed" wire:navigate>
                {{ __('Password') }}
            </flux:navlist.item>
            <flux:navlist.item :href="route('settings.appearance')" :current="request()->routeIs('settings.appearance')" icon="sun" wire:navigate>
                {{ __('Appearance') }}
            </flux:navlist.item>
        </flux:navlist>
    </x-slot:tabs>

    <div class="w-full max-w-lg">
        {{ $slot }}
    </div>
</x-ui.layouts.main>
