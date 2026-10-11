<?php

declare(strict_types=1);

namespace App\Actions\Organizations;

use AlexPavliukov\Authorization\Tenancy\TenantMembership;
use App\Enums\Policies\Role;
use App\Models\Organization;
use App\Models\User;

/** The only way `owner_id` changes after creation; the previous owner stays as a MEMBER. */
final readonly class TransferOrganizationOwnership
{
    public function __construct(private TenantMembership $membership) {}

    public function __invoke(Organization $organization, User $newOwner): void
    {
        $this->membership->transferOwnership($organization, $newOwner, Role::MEMBER);
    }
}
