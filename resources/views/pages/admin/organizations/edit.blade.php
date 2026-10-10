<x-admin.organizations.layout :mainHeading="$organization->name" :organization="$organization">
    <div class="space-y-10">
        <livewire:admin.organizations.edit-organization :organization="$organization" />

        <livewire:admin.organizations.organization-member-list :organization="$organization" />
    </div>
</x-admin.organizations.layout>
