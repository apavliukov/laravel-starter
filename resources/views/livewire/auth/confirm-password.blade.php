<div class="flex flex-col gap-6">
    <x-auth-header :title="__('Confirm password')" />

    <x-auth-session-status class="text-center" :status="session('status')" />

    <form method="POST" wire:submit="confirmPassword" class="flex flex-col gap-6">
        <flux:input
            wire:model="password"
            :label="__('Password')"
            type="password"
            required
            autocomplete="new-password"
            viewable
        />

        <flux:button variant="primary" type="submit" class="w-full">{{ __('Confirm') }}</flux:button>
    </form>
</div>
