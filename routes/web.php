<?php

declare(strict_types=1);

use App\Livewire\Common\Settings\Appearance;
use App\Livewire\Common\Settings\Password;
use App\Livewire\Common\Settings\Profile;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function (): void {
    Route::prefix('settings')
        ->name('settings.')
        ->group(function (): void {
            Route::redirect('/', '/settings/profile');

            Route::get('/profile', Profile::class)
                ->name('profile');
            Route::get('/password', Password::class)
                ->name('password');
            Route::get('/appearance', Appearance::class)
                ->name('appearance');
        });
});

require __DIR__.'/admin.php';
require __DIR__.'/client.php';
require __DIR__.'/auth.php';
