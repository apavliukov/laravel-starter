<?php

declare(strict_types=1);

namespace App\Actions\Organizations;

use AlexPavliukov\Authorization\Tenancy\TenantMembership;
use App\Dto\Organizations\CreateOrganizationInput;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/** An organization is born with its owner: the column is NOT NULL and the OWNER role row is derived from it. */
final readonly class CreateOrganization
{
    public function __construct(private TenantMembership $membership) {}

    public function __invoke(CreateOrganizationInput $input): Organization
    {
        return DB::transaction(function () use ($input): Organization {
            $owner = User::query()->where('email', $input->ownerEmail)->firstOrFail();

            $organization = new Organization(['name' => $input->name]);
            $organization->owner()->associate($owner);
            $organization->save();

            $this->membership->transferOwnership($organization, $owner);

            return $organization;
        });
    }
}
