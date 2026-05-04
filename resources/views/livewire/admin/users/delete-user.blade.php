<flux:modal :name="$modalName" class="w-full max-w-md">
    <div class="space-y-6">
        <flux:heading size="lg">{{ __('Delete User') }}</flux:heading>

        <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-900/20">
            <div class="flex gap-3">
                <flux:icon.exclamation-triangle class="h-5 w-5 text-amber-500" />
                <div>
                    <flux:text class="font-medium text-amber-800 dark:text-amber-200">
                        {{ __('Are you sure you want to delete :name? This action cannot be undone.', ['name' => $user->name]) }}
                    </flux:text>
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <flux:modal.close>
                <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
            </flux:modal.close>
            <flux:button wire:click="delete" variant="danger">{{ __('Delete') }}</flux:button>
        </div>
    </div>
</flux:modal>
