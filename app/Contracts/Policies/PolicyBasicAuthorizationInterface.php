<?php

declare(strict_types=1);

namespace App\Contracts\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

interface PolicyBasicAuthorizationInterface
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool;

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool;

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Model $model): bool;

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Model $model): bool;
}
