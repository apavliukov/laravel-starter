<?php

declare(strict_types=1);

namespace App\Policies;

use App\Contracts\Policies\PolicySoftDeletesInterface;
use App\Enums\Policies\Ability;
use App\Enums\Policies\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

final readonly class UserPolicy extends AbstractPolicy implements PolicySoftDeletesInterface
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->app_role === Role::ADMIN;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $this->userCan($user, Ability::CREATE);
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  User  $model
     */
    public function update(User $user, Model $model): bool
    {
        return $this->userCan($user, Ability::UPDATE);
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  User  $model
     */
    public function delete(User $user, Model $model): bool
    {
        return $this->userCanManageAny($user);
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  User  $model
     */
    public function restore(User $user, Model $model): bool
    {
        return $this->userCanManageAny($user);
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  User  $model
     */
    public function forceDelete(User $user, Model $model): bool
    {
        return $this->userCanManageAny($user);
    }

    protected function getModelClass(): string
    {
        return User::class;
    }
}
