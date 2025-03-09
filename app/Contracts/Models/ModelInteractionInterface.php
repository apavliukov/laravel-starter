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
     *
     * @param array<string> $columns
     *
     * @return Collection<int, Model>
     */
    public function all(array $columns = ['*']): Collection;

    /**
     * Paginate models
     *
     * @param array<string> $columns
     *
     * @return LengthAwarePaginator<Model>
     */
    public function paginate(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator;

    /**
     * Find a model by id
     *
     * @param array<string> $columns
     */
    public function find(int $id, array $columns = ['*']): ?Model;

    /**
     * Create a model
     *
     * @param array<string, mixed> $data
     *
     * @return Model
     */
    public function create(array $data);

    /**
     * Update a model
     *
     * @param array<string, mixed> $data
     */
    public function update(Model $model, array $data): bool;

    /**
     * Delete a model
     */
    public function delete(Model $model): ?bool;
}
