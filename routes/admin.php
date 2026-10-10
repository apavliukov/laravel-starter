<?php

declare(strict_types=1);

use AlexPavliukov\Authorization\Tenancy\EnsurePlatformArea;
use AlexPavliukov\Authorization\Tenancy\EnsureTenantSelected;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\SettingsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])
    ->name('admin.')
    ->group(function (): void {
        Route::get('/dashboard', DashboardController::class)
            ->name('dashboard');

        Route::prefix('settings')
            ->name('settings.')
            ->controller(SettingsController::class)
            ->group(function (): void {
                Route::redirect('/', '/settings/profile');

                Route::get('/profile', 'profile')
                    ->name('profile');
                Route::get('/password', 'password')
                    ->name('password');
                Route::get('/appearance', 'appearance')
                    ->name('appearance');
            });

        Route::prefix('platform')
            ->name('platform.')
            ->middleware(EnsurePlatformArea::class)
            ->group(base_path('routes/admin/platform.php'));

        Route::prefix('admin')
            ->name('member.')
            ->middleware(EnsureTenantSelected::class)
            ->group(base_path('routes/admin/member.php'));
    });
