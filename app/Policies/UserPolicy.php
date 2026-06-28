<?php

declare(strict_types=1);

namespace App\Policies;

use AlexPavliukov\Authorization\AbstractPolicy;
use App\Models\User;

final readonly class UserPolicy extends AbstractPolicy
{
    protected function getModelClass(): string
    {
        return User::class;
    }
}
