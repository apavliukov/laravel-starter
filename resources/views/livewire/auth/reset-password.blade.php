<div class="flex flex-col gap-6">
    <x-auth-header :title="__('Reset password')" :description="__('Please enter your new password below')" />

    <x-auth-session-status class="text-center" :status="session('status')" />

    <form method="POST" wire:submit="resetPassword" class="flex flex-col gap-6">
        <flux:field>
            <flux:label>{{ __('Email') }}</flux:label>
            <flux:input wire:model="email" type="email" required autocomplete="email" />
            <flux:error class="mt-0!" name="email" />
        </flux:field>

        <flux:field>
            <flux:label>{{ __('Password') }}</flux:label>
            <flux:input wire:model="password" type="password" required autocomplete="new-password" viewable />
            <flux:error class="mt-0!" name="password" />
        </flux:field>

        <flux:field>
            <flux:label>{{ __('Confirm password') }}</flux:label>
            <flux:input wire:model="password_confirmation" type="password" required autocomplete="new-password" viewable />
            <flux:error class="mt-0!" name="password_confirmation" />
        </flux:field>

        <div class="flex items-center justify-end">
            <flux:button type="submit" variant="primary" class="w-full">
                {{ __('Reset password') }}
            </flux:button>
        </div>
    </form>
</div>
