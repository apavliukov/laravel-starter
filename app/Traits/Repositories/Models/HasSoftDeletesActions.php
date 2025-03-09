<?php

declare(strict_types=1);

namespace App\Traits\Repositories\Models;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

trait HasSoftDeletesActions
{
    /**
     * Paginate models incl. trashed
     */
    public function paginateWithTrashed(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator
    {
        return $this->modelClass::withTrashed()->paginate($perPage, $columns);
    }

    /**
     * Find a model by id incl. trashed
     */
    public function findWithTrashed(int $id, array $columns = ['*']): ?Model
    {
        return $this->modelClass::withTrashed()->find($id, $columns);
    }

    /**
     * Restore a model
     */
    public function restore(Model $model): bool
    {
        return $model->restore();
    }

    /**
     * Permanently delete a model
     */
    public function forceDelete(Model $model): ?bool
    {
        return $model->forceDelete();
    }
}
