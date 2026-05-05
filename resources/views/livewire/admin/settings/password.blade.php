<x-admin.settings.layout
    :tab-heading="__('Update password')"
    :tab-subheading="__('Ensure your account is using a long, random password to stay secure')"
>
    <form method="POST" wire:submit="updatePassword" class="mt-6 space-y-6">
        <flux:field>
            <flux:label>{{ __('Current password') }}</flux:label>
            <flux:input wire:model="current_password" type="password" required autocomplete="current-password" />
            <flux:error class="mt-0!" name="current_password" />
        </flux:field>

        <flux:field>
            <flux:label>{{ __('New password') }}</flux:label>
            <flux:input wire:model="password" type="password" required autocomplete="new-password" />
            <flux:error class="mt-0!" name="password" />
        </flux:field>

        <flux:field>
            <flux:label>{{ __('Confirm Password') }}</flux:label>
            <flux:input wire:model="password_confirmation" type="password" required autocomplete="new-password" />
            <flux:error class="mt-0!" name="password_confirmation" />
        </flux:field>

        <div class="flex items-center gap-4">
            <div class="flex items-center justify-end">
                <flux:button variant="primary" type="submit" class="w-full">{{ __('Save') }}</flux:button>
            </div>

            <x-action-message class="me-3" on="password-updated">
                {{ __('Saved.') }}
            </x-action-message>
        </div>
    </form>
</x-admin.settings.layout>
