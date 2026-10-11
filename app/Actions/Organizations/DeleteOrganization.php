<?php

declare(strict_types=1);

namespace App\Actions\Organizations;

use App\Models\Organization;

/** Archive, never destroy; what happens to the organization's own records is a per-project decision. */
final readonly class DeleteOrganization
{
    public function __invoke(Organization $organization): void
    {
        $organization->delete();
    }
}
