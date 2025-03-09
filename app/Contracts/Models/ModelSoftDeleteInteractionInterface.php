<?php

declare(strict_types=1);

namespace App\Contracts\Models;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

interface ModelSoftDeleteInteractionInterface
{
    /**
     * Find a model by id incl. trashed
     */
    public function findWithTrashed(int $id, array $columns = ['*']): ?Model;

    /**
     * Paginate models incl. trashed
     */
    public function paginateWithTrashed(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator;

    /**
     * Restore a model
     */
    public function restore(Model $model): bool;

    /**
     * Permanently delete a model
     */
    public function forceDelete(Model $model): ?bool;
}
