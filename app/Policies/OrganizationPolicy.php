<?php

declare(strict_types=1);

namespace App\Policies;

use AlexPavliukov\Authorization\Support\ScalarKey;
use AlexPavliukov\Authorization\TenantScopedPolicy;
use App\Enums\Policies\OrganizationAbility;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * An organization is its own tenant: OWNER's `view` / `update` / `transferOwnership`
 * grants only apply to the organization that is currently active, never to another
 * one the same role name exists in.
 */
final readonly class OrganizationPolicy extends TenantScopedPolicy
{
    public function transferOwnership(User $user, Organization $organization): bool
    {
        return $this->ownsModel($user, $organization) && $this->userCan($user, OrganizationAbility::TRANSFER_OWNERSHIP, $organization);
    }

    protected function getModelClass(): string
    {
        return Organization::class;
    }

    protected function tenantKey(Model $model): int|string|null
    {
        return ScalarKey::normalize($model->getKey());
    }
}
