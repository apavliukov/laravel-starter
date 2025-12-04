@props([
    'header',
    'tabs',
    'mainHeading',
    'mainSubheading',
    'tabHeading',
    'tabSubheading',
])

<section class="flex flex-col gap-6 lg:gap-8 w-full">
    <div class="relative w-full">
        @if (isset($header))
            {{ $header }}
        @else
            @if (isset($mainHeading))
                <flux:heading size="xl" level="1">{{ $mainHeading }}</flux:heading>
            @endif
            @if (isset($mainSubheading))
                <flux:subheading size="lg">{{ $mainSubheading }}</flux:subheading>
            @endif
        @endif
    </div>

    <flux:separator variant="subtle" />

    <div class="flex items-start max-md:flex-col">
        <div class="me-10 w-full pb-4 md:w-[220px]">
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
</section>
