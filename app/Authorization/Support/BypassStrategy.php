<?php

declare(strict_types=1);

namespace App\Authorization\Support;

use App\Authorization\AuthorizationManager;
use BackedEnum;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Gate;

final class BypassStrategy
{
    public static function register(AuthorizationManager $manager): void
    {
        Gate::before(static function (Authenticatable $user) use ($manager): ?bool {
            $superAdminRoleNames = array_map(
                static fn (BackedEnum $role): string => (string) $role->value,
                $manager->superAdminRoles(),
            );

            return $user->hasAnyRole($superAdminRoleNames) ? true : null;
        });
    }
}
