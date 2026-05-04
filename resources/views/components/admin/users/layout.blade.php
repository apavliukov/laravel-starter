@use(App\Enums\Policies\Ability)
@use(App\Models\User)

@php /** @var User $user */ @endphp

@props([
    'mainHeading' => isset($user) ? $user->name : null,
    'hasTabs' => false,
    'tabHeading' => null,
    'tabSubheading' => null,
    'user' => null,
])

<x-admin.ui.layouts.main :$mainHeading :$tabHeading :$tabSubheading>
    <x-slot:headerButtons>
        @isset($user)
            @can(Ability::UPDATE->value, $user)
                <flux:button variant="primary" size="sm" icon="pencil"
                    :href="route('admin.platform.users.edit', $user)" wire:navigate>
                    {{ __('Edit') }}
                </flux:button>
            @endcan
        @else
            @can(Ability::CREATE->value, User::class)
                <flux:button variant="primary" size="sm" icon="plus"
                    :href="route('admin.platform.users.create')" wire:navigate>
                    {{ __('Add User') }}
                </flux:button>
            @endcan
        @endisset
    </x-slot:headerButtons>

    @if($hasTabs)
        <x-slot:tabs>
            <flux:navlist>
                <flux:navlist.item :href="route('admin.platform.users.edit', $user)"
                    icon="pencil" wire:navigate>
                    {{ __('Edit') }}
                </flux:navlist.item>
            </flux:navlist>
        </x-slot:tabs>
    @endif

    {{ $slot }}
</x-admin.ui.layouts.main>
