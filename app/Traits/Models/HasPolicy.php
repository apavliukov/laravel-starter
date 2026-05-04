<?php

declare(strict_types=1);

namespace App\Traits\Models;

use App\Enums\Policies\Abilities\Ability;
use App\Models\Permission;
use BackedEnum;

trait HasPolicy
{
    /**
     * Returns the standard CRUD abilities that generate permissions for this model.
     * Override to restrict which abilities apply — e.g. exclude RESTORE and
     * FORCE_DELETE on models that don't use SoftDeletes.
     *
     * @return list<Ability>
     */
    public static function getBasicAbilities(): array
    {
        return Ability::cases();
    }

    /**
     * Returns model-specific ability enum cases beyond the standard CRUD set.
     * Override in the model to declare custom abilities:
     *
     *   public static function getCustomAbilities(): array
     *   {
     *       return UserAbility::cases();
     *   }
     *
     * @return list<BackedEnum>
     */
    public static function getCustomAbilities(): array
    {
        return [];
    }

    public static function makeModelPermission(BackedEnum $ability): string
    {
        return Permission::makeNameFromAbility($ability, static::class);
    }

    /** @return list<string> */
    public static function makeAllPermissions(): array
    {
        $abilities = array_merge(static::getBasicAbilities(), static::getCustomAbilities());

        return array_map(
            static fn (BackedEnum $ability) => static::makeModelPermission($ability),
            $abilities,
        );
    }
}
