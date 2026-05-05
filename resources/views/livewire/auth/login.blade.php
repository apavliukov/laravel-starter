<div class="flex flex-col gap-6">
    <x-auth-header :title="__('Log in to your account')" />

    <x-auth-session-status class="text-center" :status="session('status')" />

    <form method="POST" wire:submit="login" class="flex flex-col gap-6">
        <flux:field>
            <flux:label>{{ __('Email address') }}</flux:label>
            <flux:input wire:model="email" type="email" required autofocus autocomplete="email" />
            <flux:error class="mt-0!" name="email" />
        </flux:field>

        <div class="relative">
            <flux:field>
                <flux:label>{{ __('Password') }}</flux:label>
                <flux:input wire:model="password" type="password" required autocomplete="current-password" viewable />
                <flux:error class="mt-0!" name="password" />
            </flux:field>

            @if (Route::has('password.request'))
                <flux:link class="absolute inset-e-0 top-0 text-xs" :href="route('password.request')" wire:navigate>
                    {{ __('Forgot your password?') }}
                </flux:link>
            @endif
        </div>

        <div class="flex items-center justify-end">
            <flux:button variant="primary" type="submit" class="w-full">{{ __('Log in') }}</flux:button>
        </div>
    </form>

    @if (Route::has('register'))
        <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-zinc-600 dark:text-zinc-400">
            <span>{{ __('Don\'t have an account?') }}</span>
            <flux:link :href="route('register')" wire:navigate>{{ __('Sign up') }}</flux:link>
        </div>
    @endif
</div>
