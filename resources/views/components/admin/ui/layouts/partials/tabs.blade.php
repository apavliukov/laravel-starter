@props([
    'tabs',
    'tabHeading' => null,
    'tabSubheading' => null,
])

<div class="flex items-start max-md:flex-col">
    <div class="me:6 md:me-8 w-full pb-4 md:w-55">
        {{ $tabs }}
    </div>

    <flux:separator class="md:hidden" />

    <div class="flex-1 self-stretch max-md:pt-6">
        @if (isset($tabHeading) || isset($tabSubheading))
            <div class="mb-6">
                @if (isset($tabHeading))
                    <flux:heading size="lg" level="2">{{ $tabHeading }}</flux:heading>
                @endif

                @if (isset($tabSubheading))
                    <flux:subheading>{{ $tabSubheading }}</flux:subheading>
                @endif
            </div>
        @endif

        {{ $slot }}
    </div>
</div>
