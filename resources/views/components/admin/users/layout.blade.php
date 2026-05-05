@use(App\Enums\Policies\Abilities\Ability)
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
        @if(! $user instanceof User)
            @can(Ability::CREATE, User::class)
                <flux:button variant="primary" size="sm" icon="plus"
                             :href="route('admin.platform.users.create')" wire:navigate>
                    {{ __('Add User') }}
                </flux:button>
            @endcan
        @endif
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
