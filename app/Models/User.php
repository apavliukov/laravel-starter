<?php

declare(strict_types=1);

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use AlexPavliukov\Authorization\Concerns\HasAppRole;
use AlexPavliukov\Authorization\Concerns\HasPolicy;
use AlexPavliukov\Authorization\Concerns\HasTeamAwareRoles;
use AlexPavliukov\Authorization\Tenancy\Concerns\HasTenantMemberships;
use App\Enums\Policies\Role as RoleEnum;
use App\Traits\Models\HasRelationTypeName;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Spatie\Permission\Traits\HasRoles;

/**
 * Membership and identity come from the authorization package: `tenants()`
 * lists the organizations the user holds a role in (model_has_roles.team_id),
 * `app_role` / `is_platform_admin` read roles team-agnostically. Never
 * hasRole() — Spatie only sees the active team.
 *
 * @mixin IdeHelperUser
 */
#[Fillable([
    'first_name',
    'last_name',
    'email',
    'password',
])]
#[Hidden([
    'password',
    'remember_token',
])]
final class User extends Authenticatable
{
    use HasAppRole;
    /** @use HasFactory<UserFactory> */
    use HasFactory;
    use HasPolicy;
    use HasRelationTypeName;
    use HasRoles;
    use HasTeamAwareRoles;
    use HasTenantMemberships;
    use Notifiable;
    use SoftDeletes;

    /** @return HasMany<Organization, $this> */
    public function ownedOrganizations(): HasMany
    {
        return $this->hasMany(Organization::class, 'owner_id');
    }

    #[Scope]
    protected function search(Builder $query, string $term): void
    {
        if ($term === '') {
            return;
        }

        $like = '%'.$term.'%';

        $query->where(function (Builder $q) use ($like): void {
            $q->where('first_name', 'ilike', $like)
                ->orWhere('last_name', 'ilike', $like)
                ->orWhere('email', 'ilike', $like);
        });
    }

    #[Scope]
    protected function withRole(Builder $query, RoleEnum $role): void
    {
        $query->whereHasRole($role);
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    protected function name(): Attribute
    {
        return new Attribute(
            get: fn (): string => "$this->first_name $this->last_name",
        );
    }

    protected function initials(): Attribute
    {
        return new Attribute(
            get: fn (): string => Str::substr($this->first_name, 0, 1).Str::substr($this->last_name, 0, 1),
        );
    }
}
