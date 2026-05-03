<?php

declare(strict_types=1);

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\Policies\Role as RoleEnum;
use App\Traits\Models\HasPolicy;
use App\Traits\Models\HasRelationTypeName;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Spatie\Permission\Traits\HasRoles;

/**
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
    /** @use HasFactory<UserFactory> */
    use HasFactory;
    use HasPolicy;
    use HasRelationTypeName;
    use HasRoles;
    use Notifiable;
    use SoftDeletes;

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
     * The user's app role enum, derived from Spatie's role assignment.
     *
     * Throws if the user has no role assigned or if the assigned name isn't
     * one of the App\Enums\Policies\Role cases — both are broken-state
     * conditions that should surface loudly, not be papered over by a default.
     *
     * Queries the relation directly (not `getRoleNames()` / `loadMissing`)
     * so a fresh registrar team-id context is honored even if the relation
     * was lazy-loaded earlier under a different team scope.
     */
    protected function appRole(): Attribute
    {
        return new Attribute(
            get: function (): RoleEnum {
                /** @var string $firstRole */
                $firstRole = $this->getRoleNames()->first();

                return RoleEnum::from($firstRole);
            },
        );
    }

    protected function is_admin(): Attribute
    {
        return new Attribute(
            get: fn (): bool => $this->hasRole(RoleEnum::ADMIN),
        );
    }

    protected function is_member(): Attribute
    {
        return new Attribute(
            get: fn (): bool => $this->hasRole(RoleEnum::MEMBER),
        );
    }

    protected function initials(): Attribute
    {
        return new Attribute(
            get: fn (): string => Str::substr($this->first_name, 0, 1).Str::substr($this->last_name, 0, 1),
        );
    }
}
