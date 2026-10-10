@props(['mode' => 'create'])

@php
    $passwordLabel = $mode === 'edit' ? __('New Password') : __('Password');
    $passwordHelp = $mode === 'edit' ? __('Leave blank to keep current password.') : null;
@endphp

<div class="space-y-6">
    <div class="grid gap-6 sm:grid-cols-2">
        <flux:field>
            <flux:label>{{ __('First name') }}</flux:label>
            <flux:input wire:model="form.first_name" />
            <flux:error class="mt-0!" name="form.first_name" />
        </flux:field>

        <flux:field>
            <flux:label>{{ __('Last name') }}</flux:label>
            <flux:input wire:model="form.last_name" />
            <flux:error class="mt-0!" name="form.last_name" />
        </flux:field>
    </div>

    <flux:field>
        <flux:label>{{ __('Email') }}</flux:label>
        <flux:input type="email" wire:model="form.email" />
        <flux:error class="mt-0!" name="form.email" />
    </flux:field>

    <flux:field>
        <flux:label>{{ $passwordLabel }}</flux:label>
        <flux:input type="password" wire:model="form.password" />
        @if ($passwordHelp)
            <flux:description class="mt-0!">{{ $passwordHelp }}</flux:description>
        @endif
        <flux:error class="mt-0!" name="form.password" />
    </flux:field>

    <flux:checkbox
        wire:model="form.is_platform_admin"
        :label="__('Platform admin')"
        :description="__('Full access to every organization. Organization memberships are managed on the organization itself.')"
    />
    <flux:error class="mt-0!" name="form.is_platform_admin" />
</div>
