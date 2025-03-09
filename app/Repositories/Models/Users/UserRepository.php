<?php

declare(strict_types=1);

namespace App\Repositories\Models\Users;

use App\Models\User;
use App\Repositories\Models\BaseModelRepository;

/**
 * @property User $modelClass
 *
 * @method User|null find(int $id, array $columns = ['*'])
 * @method User create(array $data)
 */
class UserRepository extends BaseModelRepository
{
    /**
     * Get model class
     */
    protected function getModelClass(): string
    {
        return User::class;
    }
}
