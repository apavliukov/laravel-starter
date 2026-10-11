<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\Organizations;

use AlexPavliukov\Authorization\Tenancy\TenantMembership;
use App\Actions\Organizations\TransferOrganizationOwnership;
use App\Enums\Policies\Role;
use App\Models\Organization;
use App\Models\User;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[Group('actions')]
#[Group('organizations')]
#[CoversClass(TransferOrganizationOwnership::class)]
final class TransferOrganizationOwnershipTest extends TestCase
{
    #[Test]
    public function moves_the_column_and_the_owner_role_and_demotes_the_previous_owner_to_member(): void
    {
        $previous = User::factory()->create();
        $organization = Organization::factory()->ownedBy($previous)->create();
        $next = User::factory()->member($organization)->create();
        $membership = resolve(TenantMembership::class);

        resolve(TransferOrganizationOwnership::class)($organization, $next);

        $this->assertTrue($membership->isOwner($next, $organization));
        $this->assertSame(Role::OWNER->value, $membership->roleFor($next, $organization));
        $this->assertSame(Role::MEMBER->value, $membership->roleFor($previous, $organization));
        $this->assertFalse($membership->isOwner($previous, $organization));
    }
}
