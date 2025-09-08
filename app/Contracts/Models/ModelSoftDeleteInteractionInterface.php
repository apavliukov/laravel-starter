<?php

declare(strict_types=1);

namespace App\Contracts\Models;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

interface ModelSoftDeleteInteractionInterface
{
    public function findWithTrashed(int $id, array $columns = ['*']): ?Model;

    /**
     * @return LengthAwarePaginator<Model>
     */
    public function paginateWithTrashed(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator;

    public function restore(Model $model): bool;

    public function forceDelete(Model $model): ?bool;
}
