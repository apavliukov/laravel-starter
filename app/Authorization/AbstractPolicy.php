<?php

declare(strict_types=1);

namespace App\Authorization;

use App\Authorization\Enums\Ability;
use App\Models\User;
use BackedEnum;
use Illuminate\Database\Eloquent\Model;

abstract readonly class AbstractPolicy
{
    protected string $modelClass;

    public function __construct(private PermissionRegistry $registry)
    {
        $this->modelClass = $this->getModelClass();
    }

    abstract protected function getModelClass(): string;

    public function viewAny(User $user): bool
    {
        return $this->userCan($user, Ability::VIEW_ANY);
    }

    public function view(User $user, Model $model): bool
    {
        return $this->userCan($user, Ability::VIEW, $model);
    }

    public function create(User $user): bool
    {
        return $this->userCan($user, Ability::CREATE);
    }

    public function update(User $user, Model $model): bool
    {
        return $this->userCan($user, Ability::UPDATE, $model);
    }

    public function delete(User $user, Model $model): bool
    {
        return $this->userCan($user, Ability::DELETE, $model);
    }

    public function restore(User $user, Model $model): bool
    {
        return $this->userCan($user, Ability::RESTORE, $model);
    }

    public function forceDelete(User $user, Model $model): bool
    {
        return $this->userCan($user, Ability::FORCE_DELETE, $model);
    }

    protected function userCan(User $user, BackedEnum $ability, Model|string|null $model = null): bool
    {
        $modelToCheck = $model ?? $this->modelClass;

        return $user->can($this->registry->nameFromAbility($ability, $modelToCheck));
    }
}
