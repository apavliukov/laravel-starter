<?php

declare(strict_types=1);

// @formatter:off
// phpcs:ignoreFile
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */

namespace App\Models{
    /**
     * The tenant and the Spatie permissions team. Membership is a role held in
     * this team (`members()` reads model_has_roles); a user may belong to several
     * organizations. No membership column or pivot exists besides that.
     *
     * @property int $id
     * @property string $name
     * @property \Illuminate\Support\Carbon|null $created_at
     * @property \Illuminate\Support\Carbon|null $updated_at
     * @property \Illuminate\Support\Carbon|null $deleted_at
     * @property-read \Illuminate\Database\Eloquent\Collection<int, User> $members
     * @property-read int|null $members_count
     *
     * @method static \Database\Factories\OrganizationFactory factory($count = null, $state = [])
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization newModelQuery()
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization newQuery()
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization onlyTrashed()
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization query()
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization search(string $term)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization whereCreatedAt($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization whereDeletedAt($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization whereId($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization whereName($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization whereUpdatedAt($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization withTrashed(bool $withTrashed = true)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization withoutTrashed()
     *
     * @mixin \Eloquent
     */
    #[\AllowDynamicProperties]
    final class IdeHelperOrganization {}
}

namespace App\Models{
    /**
     * Membership and identity come from the authorization package: `tenants()`
     * lists the organizations the user holds a role in (model_has_roles.team_id),
     * `app_role` / `is_platform_admin` read roles team-agnostically. Never
     * hasRole() — Spatie only sees the active team.
     *
     * @property int $id
     * @property string $first_name
     * @property string $last_name
     * @property string $email
     * @property \Illuminate\Support\Carbon|null $email_verified_at
     * @property string $password
     * @property string|null $remember_token
     * @property \Illuminate\Support\Carbon|null $created_at
     * @property \Illuminate\Support\Carbon|null $updated_at
     * @property \Illuminate\Support\Carbon|null $deleted_at
     * @property int|null $organization_id
     * @property-read \AlexPavliukov\Authorization\Contracts\AuthorizationRole|null $app_role
     * @property-read string $initials
     * @property-read bool $is_platform_admin
     * @property-read string $name
     * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
     * @property-read int|null $notifications_count
     * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Permission\Models\Permission> $permissions
     * @property-read int|null $permissions_count
     * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Permission\Models\Role> $roles
     * @property-read int|null $roles_count
     * @property-read \Illuminate\Database\Eloquent\Collection<int, Organization> $teams
     * @property-read int|null $teams_count
     * @property-read \Illuminate\Database\Eloquent\Collection<int, Organization> $tenants
     * @property-read int|null $tenants_count
     *
     * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
     * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
     * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
     * @method static \Illuminate\Database\Eloquent\Builder<static>|User onlyTrashed()
     * @method static \Illuminate\Database\Eloquent\Builder<static>|User permission($permissions, bool $without = false)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
     * @method static \Illuminate\Database\Eloquent\Builder<static>|User role($roles, ?string $guard = null, bool $without = false)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|User search(string $term)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|User team($teams, bool $without = false)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereDeletedAt($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereFirstName($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereHasGlobalRole(\BackedEnum|string $role)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereHasRole(\BackedEnum|string $role)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereHasRoleInTeam(\BackedEnum|string $role, string|int $teamId)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereLastName($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereOrganizationId($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|User withRole(\App\Enums\Policies\Role $role)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|User withTrashed(bool $withTrashed = true)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|User withoutPermission($permissions)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|User withoutRole($roles, ?string $guard = null)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|User withoutTeam($teams)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|User withoutTrashed()
     *
     * @mixin \Eloquent
     */
    #[\AllowDynamicProperties]
    final class IdeHelperUser {}
}
