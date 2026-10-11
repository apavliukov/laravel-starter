<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Organizations;

use AlexPavliukov\Authorization\Tenancy\TenantMembership;
use App\Enums\Policies\Role;
use App\Livewire\Admin\Organizations\CreateOrganization;
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
#[CoversClass(CreateOrganization::class)]
final class CreateOrganizationTest extends TestCase
{
    #[Test]
    public function admin_can_view_the_create_page(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.platform.organizations.create'))
            ->assertOk()
            ->assertSeeLivewire(CreateOrganization::class);
    }

    #[Test]
    public function member_cannot_view_the_create_page(): void
    {
        $member = User::factory()->member()->create();

        $this->actingAs($member)
            ->get(route('admin.platform.organizations.create'))
            ->assertForbidden();
    }

    #[Test]
    public function admin_can_create_an_organization_with_its_owner(): void
    {
        $admin = User::factory()->admin()->create();
        $owner = User::factory()->create(['email' => 'owner@example.test']);
        $this->actingAs($admin);

        Livewire::test(CreateOrganization::class)
            ->set('form.name', 'Acme')
            ->set('form.ownerEmail', 'owner@example.test')
            ->call('store')
            ->assertHasNoErrors()
            ->assertRedirect();

        $organization = Organization::query()->where('name', 'Acme')->firstOrFail();

        $this->assertTrue($owner->is($organization->owner));
        $this->assertSame(Role::OWNER->value, resolve(TenantMembership::class)->roleFor($owner, $organization));
    }

    #[Test]
    public function validation_requires_a_name_and_an_existing_owner(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        Livewire::test(CreateOrganization::class)
            ->set('form.name', '')
            ->set('form.ownerEmail', 'nobody@example.test')
            ->call('store')
            ->assertHasErrors(['form.name', 'form.ownerEmail']);
    }
}
