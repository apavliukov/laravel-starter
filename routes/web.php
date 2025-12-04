<?php

declare(strict_types=1);

use App\Http\Controllers\Web\PageController;
use Illuminate\Support\Facades\Route;

require base_path('routes/auth.php');
require base_path('routes/admin.php');

Route::get('/', [PageController::class, 'home'])
    ->name('home');
