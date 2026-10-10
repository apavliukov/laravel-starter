<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Organizations;

use App\Livewire\Admin\Organizations\OrganizationList;
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
#[CoversClass(OrganizationList::class)]
final class OrganizationListTest extends TestCase
{
    #[Test]
    public function admin_can_view_the_index_page(): void
    {
        $admin = User::factory()->admin()->create();
        Organization::factory()->create(['name' => 'Acme']);

        $this->actingAs($admin)
            ->get(route('admin.platform.organizations.index'))
            ->assertOk()
            ->assertSeeLivewire(OrganizationList::class)
            ->assertSee('Acme');
    }

    #[Test]
    public function member_cannot_view_the_index_page(): void
    {
        $member = User::factory()->member()->create();

        $this->actingAs($member)
            ->get(route('admin.platform.organizations.index'))
            ->assertForbidden();
    }

    #[Test]
    public function search_filters_organizations(): void
    {
        $admin = User::factory()->admin()->create();
        Organization::factory()->create(['name' => 'Acme']);
        Organization::factory()->create(['name' => 'Globex']);
        $this->actingAs($admin);

        Livewire::test(OrganizationList::class)
            ->set('search', 'Acme')
            ->assertSee('Acme')
            ->assertDontSee('Globex');
    }

    #[Test]
    public function sort_by_toggles_direction_and_clear_filters_resets(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        Livewire::test(OrganizationList::class)
            ->call('sortBy', 'name')
            ->assertSet('sort', 'name')
            ->assertSet('direction', 'desc')
            ->call('sortBy', 'name')
            ->assertSet('direction', 'asc')
            ->set('search', 'x')
            ->call('clearFilters')
            ->assertSet('search', '')
            ->assertSet('sort', 'created_at')
            ->assertSet('direction', 'desc');
    }
}
