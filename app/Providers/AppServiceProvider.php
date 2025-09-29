<?php

declare(strict_types=1);

namespace App\Providers;

use App\Traits\Models\HasRelationTypeName;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
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
    }

    private function enableModelStrictMode(): void
    {
        Model::shouldBeStrict(false); // TODO change in next phases of development

        if ($this->app->isProduction()) {
            Model::handleLazyLoadingViolationUsing(static function ($model, $relation): void {
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
            //
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
        LogViewer::auth(static fn (Request $request): bool => $request->user()?->hasVerifiedEmail() ?? false);
    }
}
