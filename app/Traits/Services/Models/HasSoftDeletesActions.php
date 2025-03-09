<?php

declare(strict_types=1);

namespace App\Traits\Services\Models;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

trait HasSoftDeletesActions
{
    /**
     * Paginate models incl. trashed
     */
    public function paginateWithTrashed(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator
    {
        return $this->repository->paginateWithTrashed($perPage, $columns);
    }

    /**
     * Find a model by id incl. trashed
     */
    public function findWithTrashed(int $id, array $columns = ['*']): ?Model
    {
        return $this->repository->findWithTrashed($id, $columns);
    }

    /**
     * Restore a model
     *
     * @param  User  $model
     */
    public function restore(Model $model): bool
    {
        return $this->repository->restore($model);
    }

    /**
     * Permanently delete a model
     */
    public function forceDelete(Model $model): ?bool
    {
        return $this->repository->forceDelete($model);
    }
}
