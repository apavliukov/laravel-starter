<?php

declare(strict_types=1);

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;

abstract readonly class BaseListener implements ShouldQueue
{
    //
}
