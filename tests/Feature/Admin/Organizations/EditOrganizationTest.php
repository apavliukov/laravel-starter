<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Organizations;

use App\Livewire\Admin\Organizations\EditOrganization;
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
#[CoversClass(EditOrganization::class)]
final class EditOrganizationTest extends TestCase
{
    #[Test]
    public function admin_can_view_the_edit_page(): void
    {
        $admin = User::factory()->admin()->create();
        $organization = Organization::factory()->create();

        $this->actingAs($admin)
            ->get(route('admin.platform.organizations.edit', $organization))
            ->assertOk()
            ->assertSeeLivewire(EditOrganization::class);
    }

    #[Test]
    public function member_cannot_view_the_edit_page_of_their_own_organization(): void
    {
        $organization = Organization::factory()->create();
        $member = User::factory()->member($organization)->create();

        $this->actingAs($member)
            ->get(route('admin.platform.organizations.edit', $organization))
            ->assertForbidden();
    }

    #[Test]
    public function admin_can_update_an_organization(): void
    {
        $admin = User::factory()->admin()->create();
        $organization = Organization::factory()->create(['name' => 'Old']);
        $this->actingAs($admin);

        Livewire::test(EditOrganization::class, ['organization' => $organization])
            ->assertSet('form.name', 'Old')
            ->set('form.name', 'New')
            ->call('update')
            ->assertHasNoErrors();

        $this->assertSame('New', $organization->refresh()->name);
    }
}
