<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Role;
use App\Models\User;

/**
 * Aggregates per-role permission lists used by the RoleSeeder.
 *
 * SuperAdmin is intentionally NOT modelled here — its authorization runs through
 * Gate::before in AppServiceProvider and short-circuits all permission checks.
 * Per-role permission methods (getAdminPermissions, getTeamLeadPermissions, etc.)
 * are added in Phase 2 alongside the granular permission seeding.
 */
abstract readonly class Permissions
{
    final public static function getAllPermissions(): array
    {
        return array_merge(
            User::makeAllPermissions(),
        );
    }

    final public static function getMemberPermissions(): array
    {
        return [];
    }

    final public static function getPermissionsByRole(Role $role): array
    {
        $roleName = str_replace(['_', ' '], '', ucwords($role->name, '_ '));
        $permissionsMethodByRoleName = sprintf('get%sPermissions', $roleName);

        if (method_exists(self::class, $permissionsMethodByRoleName)) {
            return self::$permissionsMethodByRoleName();
        }

        return [];
    }
}
