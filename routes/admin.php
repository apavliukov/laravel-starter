<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\Common\SettingsController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin');

Route::middleware(['auth', 'verified'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function (): void {
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
            ->group(base_path('routes/admin/platform.php'));

        Route::name('member.')
            ->group(base_path('routes/admin/member.php'));
    });
