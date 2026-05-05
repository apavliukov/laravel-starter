<div class="flex flex-col gap-6">
    <x-auth-header :title="__('Create an account')" />

    <x-auth-session-status class="text-center" :status="session('status')" />

    <form method="POST" wire:submit="register" class="flex flex-col gap-6">
        <flux:field>
            <flux:label>{{ __('First name') }}</flux:label>
            <flux:input wire:model="first_name" type="text" required autofocus autocomplete="given-name" />
            <flux:error class="mt-0!" name="first_name" />
        </flux:field>

        <flux:field>
            <flux:label>{{ __('Last name') }}</flux:label>
            <flux:input wire:model="last_name" type="text" required autocomplete="family-name" />
            <flux:error class="mt-0!" name="last_name" />
        </flux:field>

        <flux:field>
            <flux:label>{{ __('Email address') }}</flux:label>
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
                {{ __('Create account') }}
            </flux:button>
        </div>
    </form>

    <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-zinc-600 dark:text-zinc-400">
        <span>{{ __('Already have an account?') }}</span>
        <flux:link :href="route('login')" wire:navigate>{{ __('Log in') }}</flux:link>
    </div>
</div>
