<?php

declare(strict_types=1);

namespace App\Helpers\Policies;

use App\Enums\Policies\Role as RoleEnum;
use App\Models\User;

/**
 * Central registry of permissions and their role assignments used by seeders.
 */
final readonly class PermissionRegistry
{
    public function allPermissions(): array
    {
        return array_merge(
            User::makeAllPermissions(),
        );
    }

    public function forRole(RoleEnum $role): array
    {
        return match ($role) {
            RoleEnum::ADMIN => $this->adminPermissions(),
            RoleEnum::MEMBER => $this->memberPermissions(),
        };
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
