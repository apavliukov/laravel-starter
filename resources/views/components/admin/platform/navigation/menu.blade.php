<flux:navlist variant="outline">
    <flux:navlist.group :heading="__('Platform')" class="grid">
        <flux:navlist.item icon="arrow-up-tray"
                           :href="route('admin.platform.dashboard')"
                           :current="request()->is('admin.platform.dashboard')"
                           wire:navigate
        >
            {{ __('Platform Dashboard') }}
        </flux:navlist.item>
    </flux:navlist.group>
</flux:navlist>
