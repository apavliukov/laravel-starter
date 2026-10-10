<?php

declare(strict_types=1);

namespace Tests\Feature\Tenancy;

use AlexPavliukov\Authorization\Tenancy\EnsureTenantSelected;
use AlexPavliukov\Authorization\Tenancy\Tenancy;
use AlexPavliukov\Authorization\Tenancy\TenantMembership;
use AlexPavliukov\Authorization\Tenancy\TenantResolver;
use App\Enums\Policies\Role;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[Group('tenancy')]
final class TenantResolutionTest extends TestCase
{
    private const string MEMBER_AREA = '/_test/member-area';

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['web', 'auth', EnsureTenantSelected::class])
            ->get(self::MEMBER_AREA, static fn (): string => 'ok');
    }

    #[Test]
    public function a_member_of_one_organization_runs_inside_it_without_choosing(): void
    {
        $organization = Organization::factory()->create();
        $member = User::factory()->member($organization)->create();

        $this->actingAs($member)->get(self::MEMBER_AREA)->assertOk();

        $this->assertSame($organization->id, Tenancy::id());
        $this->assertSame($organization->id, getPermissionsTeamId());
    }

    #[Test]
    public function an_admin_without_memberships_runs_with_no_tenant_and_is_kept_out_of_the_member_area(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
        $this->assertNull(Tenancy::id());

        $this->actingAs($admin)->get(self::MEMBER_AREA)->assertForbidden();
    }

    #[Test]
    public function several_memberships_and_no_session_choice_resolve_to_no_tenant(): void
    {
        [$first, $second] = Organization::factory()->count(2)->create();
        $user = User::factory()->member($first)->create();
        resolve(TenantMembership::class)->add($user, $second, Role::MEMBER);

        $this->actingAs($user)->get(route('admin.dashboard'))->assertOk();
        $this->assertNull(Tenancy::id());

        $this->actingAs($user)->get(self::MEMBER_AREA)->assertForbidden();
    }

    #[Test]
    public function the_session_choice_wins_among_memberships(): void
    {
        [$first, $second] = Organization::factory()->count(2)->create();
        $user = User::factory()->member($first)->create();
        resolve(TenantMembership::class)->add($user, $second, Role::MEMBER);

        $this->actingAs($user)
            ->withSession([TenantResolver::sessionKey() => $second->id])
            ->get(self::MEMBER_AREA)
            ->assertOk();

        $this->assertSame($second->id, Tenancy::id());
        $this->assertSame($second->id, getPermissionsTeamId());
    }

    #[Test]
    public function a_session_choice_outside_the_memberships_is_dropped(): void
    {
        [$own, $foreign] = Organization::factory()->count(2)->create();
        $member = User::factory()->member($own)->create();

        $this->actingAs($member)
            ->withSession([TenantResolver::sessionKey() => $foreign->id])
            ->get(self::MEMBER_AREA)
            ->assertOk()
            ->assertSessionMissing(TenantResolver::sessionKey());

        $this->assertSame($own->id, Tenancy::id());
    }

    #[Test]
    public function member_roles_are_visible_regardless_of_the_active_team(): void
    {
        $member = User::factory()->member()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('admin.platform.users.index'))->assertOk();

        $this->assertSame(Role::MEMBER, $member->app_role);
        $this->assertFalse($member->is_platform_admin);
        $this->assertTrue($admin->is_platform_admin);
    }
}
