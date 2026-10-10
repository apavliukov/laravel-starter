<?php

declare(strict_types=1);

namespace App\Policies;

use AlexPavliukov\Authorization\AbstractPolicy;
use App\Models\Organization;

final readonly class OrganizationPolicy extends AbstractPolicy
{
    protected function getModelClass(): string
    {
        return Organization::class;
    }
}
