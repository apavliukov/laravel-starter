<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\Organizations;

use AlexPavliukov\Authorization\Tenancy\TenantMembership;
use App\Actions\Organizations\CreateOrganization;
use App\Actions\Organizations\DeleteOrganization;
use App\Actions\Organizations\UpdateOrganization;
use App\Dto\Organizations\CreateOrganizationInput;
use App\Dto\Organizations\UpdateOrganizationInput;
use App\Enums\Policies\Role;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[Group('actions')]
#[Group('organizations')]
#[CoversClass(CreateOrganization::class)]
#[CoversClass(UpdateOrganization::class)]
#[CoversClass(DeleteOrganization::class)]
final class OrganizationActionsTest extends TestCase
{
    #[Test]
    public function creates_an_organization_owned_by_the_given_user(): void
    {
        $owner = User::factory()->create(['email' => 'owner@example.test']);

        $organization = resolve(CreateOrganization::class)(new CreateOrganizationInput(name: 'Acme', ownerEmail: 'owner@example.test'));

        $this->assertSame('Acme', $organization->name);
        $this->assertTrue($owner->is($organization->owner));
        $this->assertSame(Role::OWNER->value, resolve(TenantMembership::class)->roleFor($owner, $organization));
        $this->assertSame(Role::OWNER, $owner->app_role);
    }

    #[Test]
    public function refuses_an_unknown_owner_and_leaves_nothing_behind(): void
    {
        $this->assertThrows(
            static fn () => resolve(CreateOrganization::class)(new CreateOrganizationInput(name: 'Acme', ownerEmail: 'nobody@example.test')),
            ModelNotFoundException::class,
        );

        $this->assertDatabaseMissing('organizations', ['name' => 'Acme']);
    }

    #[Test]
    public function updates_an_organization(): void
    {
        $organization = Organization::factory()->create(['name' => 'Old']);

        $updated = resolve(UpdateOrganization::class)($organization, new UpdateOrganizationInput(name: 'New'));

        $this->assertSame('New', $updated->name);
    }

    #[Test]
    public function archives_an_organization_and_keeps_its_memberships(): void
    {
        $organization = Organization::factory()->create();
        $member = User::factory()->member($organization)->create();

        resolve(DeleteOrganization::class)($organization);

        $this->assertSoftDeleted('organizations', ['id' => $organization->id]);
        $this->assertSame([$organization->id], resolve(TenantMembership::class)->tenantIdsOf($member));
    }
}
