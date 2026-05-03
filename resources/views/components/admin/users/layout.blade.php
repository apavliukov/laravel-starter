@php /** @var \App\Models\User $user */ @endphp

@props([
    'mainHeading' => isset($user) ? $user->name : null,
    'hasTabs' => false,
    'tabHeading' => null,
    'tabSubheading' => null,
    'user' => null,
])

<x-admin.ui.layouts.main :$mainHeading :$tabHeading :$tabSubheading>
    <x-slot:headerButtons>
        {{-- TODO Future button to create a user --}}
    </x-slot:headerButtons>

    @if($hasTabs)
        <x-slot:tabs>
            <flux:navlist>
                {{-- TODO Future routes: edit, etc. --}}
            </flux:navlist>
        </x-slot:tabs>
    @endif

    {{ $slot }}
</x-admin.ui.layouts.main>
