<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\Client\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', DashboardController::class)
    ->name('dashboard');
