<?php

declare(strict_types=1);

namespace Tests\Unit\Queries\Organizations;

use App\Dto\Organizations\ListOrganizationsFilters;
use App\Models\Organization;
use App\Models\User;
use App\Queries\Organizations\ListOrganizationsQuery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[Group('queries')]
#[Group('organizations')]
#[CoversClass(ListOrganizationsQuery::class)]
final class ListOrganizationsQueryTest extends TestCase
{
    #[Test]
    public function searches_by_name_and_counts_members(): void
    {
        $acme = Organization::factory()->create(['name' => 'Acme']);
        Organization::factory()->create(['name' => 'Globex']);
        User::factory()->member($acme)->count(2)->create();

        $result = new ListOrganizationsQuery()->handle(new ListOrganizationsFilters(search: 'acm'));

        $this->assertSame(1, $result->total());
        $this->assertSame(2, $result->getCollection()->first()?->members_count);
    }

    #[Test]
    public function sorts_by_name(): void
    {
        Organization::factory()->create(['name' => 'Zeta']);
        Organization::factory()->create(['name' => 'Alpha']);

        $result = new ListOrganizationsQuery()->handle(new ListOrganizationsFilters(sort: 'name', direction: 'asc'));

        $names = $result->getCollection()->map(fn (Organization $organization): string => $organization->name)->all();
        $this->assertSame(['Alpha', 'Zeta'], $names);
    }
}
