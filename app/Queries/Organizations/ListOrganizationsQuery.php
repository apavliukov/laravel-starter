<?php

declare(strict_types=1);

namespace App\Queries\Organizations;

use App\Dto\Organizations\ListOrganizationsFilters;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Expression;
use Illuminate\Pagination\LengthAwarePaginator;

final readonly class ListOrganizationsQuery
{
    /** @return LengthAwarePaginator<int, Organization> */
    public function handle(ListOrganizationsFilters $filters): LengthAwarePaginator
    {
        return Organization::query()
            // members() joins model_has_roles: a plain count would count role rows, not users.
            ->withCount(['members' => fn (Builder $query) => $query->select(new Expression('count(distinct users.id)'))])
            ->search($filters->search)
            ->orderBy($filters->sort, $filters->direction)
            ->paginate($filters->perPage);
    }
}
