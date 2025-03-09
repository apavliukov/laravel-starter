<?php

declare(strict_types=1);

namespace App\Services\Models;

use App\Contracts\Repositories\Models\ModelRepositoryInterface;
use App\Contracts\Services\Models\ModelInteractionServiceInterface;
use App\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

abstract class BaseModelInteractionService extends BaseService implements ModelInteractionServiceInterface
{
    public function __construct(protected ModelRepositoryInterface $repository) {}

    /**
     * Get all models
     */
    public function all(array $columns = ['*']): Collection
    {
        return $this->repository->all($columns);
    }

    /**
     * Paginate models
     */
    public function paginate(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator
    {
        return $this->repository->paginate($perPage, $columns);
    }

    /**
     * Find a model by id
     */
    public function find(int $id, array $columns = ['*']): ?Model
    {
        return $this->repository->find($id, $columns);
    }

    /**
     * Create a model
     */
    public function create(array $data): ?Model
    {
        if ($data === []) {
            return null;
        }

        return $this->repository->create($data);
    }

    /**
     * Update a model
     */
    public function update(Model $model, array $data): bool
    {
        return $this->repository->update($model, $data);
    }

    /**
     * Delete a model
     */
    public function delete(Model $model): ?bool
    {
        return $this->repository->delete($model);
    }
}
