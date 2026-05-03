<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\Platform\DashboardController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', DashboardController::class)
    ->name('dashboard');

Route::prefix('users')
    ->name('users.')
    ->controller(UserController::class)
    ->group(function (): void {
        Route::get('/', 'index')
            ->name('index');
    });
