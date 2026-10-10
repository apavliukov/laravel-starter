<?php

declare(strict_types=1);

namespace App\Actions\Organizations;

use App\Models\Organization;

final readonly class DeleteOrganization
{
    public function __invoke(Organization $organization): void
    {
        $organization->delete();
    }
}
