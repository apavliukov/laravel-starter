<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Models\Organization;
use App\Models\User;
use App\Policies\OrganizationPolicy;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

#[Group('policies')]
#[Group('organizations')]
#[CoversClass(OrganizationPolicy::class)]
final class OrganizationPolicyTest extends BasePolicyTestCase
{
    #[Test]
    public function admin_can_manage_organizations(): void
    {
        $admin = User::factory()->admin()->create();
        $organization = Organization::factory()->create();

        $this->assertTrue($this->policy->viewAny($admin));
        $this->assertTrue($this->policy->create($admin));
        $this->assertTrue($this->policy->view($admin, $organization));
        $this->assertTrue($this->policy->update($admin, $organization));
        $this->assertTrue($this->policy->delete($admin, $organization));
    }

    #[Test]
    public function member_cannot_manage_organizations_including_their_own(): void
    {
        $organization = Organization::factory()->create();
        $member = User::factory()->member($organization)->create();

        $this->assertFalse($this->policy->viewAny($member));
        $this->assertFalse($this->policy->create($member));
        $this->assertFalse($this->policy->view($member, $organization));
        $this->assertFalse($this->policy->update($member, $organization));
        $this->assertFalse($this->policy->delete($member, $organization));
    }

    protected function getPolicyClass(): string
    {
        return OrganizationPolicy::class;
    }
}
