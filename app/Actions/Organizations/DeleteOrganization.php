<?php

declare(strict_types=1);

namespace App\Actions\Organizations;

use App\Models\Organization;
use DomainException;

final readonly class DeleteOrganization
{
    public function __invoke(Organization $organization): void
    {
        throw_if($organization->members()->exists(), DomainException::class, __('An organization with members cannot be deleted.'));

        $organization->delete();
    }
}
