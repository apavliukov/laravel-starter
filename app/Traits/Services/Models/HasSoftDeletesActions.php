<?php

declare(strict_types=1);

namespace App\Traits\Services\Models;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

/**
 * @phpstan-ignore trait.unused
 */
trait HasSoftDeletesActions
{
    public function paginateWithTrashed(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator
    {
        return $this->repository->paginateWithTrashed($perPage, $columns);
    }

    public function findWithTrashed(int $id, array $columns = ['*']): ?Model
    {
        return $this->repository->findWithTrashed($id, $columns);
    }

    public function restore(Model $model): bool
    {
        return $this->repository->restore($model);
    }

    public function forceDelete(Model $model): ?bool
    {
        return $this->repository->forceDelete($model);
    }
}
