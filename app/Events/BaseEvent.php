<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

abstract readonly class BaseEvent
{
    use Dispatchable;
    use SerializesModels;
}
