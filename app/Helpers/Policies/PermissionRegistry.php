<?php

declare(strict_types=1);

namespace App\Helpers\Policies;

use App\Enums\Policies\Role as RoleEnum;
use App\Models\User;
use App\Traits\Models\HasPolicy;
use BackedEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Central registry of permissions and their role assignments used by seeders.
 */
final readonly class PermissionRegistry
{
    /**
     * Converts an ability + model to a DB permission string.
     * Ability values are camelCase (`viewAny`) — converted to space-separated (`view any users`).
     */
    public function nameFromAbility(BackedEnum $ability, Model|string $model): string
    {
        $table = $model instanceof Model ? $model->getTable() : get_model_table($model);

        return sprintf('%s %s', Str::snake($ability->value, ' '), str_replace('_', ' ', $table));
    }

    public function allPermissions(): array
    {
        return array_merge(
            $this->permissionsFor(User::class),
        );
    }

    public function forRole(RoleEnum $role): array
    {
        return match ($role) {
            RoleEnum::ADMIN => $this->adminPermissions(),
            RoleEnum::MEMBER => $this->memberPermissions(),
        };
    }

    /** @param class-string<HasPolicy> $modelClass */
    private function permissionsFor(string $modelClass): array
    {
        $abilities = array_merge(
            $modelClass::getBasicAbilities(),
            $modelClass::getCustomAbilities(),
        );

        return array_map(
            fn (BackedEnum $ability): string => $this->nameFromAbility($ability, $modelClass),
            $abilities,
        );
    }

    private function adminPermissions(): array
    {
        return [];
    }

    private function memberPermissions(): array
    {
        return [];
    }
}
