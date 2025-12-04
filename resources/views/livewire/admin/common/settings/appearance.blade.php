<x-common.modules.settings.layout
    :tab-heading="__('Appearance')"
    :tab-subheading="__('Update the appearance settings for your account')"
>
    <flux:radio.group x-data variant="segmented" x-model="$flux.appearance" class="mt-4">
        <flux:radio value="light" icon="sun">{{ __('Light') }}</flux:radio>
        <flux:radio value="dark" icon="moon">{{ __('Dark') }}</flux:radio>
        <flux:radio value="system" icon="computer-desktop">{{ __('System') }}</flux:radio>
    </flux:radio.group>
</x-common.modules.settings.layout>
