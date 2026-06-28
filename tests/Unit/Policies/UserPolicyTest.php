<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Models\User;
use App\Policies\UserPolicy;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

#[Group('policies')]
#[Group('users')]
#[CoversClass(UserPolicy::class)]
final class UserPolicyTest extends BasePolicyTestCase
{
    #[Test]
    public function admin_can_view_any_users(): void
    {
        $admin = User::factory()->admin()->create();

        $this->assertTrue($this->policy->viewAny($admin));
    }

    #[Test]
    public function member_cannot_view_any_users(): void
    {
        $member = User::factory()->member()->create();

        $this->assertFalse($this->policy->viewAny($member));
    }

    #[Test]
    public function admin_can_view_a_user(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        $this->assertTrue($this->policy->view($admin, $user));
    }

    #[Test]
    public function member_cannot_view_a_user(): void
    {
        $member = User::factory()->member()->create();
        $user = User::factory()->create();

        $this->assertFalse($this->policy->view($member, $user));
    }

    #[Test]
    public function admin_can_create_users(): void
    {
        $admin = User::factory()->admin()->create();

        $this->assertTrue($this->policy->create($admin));
    }

    #[Test]
    public function member_cannot_create_users(): void
    {
        $member = User::factory()->member()->create();

        $this->assertFalse($this->policy->create($member));
    }

    #[Test]
    public function admin_can_update_a_user(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        $this->assertTrue($this->policy->update($admin, $user));
    }

    #[Test]
    public function member_cannot_update_a_user(): void
    {
        $member = User::factory()->member()->create();
        $user = User::factory()->create();

        $this->assertFalse($this->policy->update($member, $user));
    }

    #[Test]
    public function admin_can_delete_a_user(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        $this->assertTrue($this->policy->delete($admin, $user));
    }

    #[Test]
    public function member_cannot_delete_a_user(): void
    {
        $member = User::factory()->member()->create();
        $user = User::factory()->create();

        $this->assertFalse($this->policy->delete($member, $user));
    }

    #[Test]
    public function admin_can_restore_a_user(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        $this->assertTrue($this->policy->restore($admin, $user));
    }

    #[Test]
    public function member_cannot_restore_a_user(): void
    {
        $member = User::factory()->member()->create();
        $user = User::factory()->create();

        $this->assertFalse($this->policy->restore($member, $user));
    }

    #[Test]
    public function admin_can_force_delete_a_user(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        $this->assertTrue($this->policy->forceDelete($admin, $user));
    }

    #[Test]
    public function member_cannot_force_delete_a_user(): void
    {
        $member = User::factory()->member()->create();
        $user = User::factory()->create();

        $this->assertFalse($this->policy->forceDelete($member, $user));
    }

    protected function getPolicyClass(): string
    {
        return UserPolicy::class;
    }
}
