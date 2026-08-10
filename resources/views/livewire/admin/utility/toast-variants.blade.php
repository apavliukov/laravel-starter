<div class="space-y-4 p-6">
    <h2 class="mb-6 text-2xl font-bold text-gray-900 dark:text-white">{{ __('Toast Component Demo') }}</h2>

    <div class="space-y-4">
        <div>
            <h3 class="mb-2 text-lg font-semibold text-gray-700 dark:text-gray-300">{{ __('Toast Variants') }}</h3>
            <div class="flex flex-wrap gap-3">
                <flux:button wire:click="showSuccess" variant="primary" size="sm">
                    {{ __('Show Success Toast') }}
                </flux:button>

                <flux:button wire:click="showError" variant="danger" size="sm">
                    {{ __('Show Error Toast') }}
                </flux:button>

                <flux:button wire:click="showWarning" variant="filled" size="sm">
                    {{ __('Show Warning Toast') }}
                </flux:button>

                <flux:button wire:click="showInfo" variant="primary" size="sm">
                    {{ __('Show Info Toast') }}
                </flux:button>
            </div>
        </div>

        <div>
            <h3 class="mb-2 text-lg font-semibold text-gray-700 dark:text-gray-300">{{ __('Special Options') }}</h3>
            <flux:button wire:click="showPersistent" variant="filled" size="sm">
                {{ __('Show Persistent Toast (No auto-dismiss)') }}
            </flux:button>
        </div>
    </div>
</div>
