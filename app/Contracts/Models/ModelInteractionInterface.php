<?php

declare(strict_types=1);

namespace App\Contracts\Models;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

interface ModelInteractionInterface
{
    /**
     * @return Collection<int, Model>
     */
    public function all(array $columns = ['*']): Collection;

    /**
     * @return LengthAwarePaginator<Model>
     */
    public function paginate(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator;

    public function find(int $id, array $columns = ['*']): ?Model;

    /**
     * @return Model
     */
    public function create(array $data);

    public function update(Model $model, array $data): bool;

    public function delete(Model $model): ?bool;
}
