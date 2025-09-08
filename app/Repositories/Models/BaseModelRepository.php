<?php

declare(strict_types=1);

namespace App\Repositories\Models;

use App\Contracts\Repositories\Models\ModelRepositoryInterface;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

abstract readonly class BaseModelRepository extends BaseRepository implements ModelRepositoryInterface
{
    /** @var class-string<Model> */
    protected string $modelClass;

    public function __construct()
    {
        $this->modelClass = $this->getModelClass();
    }

    /**
     * @return class-string<Model>
     */
    abstract protected function getModelClass(): string;

    public function all(array $columns = ['*']): Collection
    {
        return $this->modelClass::all($columns);
    }

    public function paginate(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator
    {
        return $this->modelClass::paginate($perPage, $columns);
    }

    public function find(int $id, array $columns = ['*']): ?Model
    {
        return $this->modelClass::find($id, $columns);
    }

    public function create(array $data): Model
    {
        return $this->modelClass::create($data);
    }

    public function update(Model $model, array $data): bool
    {
        return $model->update($data);
    }

    public function delete(Model $model): ?bool
    {
        return $model->delete();
    }
}
