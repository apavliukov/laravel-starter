@props(['mode' => 'create'])

@php
    $passwordLabel = $mode === 'edit' ? __('New Password') : __('Password');
    $passwordHelp = $mode === 'edit' ? __('Leave blank to keep current password.') : null;
@endphp

<div class="space-y-6">
    <div class="grid gap-6 sm:grid-cols-2">
        <flux:field>
            <flux:label>{{ __('First name') }}</flux:label>
            <flux:input wire:model="form.firstName" />
            <flux:error class="mt-0!" name="form.firstName" />
        </flux:field>

        <flux:field>
            <flux:label>{{ __('Last name') }}</flux:label>
            <flux:input wire:model="form.lastName" />
            <flux:error class="mt-0!" name="form.lastName" />
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
            <flux:description>{{ $passwordHelp }}</flux:description>
        @endif
        <flux:error class="mt-0!" name="form.password" />
    </flux:field>

    <flux:field>
        <flux:label>{{ __('Role') }}</flux:label>
        <flux:select wire:model="form.role">
            @foreach ($this->roleOptions as $case)
                <flux:select.option :value="$case->value">{{ $case->label() }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:error class="mt-0!" name="form.role" />
    </flux:field>
</div>
