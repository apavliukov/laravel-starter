<x-admin.settings.layout :tab-heading="__('Profile')"
                         :tab-subheading="__('Update your name and email address')"
>
    <div class="flex flex-col gap-6">
        <livewire:admin.settings.profile />
        <flux:separator />
        <livewire:admin.settings.delete-user-form />
    </div>
</x-admin.settings.layout>
