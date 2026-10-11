<?php

declare(strict_types=1);

namespace App\Enums\Policies;

/** Organization abilities beyond the standard CRUD set; values are policy method names. */
enum OrganizationAbility: string
{
    case TRANSFER_OWNERSHIP = 'transferOwnership';
}
