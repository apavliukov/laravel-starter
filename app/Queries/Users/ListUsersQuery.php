<?php

declare(strict_types=1);

namespace App\Queries\Users;

use App\Dto\Users\ListUsersFilters;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

final readonly class ListUsersQuery
{
    /** @return LengthAwarePaginator<int, User> */
    public function handle(ListUsersFilters $filters): LengthAwarePaginator
    {
        $query = User::query()->with('roles')->search($filters->search);

        if ($filters->sort === 'name') {
            $query->orderBy('first_name', $filters->direction)
                ->orderBy('last_name', $filters->direction);
        } else {
            $query->orderBy($filters->sort, $filters->direction);
        }

        return $query->paginate($filters->perPage);
    }
}
