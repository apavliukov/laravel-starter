<?php

declare(strict_types=1);

namespace Tests\Feature\Tenancy;

use AlexPavliukov\Authorization\Tenancy\Tenancy;
use App\Enums\Policies\Role;
use App\Models\Organization;
use App\Models\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[Group('tenancy')]
final class TenantResolutionTest extends TestCase
{
    #[Test]
    public function a_request_by_a_member_runs_inside_their_organization(): void
    {
        $organization = Organization::factory()->create();
        $member = User::factory()->member($organization)->create();

        $this->actingAs($member)->get(route('admin.dashboard'))->assertOk();

        $this->assertSame($organization->id, Tenancy::id());
        $this->assertSame($organization->id, getPermissionsTeamId());
    }

    #[Test]
    public function a_request_by_an_admin_without_an_organization_runs_with_no_tenant(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();

        $this->assertNull(Tenancy::id());
    }

    #[Test]
    public function member_roles_are_visible_regardless_of_the_active_team(): void
    {
        $member = User::factory()->member()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('admin.platform.users.index'))->assertOk();

        $this->assertSame(Role::MEMBER, $member->app_role);
        $this->assertTrue($member->is_member);
        $this->assertFalse($member->is_admin);
    }
}
