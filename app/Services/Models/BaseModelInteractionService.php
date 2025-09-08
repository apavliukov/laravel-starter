<?php

declare(strict_types=1);

namespace App\Services\Models;

use App\Contracts\Repositories\Models\ModelRepositoryInterface;
use App\Contracts\Services\Models\ModelInteractionServiceInterface;
use App\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

abstract readonly class BaseModelInteractionService extends BaseService implements ModelInteractionServiceInterface
{
    public function __construct(protected ModelRepositoryInterface $modelRepository) {}

    public function all(array $columns = ['*']): Collection
    {
        return $this->modelRepository->all($columns);
    }

    public function paginate(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator
    {
        return $this->modelRepository->paginate($perPage, $columns);
    }

    public function find(int $id, array $columns = ['*']): ?Model
    {
        return $this->modelRepository->find($id, $columns);
    }

    public function create(array $data): ?Model
    {
        if ($data === []) {
            return null;
        }

        return $this->modelRepository->create($data);
    }

    public function update(Model $model, array $data): bool
    {
        return $this->modelRepository->update($model, $data);
    }

    public function delete(Model $model): ?bool
    {
        return $this->modelRepository->delete($model);
    }
}
