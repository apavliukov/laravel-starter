<flux:navlist variant="outline">
    <flux:navlist.group :heading="__('Member')" class="grid">
        <flux:navlist.item icon="arrow-up-tray"
                           :href="route('admin.member.dashboard')"
                           :current="request()->is('admin.member.dashboard')"
                           wire:navigate
        >
            {{ __('Member Dashboard') }}
        </flux:navlist.item>
    </flux:navlist.group>
</flux:navlist>
