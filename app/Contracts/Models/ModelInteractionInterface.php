<?php

declare(strict_types=1);

namespace App\Contracts\Models;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

interface ModelInteractionInterface
{
    /**
     * Get all models
     */
    public function all(array $columns = ['*']): Collection;

    /**
     * Paginate models
     */
    public function paginate(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator;

    /**
     * Find a model by id
     */
    public function find(int $id, array $columns = ['*']): ?Model;

    /**
     * Create a model
     */
    public function create(array $data);

    /**
     * Update a model
     */
    public function update(Model $model, array $data): bool;

    /**
     * Delete a model
     */
    public function delete(Model $model): ?bool;
}
