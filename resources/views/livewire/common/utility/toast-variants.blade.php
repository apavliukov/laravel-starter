<div class="p-6 space-y-4">
    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">{{ __('Toast Component Demo') }}</h2>

    <div class="space-y-4">
        <div>
            <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-2">{{ __('Toast Variants') }}</h3>
            <div class="flex flex-wrap gap-3">
                <flux:button wire:click="showSuccess" variant="primary">
                    {{ __('Show Success Toast') }}
                </flux:button>

                <flux:button wire:click="showError" variant="danger">
                    {{ __('Show Error Toast') }}
                </flux:button>

                <flux:button wire:click="showWarning" variant="filled">
                    {{ __('Show Warning Toast') }}
                </flux:button>

                <flux:button wire:click="showInfo" variant="primary">
                    {{ __('Show Info Toast') }}
                </flux:button>
            </div>
        </div>

        <div>
            <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-2">{{ __('Special Options') }}</h3>
            <flux:button wire:click="showPersistent" variant="filled">
                {{ __('Show Persistent Toast (No auto-dismiss)') }}
            </flux:button>
        </div>
    </div>
</div>
