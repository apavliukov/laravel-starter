<?php

declare(strict_types=1);

namespace App\Actions\Organizations;

use AlexPavliukov\Authorization\Tenancy\TenantMembership;
use App\Models\Organization;
use App\Models\User;

final readonly class RemoveOrganizationMember
{
    public function __construct(private TenantMembership $membership) {}

    public function __invoke(Organization $organization, User $user): void
    {
        $this->membership->remove($user, $organization);
    }
}
