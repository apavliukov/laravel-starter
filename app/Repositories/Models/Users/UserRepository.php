<?php

declare(strict_types=1);

namespace App\Repositories\Models\Users;

use App\Models\User;
use App\Repositories\Models\BaseModelRepository;

/**
 * @property User $modelClass
 *
 * @method User|null find(int $id, string[] $columns = ['*'])
 * @method User create(mixed[] $data)
 */
final readonly class UserRepository extends BaseModelRepository
{
    protected function getModelClass(): string
    {
        return User::class;
    }
}
