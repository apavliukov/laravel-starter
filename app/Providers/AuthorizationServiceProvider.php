<?php

declare(strict_types=1);

namespace App\Providers;

use App\Authorization\Authorization;
use App\Authorization\AuthorizationManager;
use App\Authorization\Console\MakePolicyCommand;
use App\Authorization\Contracts\TeamResolver;
use App\Authorization\Enums\SystemAbility;
use App\Authorization\Support\BypassStrategy;
use App\Authorization\Teams\DefaultTeamResolver;
use App\Authorization\Teams\SetPermissionsTeam;
use App\Enums\Policies\Role;
use App\Models\User;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

final class AuthorizationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AuthorizationManager::class);
        $this->app->bind(TeamResolver::class, DefaultTeamResolver::class);
    }

    public function boot(): void
    {
        Authorization::useRoleEnum(Role::class);

        Authorization::authorizableModels([
            User::class,
        ]);

        BypassStrategy::register($this->app->make(AuthorizationManager::class));

        Gate::define(SystemAbility::ACCESS_PLATFORM_ADMIN, static fn (): bool => false);

        if (config('permission.teams') === true) {
            $this->app->make(Router::class)
                ->pushMiddlewareToGroup('web', SetPermissionsTeam::class);
        }

        if ($this->app->runningInConsole()) {
            $this->commands([MakePolicyCommand::class]);
        }
    }
}
