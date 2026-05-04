<flux:navlist variant="outline">
    <flux:navlist.group :heading="__('Member')" class="grid">
        <flux:navlist.item icon="home"
                           :href="route('admin.dashboard')"
                           :current="request()->routeIs('admin.dashboard')"
                           wire:navigate
        >
            {{ __('Member Dashboard') }}
        </flux:navlist.item>
    </flux:navlist.group>
</flux:navlist>
