<?php

declare(strict_types=1);

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use AlexPavliukov\Authorization\Authorization;
use AlexPavliukov\Authorization\Concerns\HasPolicy;
use AlexPavliukov\Authorization\Concerns\HasTeamAwareRoles;
use App\Enums\Policies\Role as RoleEnum;
use App\Traits\Models\HasRelationTypeName;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use RuntimeException;
use Spatie\Permission\Traits\HasRoles;

/**
 * @mixin IdeHelperUser
 */
#[Fillable([
    'organization_id',
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
    /** @use HasFactory<UserFactory> */
    use HasFactory;
    use HasPolicy;
    use HasRelationTypeName;
    use HasRoles;
    use HasTeamAwareRoles;
    use Notifiable;
    use SoftDeletes;

    /** @return BelongsTo<Organization, $this> */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
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

    /**
     * The user's app role enum, read team-agnostically: ADMIN is a global
     * assignment, MEMBER lives inside the organization's team, and Spatie's own
     * hasRole() only sees the active team. Throws if the user has no role —
     * a broken state that should surface loudly, not be papered over.
     */
    protected function appRole(): Attribute
    {
        return new Attribute(
            get: function (): RoleEnum {
                $role = Authorization::primaryRole($this);

                throw_unless($role instanceof RoleEnum, RuntimeException::class, "User [{$this->id}] has no role assigned.");

                return $role;
            },
        );
    }

    protected function isAdmin(): Attribute
    {
        return new Attribute(
            get: fn (): bool => Authorization::userHasGlobalRole($this, RoleEnum::ADMIN),
        );
    }

    protected function isMember(): Attribute
    {
        return new Attribute(
            get: fn (): bool => Authorization::userHasRole($this, RoleEnum::MEMBER),
        );
    }

    protected function initials(): Attribute
    {
        return new Attribute(
            get: fn (): string => Str::substr($this->first_name, 0, 1).Str::substr($this->last_name, 0, 1),
        );
    }
}
