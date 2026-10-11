<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Organizations;

use AlexPavliukov\Authorization\Tenancy\TenantMembership;
use App\Enums\Policies\Role;
use App\Livewire\Admin\Organizations\OrganizationMemberList;
use App\Models\Organization;
use App\Models\User;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[Group('admin')]
#[Group('organizations')]
#[Group('livewire')]
#[CoversClass(OrganizationMemberList::class)]
final class OrganizationMemberListTest extends TestCase
{
    #[Test]
    public function the_edit_page_lists_the_members(): void
    {
        $admin = User::factory()->admin()->create();
        $organization = Organization::factory()->create();
        $member = User::factory()->member($organization)->create(['first_name' => 'Zed', 'last_name' => 'Member']);
        User::factory()->member()->create(['first_name' => 'Other', 'last_name' => 'Org']);

        $this->actingAs($admin)
            ->get(route('admin.platform.organizations.edit', $organization))
            ->assertOk()
            ->assertSee($member->email)
            ->assertDontSee('Other Org');
    }

    #[Test]
    public function admin_adds_an_existing_user_by_email(): void
    {
        $admin = User::factory()->admin()->create();
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['email' => 'new@example.test']);
        $this->actingAs($admin);

        Livewire::test(OrganizationMemberList::class, ['organization' => $organization])
            ->set('email', 'new@example.test')
            ->set('role', Role::MEMBER->value)
            ->call('add')
            ->assertHasNoErrors()
            ->assertDispatched('toast', variant: 'success')
            ->assertSet('email', '');

        $this->assertTrue(resolve(TenantMembership::class)->isMember($user, $organization));
        $this->assertSame(Role::MEMBER->value, resolve(TenantMembership::class)->roleFor($user, $organization));
    }

    #[Test]
    public function a_second_membership_does_not_remove_the_first(): void
    {
        $admin = User::factory()->admin()->create();
        [$first, $second] = Organization::factory()->count(2)->create();
        $user = User::factory()->member($first)->create();
        $this->actingAs($admin);

        Livewire::test(OrganizationMemberList::class, ['organization' => $second])
            ->set('email', $user->email)
            ->call('add')
            ->assertHasNoErrors();

        $this->assertEqualsCanonicalizing([$first->id, $second->id], resolve(TenantMembership::class)->tenantIdsOf($user));
    }

    #[Test]
    public function a_global_role_cannot_be_given_as_a_membership(): void
    {
        $admin = User::factory()->admin()->create();
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        $this->actingAs($admin);

        Livewire::test(OrganizationMemberList::class, ['organization' => $organization])
            ->set('email', $user->email)
            ->set('role', Role::ADMIN->value)
            ->call('add')
            ->assertHasErrors(['role']);

        $this->assertFalse(resolve(TenantMembership::class)->isMember($user, $organization));
    }

    #[Test]
    public function an_unknown_email_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();
        $organization = Organization::factory()->create();
        $this->actingAs($admin);

        Livewire::test(OrganizationMemberList::class, ['organization' => $organization])
            ->set('email', 'nobody@example.test')
            ->call('add')
            ->assertHasErrors(['email']);
    }

    #[Test]
    public function admin_removes_a_member(): void
    {
        $admin = User::factory()->admin()->create();
        $organization = Organization::factory()->create();
        $member = User::factory()->member($organization)->create();
        $this->actingAs($admin);

        Livewire::test(OrganizationMemberList::class, ['organization' => $organization])
            ->call('remove', $member->id)
            ->assertDispatched('toast', variant: 'success');

        $this->assertFalse(resolve(TenantMembership::class)->isMember($member, $organization));
        $this->assertNull($member->fresh()?->app_role);
    }

    #[Test]
    public function a_user_outside_the_organization_cannot_be_removed_through_it(): void
    {
        $admin = User::factory()->admin()->create();
        [$first, $second] = Organization::factory()->count(2)->create();
        $outsider = User::factory()->member($second)->create();
        $this->actingAs($admin);

        Livewire::test(OrganizationMemberList::class, ['organization' => $first])
            ->call('remove', $outsider->id)
            ->assertNotFound();

        $this->assertTrue(resolve(TenantMembership::class)->isMember($outsider, $second));
    }

    #[Test]
    public function the_owner_role_is_not_offered_as_a_membership(): void
    {
        $admin = User::factory()->admin()->create();
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        $this->actingAs($admin);

        Livewire::test(OrganizationMemberList::class, ['organization' => $organization])
            ->assertSet('roleOptions', [Role::MEMBER])
            ->set('email', $user->email)
            ->set('role', Role::OWNER->value)
            ->call('add')
            ->assertHasErrors(['role']);
    }

    #[Test]
    public function the_owner_cannot_be_removed_or_demoted(): void
    {
        $admin = User::factory()->admin()->create();
        $owner = User::factory()->create();
        $organization = Organization::factory()->ownedBy($owner)->create();
        $this->actingAs($admin);

        Livewire::test(OrganizationMemberList::class, ['organization' => $organization])
            ->call('remove', $owner->id)
            ->assertDispatched('toast', variant: 'error')
            ->call('changeRole', $owner->id, Role::MEMBER->value)
            ->assertDispatched('toast', variant: 'error');

        $this->assertSame(Role::OWNER->value, resolve(TenantMembership::class)->roleFor($owner, $organization));
    }

    #[Test]
    public function admin_transfers_ownership_to_a_member(): void
    {
        $admin = User::factory()->admin()->create();
        $previous = User::factory()->create();
        $organization = Organization::factory()->ownedBy($previous)->create();
        $next = User::factory()->member($organization)->create();
        $this->actingAs($admin);

        Livewire::test(OrganizationMemberList::class, ['organization' => $organization])
            ->call('transferOwnership', $next->id)
            ->assertDispatched('toast', variant: 'success');

        $membership = resolve(TenantMembership::class);

        $this->assertTrue($membership->isOwner($next, $organization));
        $this->assertSame(Role::MEMBER->value, $membership->roleFor($previous, $organization));
    }

    #[Test]
    public function ownership_cannot_be_transferred_to_an_outsider(): void
    {
        $admin = User::factory()->admin()->create();
        $organization = Organization::factory()->create();
        $outsider = User::factory()->create();
        $this->actingAs($admin);

        Livewire::test(OrganizationMemberList::class, ['organization' => $organization])
            ->call('transferOwnership', $outsider->id)
            ->assertNotFound();

        $this->assertFalse(resolve(TenantMembership::class)->isOwner($outsider, $organization));
    }

    #[Test]
    public function member_cannot_transfer_ownership(): void
    {
        $organization = Organization::factory()->create();
        $member = User::factory()->member($organization)->create();
        $this->actingAs($member);

        Livewire::test(OrganizationMemberList::class, ['organization' => $organization])
            ->call('transferOwnership', $member->id)
            ->assertForbidden();
    }

    #[Test]
    public function member_cannot_manage_memberships(): void
    {
        $organization = Organization::factory()->create();
        $member = User::factory()->member($organization)->create();
        $other = User::factory()->create();
        $this->actingAs($member);

        Livewire::test(OrganizationMemberList::class, ['organization' => $organization])
            ->set('email', $other->email)
            ->call('add')
            ->assertForbidden();

        Livewire::test(OrganizationMemberList::class, ['organization' => $organization])
            ->call('remove', $member->id)
            ->assertForbidden();
    }
}
