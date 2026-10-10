<?php

declare(strict_types=1);

namespace App\Actions\Organizations;

use App\Dto\Organizations\CreateOrganizationInput;
use App\Models\Organization;

final readonly class CreateOrganization
{
    public function __invoke(CreateOrganizationInput $input): Organization
    {
        return Organization::query()->create([
            'name' => $input->name,
        ]);
    }
}
