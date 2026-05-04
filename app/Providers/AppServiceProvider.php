<?php

declare(strict_types=1);

namespace App\Providers;

use App\Enums\Policies\Ability;
use App\Models\Permission;
use App\Models\User;
use App\Traits\Models\HasRelationTypeName;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Opcodes\LogViewer\Facades\LogViewer;
use Sentry\EventHint;
use Sentry\Severity;

use function Sentry\captureMessage;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerTelescopeProvider();
    }

    public function boot(): void
    {
        $this->enforceMorphMap();
        $this->enableModelStrictMode();
        $this->registerLogViewerAuth();
        $this->registerAdminAccessGate();
        $this->registerCarbonMacros();
    }

    /**
     * Admin bypasses via the Gate::before callback below; everyone else
     * falls through to this `false` and gets 403.
     *
     * The ability name reflects the area's purpose — this is the *platform
     * admin* surface, not the platform itself
     */
    private function registerAdminAccessGate(): void
    {
        Gate::define(Ability::ACCESS_PLATFORM_ADMIN->value, static fn (): bool => false);
        Gate::before(static fn(User $user): ?bool => $user->is_admin ? true : null);
    }

    private function registerCarbonMacros(): void
    {
        Date::macro('smartDate', fn (): string => $this->isoFormat($this->isCurrentYear() ? 'D MMMM' : 'D MMMM YYYY'));
        Date::macro('smartDateTime', fn (): string => $this->isoFormat('D MMMM YYYY HH:mm'));
    }

    private function enableModelStrictMode(): void
    {
        Model::shouldBeStrict();

        if ($this->app->isProduction()) {
            Model::handleLazyLoadingViolationUsing(static function ($model, string $relation): void {
                $fullTrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, limit: 6);
                $trace = array_pop($fullTrace);
                $fileParts = explode('/', $trace['file']);
                $file = array_pop($fileParts);
                $class = $model::class;

                $warning = sprintf('Attempted to lazy load [%s] on [line:%d] in [%s] for model [%s].', $relation, $trace['line'], $file, $class);

                info($warning);

                captureMessage(
                    $warning,
                    Severity::warning(),
                    EventHint::fromArray([
                        'relation' => $relation,
                        'model' => $model->toArray(),
                        'trace' => $fullTrace,
                    ]),
                );
            });
        }
    }

    private function enforceMorphMap(): void
    {
        $models = [
            User::class,
            Permission::class,
        ];

        $morphMap = array_reduce($models, static function (array $result, string $model): array {
            /** @var HasRelationTypeName $model */
            $result[$model::getRelationTypeName()] = $model;

            return $result;
        }, []);

        Relation::enforceMorphMap($morphMap);
    }

    private function registerTelescopeProvider(): void
    {
        if ($this->app->environment('local')) {
            $this->app->register(TelescopeServiceProvider::class);
        }
    }

    private function registerLogViewerAuth(): void
    {
        LogViewer::auth(static fn (Request $request): bool => $request->user()?->is_admin ?? false);
    }
}
