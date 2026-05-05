<div class="w-full max-w-2xl">
    <form wire:submit="store" class="space-y-6">
        @include('livewire.admin.users.partials.form', ['mode' => 'create'])

        <div class="border-t border-neutral-200 pt-6 dark:border-neutral-700">
            <flux:button type="submit" variant="primary" size="sm">
                {{ __('Create User') }}
            </flux:button>
        </div>
    </form>
</div>
