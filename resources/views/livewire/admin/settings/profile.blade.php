<x-admin.settings.layout
    :tab-heading="__('Profile')"
    :tab-subheading="__('Update your name and email address')"
>
    <form wire:submit="updateProfileInformation" class="w-full space-y-6 mb-6">
        <flux:field>
            <flux:label>{{ __('Name') }}</flux:label>
            <flux:input wire:model="name" type="text" required autofocus autocomplete="name" />
            <flux:error class="mt-0!" name="name" />
        </flux:field>

        <div>
            <flux:field>
                <flux:label>{{ __('Email') }}</flux:label>
                <flux:input wire:model="email" type="email" required autocomplete="email" />
                <flux:error class="mt-0!" name="email" />
            </flux:field>

            @if (auth()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail &&! auth()->user()->hasVerifiedEmail())
                <div>
                    <flux:text class="mt-4">
                        {{ __('Your email address is unverified.') }}

                        <flux:link class="text-sm cursor-pointer" wire:click.prevent="resendVerificationNotification">
                            {{ __('Click here to re-send the verification email.') }}
                        </flux:link>
                    </flux:text>

                    @if (session('status') === 'verification-link-sent')
                        <flux:text class="mt-2 font-medium !dark:text-green-400 !text-green-600">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </flux:text>
                    @endif
                </div>
            @endif
        </div>

        <div class="flex items-center gap-4">
            <div class="flex items-center justify-end">
                <flux:button variant="primary" type="submit" class="w-full">{{ __('Save') }}</flux:button>
            </div>

            <x-action-message class="me-3" on="profile-updated">
                {{ __('Saved.') }}
            </x-action-message>
        </div>
    </form>

    <livewire:admin.settings.delete-user-form />
</x-admin.settings.layout>
