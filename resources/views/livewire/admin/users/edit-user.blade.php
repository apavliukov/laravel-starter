<div class="w-full max-w-2xl">
    <form wire:submit="update" class="space-y-6">
        @include('livewire.admin.users.partials.form', ['mode' => 'edit'])

        <div class="border-t border-neutral-200 pt-6 dark:border-neutral-700">
            <flux:button type="submit" variant="primary">
                {{ __('Save Changes') }}
            </flux:button>
        </div>
    </form>
</div>
