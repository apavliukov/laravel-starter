<?php

declare(strict_types=1);

namespace App\Contracts\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

interface PolicySoftDeletesInterface
{
    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Model $model): bool;

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Model $model): bool;
}
