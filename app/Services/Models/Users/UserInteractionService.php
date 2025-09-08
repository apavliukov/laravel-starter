<?php

declare(strict_types=1);

namespace App\Services\Models\Users;

use App\Models\User;
use App\Repositories\Models\Users\UserRepository;
use App\Services\Models\BaseModelInteractionService;

/**
 * @property UserRepository $modelRepository
 *
 * @method User|null find(int $id, string[] $columns = ['*'])
 * @method User|null create(mixed[] $data)
 */
final readonly class UserInteractionService extends BaseModelInteractionService
{
    //
}
