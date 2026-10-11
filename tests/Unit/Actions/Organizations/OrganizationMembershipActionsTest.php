<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\Organizations;

use AlexPavliukov\Authorization\Tenancy\OwnershipException;
use AlexPavliukov\Authorization\Tenancy\TenantMembership;
use App\Actions\Organizations\AddOrganizationMember;
use App\Actions\Organizations\RemoveOrganizationMember;
use App\Enums\Policies\Role;
use App\Models\Organization;
use App\Models\User;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[Group('actions')]
#[Group('organizations')]
#[CoversClass(AddOrganizationMember::class)]
#[CoversClass(RemoveOrganizationMember::class)]
final class OrganizationMembershipActionsTest extends TestCase
{
    #[Test]
    public function adds_a_member_and_re_adding_keeps_a_single_membership(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();

        resolve(AddOrganizationMember::class)($organization, $user, Role::MEMBER);
        resolve(AddOrganizationMember::class)($organization, $user, Role::MEMBER);

        $this->assertSame([$organization->id], resolve(TenantMembership::class)->tenantIdsOf($user));
        $this->assertSame(Role::MEMBER, $user->app_role);
    }

    #[Test]
    public function refuses_a_global_role_as_membership(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();

        $this->expectException(InvalidArgumentException::class);

        resolve(AddOrganizationMember::class)($organization, $user, Role::ADMIN);
    }

    #[Test]
    public function refuses_the_owner_role_as_a_membership(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();

        $this->expectException(OwnershipException::class);

        resolve(AddOrganizationMember::class)($organization, $user, Role::OWNER);
    }

    #[Test]
    public function refuses_to_demote_the_owner(): void
    {
        $owner = User::factory()->create();
        $organization = Organization::factory()->ownedBy($owner)->create();

        $this->expectException(OwnershipException::class);

        resolve(AddOrganizationMember::class)($organization, $owner, Role::MEMBER);
    }

    #[Test]
    public function refuses_to_remove_the_owner(): void
    {
        $owner = User::factory()->create();
        $organization = Organization::factory()->ownedBy($owner)->create();

        $this->assertThrows(static fn () => resolve(RemoveOrganizationMember::class)($organization, $owner), OwnershipException::class);

        $this->assertSame(Role::OWNER->value, resolve(TenantMembership::class)->roleFor($owner, $organization));
    }

    #[Test]
    public function removes_a_member_and_leaves_other_memberships_alone(): void
    {
        [$first, $second] = Organization::factory()->count(2)->create();
        $user = User::factory()->member($first)->create();
        resolve(AddOrganizationMember::class)($second, $user, Role::MEMBER);

        resolve(RemoveOrganizationMember::class)($first, $user);

        $this->assertSame([$second->id], resolve(TenantMembership::class)->tenantIdsOf($user));
    }
}
