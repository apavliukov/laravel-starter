<?php

declare(strict_types=1);

use App\Providers\AppServiceProvider;
use App\Providers\AuthorizationServiceProvider;
use App\Providers\HorizonServiceProvider;

return [
    AppServiceProvider::class,
    AuthorizationServiceProvider::class,
    HorizonServiceProvider::class,
];
