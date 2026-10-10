<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Organizations;

use App\Livewire\Admin\Organizations\DeleteOrganization;
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
#[CoversClass(DeleteOrganization::class)]
final class DeleteOrganizationTest extends TestCase
{
    #[Test]
    public function admin_can_soft_delete_an_organization(): void
    {
        $admin = User::factory()->admin()->create();
        $organization = Organization::factory()->create();
        $this->actingAs($admin);

        Livewire::test(DeleteOrganization::class, [
            'organization' => $organization,
            'modalName' => 'delete-organization-'.$organization->id,
        ])
            ->call('delete')
            ->assertRedirect(route('admin.platform.organizations.index'));

        $this->assertSoftDeleted('organizations', ['id' => $organization->id]);
    }

    #[Test]
    public function member_cannot_delete_an_organization(): void
    {
        $organization = Organization::factory()->create();
        $member = User::factory()->member($organization)->create();
        $this->actingAs($member);

        Livewire::test(DeleteOrganization::class, [
            'organization' => $organization,
            'modalName' => 'delete-organization-'.$organization->id,
        ])
            ->call('delete')
            ->assertForbidden();

        $this->assertDatabaseHas('organizations', ['id' => $organization->id, 'deleted_at' => null]);
    }
}
