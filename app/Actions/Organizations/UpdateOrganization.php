<?php

declare(strict_types=1);

namespace App\Actions\Organizations;

use App\Dto\Organizations\UpdateOrganizationInput;
use App\Models\Organization;

final readonly class UpdateOrganization
{
    public function __invoke(Organization $organization, UpdateOrganizationInput $input): Organization
    {
        $organization->update([
            'name' => $input->name,
        ]);

        return $organization->refresh();
    }
}
