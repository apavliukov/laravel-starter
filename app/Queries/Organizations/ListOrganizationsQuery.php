<?php

declare(strict_types=1);

namespace App\Queries\Organizations;

use App\Dto\Organizations\ListOrganizationsFilters;
use App\Models\Organization;
use Illuminate\Pagination\LengthAwarePaginator;

final readonly class ListOrganizationsQuery
{
    /** @return LengthAwarePaginator<int, Organization> */
    public function handle(ListOrganizationsFilters $filters): LengthAwarePaginator
    {
        return Organization::query()
            ->withCount('users')
            ->search($filters->search)
            ->orderBy($filters->sort, $filters->direction)
            ->paginate($filters->perPage);
    }
}
