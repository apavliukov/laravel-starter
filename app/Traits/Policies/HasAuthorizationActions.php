<?php

declare(strict_types=1);

namespace App\Traits\Policies;

use App\Models\Permission;
use App\Models\User;
use BackedEnum;
use Illuminate\Database\Eloquent\Model;

trait HasAuthorizationActions
{
    public function userCan(User $user, BackedEnum $ability, Model|string|null $model = null): bool
    {
        $modelToCheck = $model ?? $this->modelClass;

        return $user->can($this->getPermission($ability, $modelToCheck));
    }

    /**
     * Accepts any string-backed enum so global Ability cases and per-model
     * ability enums both flow through the same permission name pipeline.
     */
    protected function getPermission(BackedEnum $ability, Model|string $model): string
    {
        return Permission::makeNameFromAbility($ability, $model);
    }
}
