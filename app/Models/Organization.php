<?php

declare(strict_types=1);

namespace App\Models;

use AlexPavliukov\Authorization\Concerns\HasPolicy;
use App\Traits\Models\HasRelationTypeName;
use Database\Factories\OrganizationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * The tenant: every member belongs to exactly one organization, and the
 * organization is also the Spatie permissions team that scopes member roles.
 *
 * @mixin IdeHelperOrganization
 */
#[Fillable([
    'name',
])]
final class Organization extends Model
{
    /** @use HasFactory<OrganizationFactory> */
    use HasFactory;
    use HasPolicy;
    use HasRelationTypeName;
    use SoftDeletes;

    /** @return HasMany<User, $this> */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    #[Scope]
    protected function search(Builder $query, string $term): void
    {
        if ($term === '') {
            return;
        }

        $query->where('name', 'ilike', "%{$term}%");
    }
}
