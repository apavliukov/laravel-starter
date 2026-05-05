<div class="flex flex-col gap-6">
    <x-auth-header :title="__('Forgot password')" />

    <x-auth-session-status class="text-center" :status="session('status')" />

    <form method="POST" wire:submit="sendPasswordResetLink" class="flex flex-col gap-6">
        <flux:field>
            <flux:label>{{ __('Email Address') }}</flux:label>
            <flux:input wire:model="email" type="email" required autofocus />
            <flux:error class="mt-0!" name="email" />
        </flux:field>

        <flux:button variant="primary" type="submit" class="w-full">{{ __('Email password reset link') }}</flux:button>
    </form>

    <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-zinc-400">
        <span>{{ __('Or, return to') }}</span>
        <flux:link :href="route('login')" wire:navigate>{{ __('log in') }}</flux:link>
    </div>
</div>
