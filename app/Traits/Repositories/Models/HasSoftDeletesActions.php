<?php

declare(strict_types=1);

namespace App\Traits\Repositories\Models;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

/**
 * @phpstan-ignore trait.unused
 */
trait HasSoftDeletesActions
{
    public function paginateWithTrashed(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator
    {
        return $this->modelClass::withTrashed()->paginate($perPage, $columns);
    }

    public function findWithTrashed(int $id, array $columns = ['*']): ?Model
    {
        return $this->modelClass::withTrashed()->find($id, $columns);
    }

    public function restore(Model $model): bool
    {
        return $model->restore();
    }

    public function forceDelete(Model $model): ?bool
    {
        return $model->forceDelete();
    }
}
