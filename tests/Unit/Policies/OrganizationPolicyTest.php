<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use AlexPavliukov\Authorization\Enums\Ability;
use AlexPavliukov\Authorization\Tenancy\Tenancy;
use App\Enums\Policies\OrganizationAbility;
use App\Models\Organization;
use App\Models\User;
use App\Policies\OrganizationPolicy;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

#[Group('policies')]
#[Group('organizations')]
#[CoversClass(OrganizationPolicy::class)]
/** @extends BasePolicyTestCase<OrganizationPolicy> */
final class OrganizationPolicyTest extends BasePolicyTestCase
{
    /** The super-admin bypass sits in front of the policy (Gate::before), so ADMIN is checked through the Gate with no tenant active. */
    #[Test]
    public function admin_manages_every_organization_through_the_bypass(): void
    {
        $admin = User::factory()->admin()->create();
        $organization = Organization::factory()->create();

        $this->assertTrue($admin->can(Ability::VIEW_ANY, Organization::class));
        $this->assertTrue($admin->can(Ability::CREATE, Organization::class));
        $this->assertTrue($admin->can(Ability::VIEW, $organization));
        $this->assertTrue($admin->can(Ability::UPDATE, $organization));
        $this->assertTrue($admin->can(Ability::DELETE, $organization));
        $this->assertTrue($admin->can(OrganizationAbility::TRANSFER_OWNERSHIP, $organization));
    }

    #[Test]
    public function owner_manages_only_the_organization_that_is_active(): void
    {
        $owner = User::factory()->create();
        $own = Organization::factory()->ownedBy($owner)->create();
        $other = Organization::factory()->create();
        Tenancy::set($own);

        $this->assertTrue($this->policy->view($owner, $own));
        $this->assertTrue($this->policy->update($owner, $own));
        $this->assertTrue($this->policy->transferOwnership($owner, $own));
        $this->assertFalse($this->policy->delete($owner, $own));
        $this->assertFalse($this->policy->viewAny($owner));
        $this->assertFalse($this->policy->create($owner));

        $this->assertFalse($this->policy->view($owner, $other));
        $this->assertFalse($this->policy->update($owner, $other));
        $this->assertFalse($this->policy->transferOwnership($owner, $other));
    }

    #[Test]
    public function member_cannot_manage_organizations_including_their_own(): void
    {
        $organization = Organization::factory()->create();
        $member = User::factory()->member($organization)->create();
        Tenancy::set($organization);

        $this->assertFalse($this->policy->viewAny($member));
        $this->assertFalse($this->policy->create($member));
        $this->assertFalse($this->policy->view($member, $organization));
        $this->assertFalse($this->policy->update($member, $organization));
        $this->assertFalse($this->policy->delete($member, $organization));
        $this->assertFalse($this->policy->transferOwnership($member, $organization));
    }

    protected function getPolicyClass(): string
    {
        return OrganizationPolicy::class;
    }
}
