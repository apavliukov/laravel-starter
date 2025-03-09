<?php

declare(strict_types=1);

namespace App\Repositories\Models;

use App\Contracts\Repositories\Models\ModelRepositoryInterface;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

abstract class BaseModelRepository extends BaseRepository implements ModelRepositoryInterface
{
    /** @var class-string<Model> */
    protected string $modelClass;

    public function __construct()
    {
        $this->modelClass = $this->getModelClass();
    }

    /**
     * Get model class
     *
     * @return class-string<Model>
     */
    abstract protected function getModelClass(): string;

    /**
     * Get all models
     */
    public function all(array $columns = ['*']): Collection
    {
        return $this->modelClass::all($columns);
    }

    /**
     * Paginate models
     */
    public function paginate(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator
    {
        return $this->modelClass::paginate($perPage, $columns);
    }

    /**
     * Find a model by id
     */
    public function find(int $id, array $columns = ['*']): ?Model
    {
        return $this->modelClass::find($id, $columns);
    }

    /**
     * Create a model
     *
     * @param array<string, mixed> $data
     */
    public function create(array $data): Model
    {
        return $this->modelClass::create($data);
    }

    /**
     * Update a model
     */
    public function update(Model $model, array $data): bool
    {
        return $model->update($data);
    }

    /**
     * Delete a model
     */
    public function delete(Model $model): ?bool
    {
        return $model->delete();
    }
}
