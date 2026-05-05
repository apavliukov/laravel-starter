<div class="flex flex-col gap-6">
    <x-auth-header :title="__('Confirm password')" />

    <x-auth-session-status class="text-center" :status="session('status')" />

    <form method="POST" wire:submit="confirmPassword" class="flex flex-col gap-6">
        <flux:field>
            <flux:label>{{ __('Password') }}</flux:label>
            <flux:input wire:model="password" type="password" required autocomplete="new-password" viewable />
            <flux:error class="mt-0!" name="password" />
        </flux:field>

        <flux:button variant="primary" type="submit" class="w-full">{{ __('Confirm') }}</flux:button>
    </form>
</div>
