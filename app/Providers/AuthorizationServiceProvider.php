<?php

declare(strict_types=1);

namespace App\Providers;

use AlexPavliukov\Authorization\Authorization;
use App\Enums\Policies\Role;
use App\Enums\Policies\SystemAbility;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

final class AuthorizationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Authorization::useRoleEnum(Role::class);

        Authorization::authorizableModels([
            User::class,
        ]);

        Gate::define(SystemAbility::ACCESS_PLATFORM_ADMIN, static fn (): bool => false);
    }
}
