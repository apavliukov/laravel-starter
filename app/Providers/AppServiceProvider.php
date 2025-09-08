<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\ServiceProvider;
use Opcodes\LogViewer\Facades\LogViewer;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerTelescopeProvider();
    }

    public function boot(): void
    {
        $this->registerLogViewerAuth();
    }

    private function registerTelescopeProvider(): void
    {
        if ($this->app->environment('local')) {
            $this->app->register(TelescopeServiceProvider::class);
        }
    }

    private function registerLogViewerAuth(): void
    {
        LogViewer::auth(static fn (User $user): bool => in_array($user->email, [
            //
        ]));
    }
}
