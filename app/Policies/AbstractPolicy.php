<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Policies\Abilities\Ability;
use App\Models\User;
use App\Traits\Policies\HasAuthorizationActions;
use Illuminate\Database\Eloquent\Model;

abstract readonly class AbstractPolicy
{
    use HasAuthorizationActions;

    protected string $modelClass;

    public function __construct()
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
}
