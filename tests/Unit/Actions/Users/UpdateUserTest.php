<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\Users;

use AlexPavliukov\Authorization\Tenancy\TenantMembership;
use App\Actions\Users\UpdateUser;
use App\Dto\Users\UpdateUserInput;
use App\Enums\Policies\Role;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[Group('actions')]
#[Group('users')]
#[CoversClass(UpdateUser::class)]
final class UpdateUserTest extends TestCase
{
    private UpdateUser $action;

    private TenantMembership $membership;

    protected function setUp(): void
    {
        parent::setUp();

        $this->action = $this->app->make(UpdateUser::class);
        $this->membership = $this->app->make(TenantMembership::class);
    }

    #[Test]
    public function updates_fields_and_rehashes_password_when_provided(): void
    {
        $user = User::factory()->create([
            'first_name' => 'Old',
            'last_name' => 'Name',
            'email' => 'old@example.test',
        ]);
        $originalHash = $user->password;

        $updated = ($this->action)($user, new UpdateUserInput(
            firstName: 'New',
            lastName: 'Name',
            email: 'new@example.test',
            password: 'fresh-pass',
        ));

        $this->assertSame('New', $updated->first_name);
        $this->assertSame('new@example.test', $updated->email);
        $this->assertNotSame($originalHash, $updated->password);
        $this->assertTrue(Hash::check('fresh-pass', $updated->password));
    }

    #[Test]
    public function leaves_password_unchanged_when_input_password_is_null(): void
    {
        $user = User::factory()->create();
        $originalHash = $user->password;

        $updated = ($this->action)($user, new UpdateUserInput(
            firstName: $user->first_name,
            lastName: $user->last_name,
            email: $user->email,
            password: null,
        ));

        $this->assertSame($originalHash, $updated->password);
    }

    #[Test]
    public function promotes_to_platform_admin_without_touching_memberships(): void
    {
        [$first, $second] = Organization::factory()->count(2)->create();
        $user = User::factory()->member($first)->create();
        $this->membership->add($user, $second, Role::MEMBER);

        $updated = ($this->action)($user, new UpdateUserInput(
            firstName: $user->first_name,
            lastName: $user->last_name,
            email: $user->email,
            password: null,
            platformAdmin: true,
        ));

        $this->assertTrue($updated->is_platform_admin);
        $this->assertSame(Role::ADMIN, $updated->app_role);
        $this->assertEqualsCanonicalizing([$first->id, $second->id], $this->membership->tenantIdsOf($updated));
    }

    #[Test]
    public function demotes_a_platform_admin_and_keeps_their_memberships(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->admin()->create();
        $this->membership->add($user, $organization, Role::MEMBER);

        $updated = ($this->action)($user, new UpdateUserInput(
            firstName: $user->first_name,
            lastName: $user->last_name,
            email: $user->email,
            password: null,
            platformAdmin: false,
        ));

        $this->assertFalse($updated->is_platform_admin);
        $this->assertSame(Role::MEMBER, $updated->app_role);
        $this->assertSame([$organization->id], $this->membership->tenantIdsOf($updated));
    }

    #[Test]
    public function saving_a_member_without_changes_keeps_every_membership(): void
    {
        [$first, $second] = Organization::factory()->count(2)->create();
        $user = User::factory()->member($first)->create();
        $this->membership->add($user, $second, Role::MEMBER);

        $updated = ($this->action)($user, new UpdateUserInput(
            firstName: $user->first_name,
            lastName: 'Renamed',
            email: $user->email,
            password: null,
        ));

        $this->assertSame('Renamed', $updated->last_name);
        $this->assertEqualsCanonicalizing([$first->id, $second->id], $this->membership->tenantIdsOf($updated));
    }
}
