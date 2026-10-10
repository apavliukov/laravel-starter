<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Organizations;

use AlexPavliukov\Authorization\Enums\Ability;
use AlexPavliukov\Authorization\Tenancy\TenantMembership;
use App\Actions\Organizations\AddOrganizationMember;
use App\Actions\Organizations\RemoveOrganizationMember;
use App\Enums\Policies\Role;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;

final class OrganizationMemberList extends Component
{
    public Organization $organization;

    public string $email = '';

    public string $role = '';

    public function mount(Organization $organization): void
    {
        $this->organization = $organization;
        $this->role = Role::default()->value;
    }

    /** @return array<int, Role> */
    #[Computed]
    public function roleOptions(): array
    {
        return array_values(array_filter(Role::cases(), static fn (Role $role): bool => ! $role->isSuperAdmin()));
    }

    /** @return Collection<int, User> */
    #[Computed]
    public function members(): Collection
    {
        return $this->organization->members()->orderBy('first_name')->orderBy('last_name')->get();
    }

    public function roleOf(User $user): ?Role
    {
        $name = resolve(TenantMembership::class)->roleFor($user, $this->organization);

        return $name === null ? null : Role::tryFrom($name);
    }

    public function add(AddOrganizationMember $action): void
    {
        Gate::authorize(Ability::UPDATE, $this->organization);

        $this->validate([
            'email' => ['required', 'email', Rule::exists('users', 'email')->whereNull('deleted_at')],
            'role' => $this->roleRules(),
        ]);

        $user = User::query()->where('email', $this->email)->firstOrFail();

        $action($this->organization, $user, Role::from($this->role));

        $this->reset('email');
        unset($this->members);

        $this->dispatch('toast', message: __('Member added'), variant: 'success');
    }

    public function changeRole(int $userId, string $role, AddOrganizationMember $action): void
    {
        Gate::authorize(Ability::UPDATE, $this->organization);

        $this->role = $role;
        $this->validate(['role' => $this->roleRules()]);

        $action($this->organization, $this->member($userId), Role::from($role));

        unset($this->members);

        $this->dispatch('toast', message: __('Member role updated'), variant: 'success');
    }

    public function remove(int $userId, RemoveOrganizationMember $action): void
    {
        Gate::authorize(Ability::UPDATE, $this->organization);

        $action($this->organization, $this->member($userId));

        unset($this->members);

        $this->dispatch('toast', message: __('Member removed'), variant: 'success');
    }

    public function render(): View
    {
        return view('livewire.admin.organizations.organization-member-list');
    }

    /** Resolved through the organization, never by raw id: a foreign user 404s. */
    private function member(int $userId): User
    {
        /** @var User|null $user */
        $user = $this->organization->members()->find($userId);

        abort_if($user === null, 404);

        return $user;
    }

    /** @return array<int, mixed> */
    private function roleRules(): array
    {
        return ['required', Rule::enum(Role::class), Rule::in(array_map(static fn (Role $role): string => $role->value, $this->roleOptions()))];
    }
}
