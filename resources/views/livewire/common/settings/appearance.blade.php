<div>
    <x-common.modules.settings.layout>
        <x-common.modules.settings.tab :heading="__('Appearance')"
                               :subheading=" __('Update the appearance settings for your account')"
        >
            <flux:radio.group x-data variant="segmented" x-model="$flux.appearance" class="mt-4">
                <flux:radio value="light" icon="sun">{{ __('Light') }}</flux:radio>
                <flux:radio value="dark" icon="moon">{{ __('Dark') }}</flux:radio>
                <flux:radio value="system" icon="computer-desktop">{{ __('System') }}</flux:radio>
            </flux:radio.group>
        </x-common.modules.settings.tab>
    </x-common.modules.settings.layout>
</div>
