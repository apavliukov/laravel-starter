<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\Organizations;

use App\Actions\Organizations\CreateOrganization;
use App\Actions\Organizations\DeleteOrganization;
use App\Actions\Organizations\UpdateOrganization;
use App\Dto\Organizations\CreateOrganizationInput;
use App\Dto\Organizations\UpdateOrganizationInput;
use App\Models\Organization;
use App\Models\User;
use DomainException;
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
    public function creates_an_organization(): void
    {
        $organization = resolve(CreateOrganization::class)(new CreateOrganizationInput(name: 'Acme'));

        $this->assertSame('Acme', $organization->name);
        $this->assertDatabaseHas('organizations', ['name' => 'Acme']);
    }

    #[Test]
    public function updates_an_organization(): void
    {
        $organization = Organization::factory()->create(['name' => 'Old']);

        $updated = resolve(UpdateOrganization::class)($organization, new UpdateOrganizationInput(name: 'New'));

        $this->assertSame('New', $updated->name);
    }

    #[Test]
    public function refuses_to_delete_an_organization_with_members(): void
    {
        $organization = Organization::factory()->create();
        User::factory()->member($organization)->create();

        $this->expectException(DomainException::class);

        resolve(DeleteOrganization::class)($organization);
    }

    #[Test]
    public function soft_deletes_an_organization(): void
    {
        $organization = Organization::factory()->create();

        resolve(DeleteOrganization::class)($organization);

        $this->assertSoftDeleted('organizations', ['id' => $organization->id]);
    }
}
