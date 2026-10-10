<?php

declare(strict_types=1);

namespace App\Support\Users;

use AlexPavliukov\Authorization\Authorization;
use AlexPavliukov\Authorization\Tenancy\TenantMembership;
use App\Enums\Policies\Role;
use App\Models\User;
use InvalidArgumentException;
use Spatie\Permission\PermissionRegistrar;

/**
 * A user holds exactly one role: ADMIN globally (team_id NULL), MEMBER inside
 * the organization's team. Spatie's syncRoles() only sees the active team, so
 * every previous assignment — global and per-organization — is cleared first.
 */
trait SyncsUserRole
{
    protected function syncRole(User $user, Role $role): void
    {
        $this->detachAllRoles($user);

        if ($role->isSuperAdmin()) {
            Authorization::withTeam(null, static fn (): User => $user->assignRole($role));
            Authorization::forgetUserRoles($user);

            return;
        }

        throw_if($user->organization_id === null, InvalidArgumentException::class, "Role [{$role->value}] requires an organization.");

        resolve(TenantMembership::class)->add($user, $user->organization_id, $role);
    }

    /** Same trick Spatie uses in HasRoles::bootHasRoles(): the pivot team filter only lifts with teams off. */
    private function detachAllRoles(User $user): void
    {
        $registrar = resolve(PermissionRegistrar::class);
        $teams = $registrar->teams;
        $registrar->teams = false;

        try {
            $user->roles()->detach();
        } finally {
            $registrar->teams = $teams;
        }

        Authorization::forgetUserRoles($user);
    }
}
