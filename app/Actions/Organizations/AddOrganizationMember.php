<?php

declare(strict_types=1);

namespace App\Actions\Organizations;

use AlexPavliukov\Authorization\Tenancy\TenantMembership;
use App\Enums\Policies\Role;
use App\Models\Organization;
use App\Models\User;
use InvalidArgumentException;

/** The single write path for memberships; re-invoking for an existing member changes their role. */
final readonly class AddOrganizationMember
{
    public function __construct(private TenantMembership $membership) {}

    public function __invoke(Organization $organization, User $user, Role $role): void
    {
        throw_if($role->isSuperAdmin(), InvalidArgumentException::class, "Role [{$role->value}] is global and cannot be an organization membership.");

        $this->membership->changeRole($user, $organization, $role);
    }
}
