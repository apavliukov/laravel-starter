<div class="space-y-6">
    <flux:field>
        <flux:label>{{ __('Name') }}</flux:label>
        <flux:input wire:model="form.name" />
        <flux:error class="mt-0!" name="form.name" />
    </flux:field>

    @if (! $form->exists)
        <flux:field>
            <flux:label>{{ __('Owner') }}</flux:label>
            <flux:input type="email" wire:model="form.ownerEmail" :placeholder="__('Existing user email')" />
            <flux:description>{{ __('The owner holds the organization and is the only member who can hand it over.') }}</flux:description>
            <flux:error class="mt-0!" name="form.ownerEmail" />
        </flux:field>
    @endif
</div>
