@use(AlexPavliukov\Authorization\Enums\Ability)
@use(App\Models\Organization)

@php /** @var Organization $organization */ @endphp

@props([
    'mainHeading' => isset($organization) ? $organization->name : null,
    'hasTabs' => false,
    'tabHeading' => null,
    'tabSubheading' => null,
    'organization' => null,
])

<x-admin.ui.layouts.main :$mainHeading :$tabHeading :$tabSubheading>
    <x-slot:headerButtons>
        @if (! $organization instanceof Organization)
            @can(Ability::CREATE, Organization::class)
                <flux:button
                    variant="primary"
                    size="sm"
                    icon="plus"
                    :href="route('admin.platform.organizations.create')"
                    wire:navigate
                >
                    {{ __('Add Organization') }}
                </flux:button>
            @endcan
        @endif
    </x-slot:headerButtons>

    @if ($hasTabs)
        <x-slot:tabs>
            <flux:navlist>
                <flux:navlist.item
                    :href="route('admin.platform.organizations.edit', $organization)"
                    icon="pencil"
                    wire:navigate
                >
                    {{ __('Edit') }}
                </flux:navlist.item>
            </flux:navlist>
        </x-slot:tabs>
    @endif

    {{ $slot }}
</x-admin.ui.layouts.main>
