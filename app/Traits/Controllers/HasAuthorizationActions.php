<?php

declare(strict_types=1);

namespace App\Traits\Controllers;

use App\Enums\Policies\Ability;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

trait HasAuthorizationActions
{
    /**
     * Get permission name for ability from current model
     */
    public function getPermission(Ability $ability, Model|string $model): string
    {
        return Permission::makeNameFromAbility($ability, $model);
    }

    public function userCan(User $user, Ability $ability, Model|string|null $model = null): bool
    {
        $modelToCheck = $model ?? $this->modelClass;

        return $user->can($this->getPermission($ability, $modelToCheck));
    }

    public function userCannot(User $user, Ability $ability, Model|string|null $model = null): bool
    {
        $modelToCheck = $model ?? $this->modelClass;

        return $user->cannot($this->getPermission($ability, $modelToCheck));
    }

    public function userCanManageAny(User $user, Model|string|null $model = null): bool
    {
        $modelToCheck = $model ?? $this->modelClass;

        return $this->userCan($user, Ability::MANAGE_ANY, $modelToCheck);
    }
}
