<?php

declare(strict_types=1);

namespace App\Traits\Models;

use App\Enums\Policies\Ability;
use App\Models\Permission;

trait HasPolicy
{
    /**
     * Set default abilities for model
     */
    public static function getAbilities(): array
    {
        return Ability::values();
    }

    /**
     * Generate permission string for ability
     */
    public static function makeModelPermission(Ability $ability): string
    {
        return Permission::makeNameFromAbility($ability, self::class);
    }

    /**
     * Make permissions for model
     */
    public static function makeAllPermissions(): array
    {
        $modelPermissions = [];
        $modelAbilities = self::getAbilities();

        foreach ($modelAbilities as $abilityName) {
            $modelPermissions[] = self::makeModelPermission(Ability::from($abilityName));
        }

        return $modelPermissions;
    }
}
