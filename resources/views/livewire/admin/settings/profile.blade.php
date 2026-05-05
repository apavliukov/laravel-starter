<form wire:submit="updateProfileInformation" class="w-full space-y-4">
    <div class="grid gap-4 sm:grid-cols-2">
        <flux:field>
            <flux:label>{{ __('First name') }}</flux:label>
            <flux:input wire:model="first_name" type="text" required autofocus autocomplete="given-name"/>
            <flux:error class="mt-0!" name="first_name"/>
        </flux:field>

        <flux:field>
            <flux:label>{{ __('Last name') }}</flux:label>
            <flux:input wire:model="last_name" type="text" required autocomplete="family-name"/>
            <flux:error class="mt-0!" name="last_name"/>
        </flux:field>
    </div>

    <div>
        <flux:field>
            <flux:label>{{ __('Email') }}</flux:label>
            <flux:input wire:model="email" type="email" required autocomplete="email"/>
            <flux:error class="mt-0!" name="email"/>
        </flux:field>

        @if ($showEmailVerificationLink)
            <div>
                <flux:text class="mt-4">
                    {{ __('Your email address is unverified.') }}

                    <flux:link class="text-sm cursor-pointer" wire:click.prevent="resendVerificationNotification">
                        {{ __('Click here to re-send the verification email.') }}
                    </flux:link>
                </flux:text>

                @if (session('status') === 'verification-link-sent')
                    <flux:text class="mt-2 font-medium !dark:text-green-400 text-green-600!">
                        {{ __('A new verification link has been sent to your email address.') }}
                    </flux:text>
                @endif
            </div>
        @endif
    </div>

    <div class="flex items-center gap-4">
        <div class="flex items-center justify-end">
            <flux:button variant="primary" type="submit" size="sm" class="w-full">{{ __('Save') }}</flux:button>
        </div>

        <x-action-message class="me-3" on="profile-updated">
            {{ __('Saved.') }}
        </x-action-message>
    </div>
</form>
