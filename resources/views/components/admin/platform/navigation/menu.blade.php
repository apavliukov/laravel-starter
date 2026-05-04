<flux:navlist variant="outline">
    <flux:navlist.group :heading="__('Platform')" class="grid">
        <flux:navlist.item icon="home"
                           :href="route('admin.dashboard')"
                           :current="request()->routeIs('admin.dashboard')"
                           wire:navigate
        >
            {{ __('Platform Dashboard') }}
        </flux:navlist.item>

        <flux:navlist.item icon="users"
                           :href="route('admin.platform.users.index')"
                           :current="request()->routeIs('admin.platform.users.*')"
                           wire:navigate
        >
            {{ __('Users') }}
        </flux:navlist.item>
    </flux:navlist.group>
</flux:navlist>
