<?php

declare(strict_types=1);

namespace App\Authorization\Enums;

use App\Traits\Enums\HasValues;

/**
 * System abilities are registered as standalone Gate::define() calls with no
 * model attached. They are intentionally separate from the resource Ability
 * enum so they never generate model permissions via HasPolicy.
 */
enum SystemAbility: string
{
    use HasValues;

    case ACCESS_PLATFORM_ADMIN = 'accessPlatformAdmin';
}
