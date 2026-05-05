@props([
    'mainHeading',
    'mainSubheading' => null,
    'header' => null,
    'headerBadges' => null,
    'headerButtons' => null,
    'tabs' => null,
    'tabHeading' => null,
    'tabSubheading' => null,
    'pageTitle' => null,
])

<x-layouts.admin :title="$pageTitle ?? $mainHeading">
    <section class="flex flex-col gap-6 lg:gap-8 w-full">
        <div class="relative w-full">
            @if (isset($header))
                {{ $header }}
            @else
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div class="flex flex-wrap items-center gap-4">
                        <div>
                            <flux:heading size="xl" level="1">{{ $mainHeading }}</flux:heading>
                            @if (isset($mainSubheading))
                                <flux:subheading size="lg">{{ $mainSubheading }}</flux:subheading>
                            @endif
                        </div>

                        @isset($headerBadges)
                            <div class="flex flex-wrap items-center gap-2">
                                {{ $headerBadges }}
                            </div>
                        @endisset
                    </div>

                    @isset($headerButtons)
                        <div class="flex flex-wrap gap-3">
                            {{ $headerButtons }}
                        </div>
                    @endisset
                </div>
            @endif
        </div>

        <flux:separator class="bg-zinc-200  dark:bg-zinc-700" />

        @isset($tabs)
            <x-admin.ui.layouts.partials.tabs :$tabs :$tabHeading :$tabSubheading>
                {{ $slot }}
            </x-admin.ui.layouts.partials.tabs>
        @else
            {{ $slot }}
        @endisset
    </section>
</x-layouts.admin>
