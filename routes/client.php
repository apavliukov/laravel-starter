<?php

declare(strict_types=1);

use App\Http\Controllers\Client\DashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])
    ->name('client.')
    ->group(function (): void {
        Route::get('/', DashboardController::class)
            ->name('dashboard');
    });
