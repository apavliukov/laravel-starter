<?php

declare(strict_types=1);

namespace App\Providers;

use AlexPavliukov\Authorization\Authorization;
use App\Enums\Policies\Role;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\ServiceProvider;

/**
 * Tenancy itself is configured in config/authorization.php (Organization is the
 * tenant and the Spatie team; membership is a role in model_has_roles). The
 * package registers the deny-all SystemAbility::ACCESS_PLATFORM_ADMIN gate; only
 * the super-admin bypass can satisfy it.
 */
final class AuthorizationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Authorization::useRoleEnum(Role::class);

        Authorization::authorizableModels([
            Organization::class,
            User::class,
        ]);
    }
}
