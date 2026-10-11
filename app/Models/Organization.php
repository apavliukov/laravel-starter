<?php

declare(strict_types=1);

namespace App\Models;

use AlexPavliukov\Authorization\Concerns\HasPolicy;
use AlexPavliukov\Authorization\Tenancy\Concerns\HasMembers;
use App\Enums\Policies\OrganizationAbility;
use App\Traits\Models\HasRelationTypeName;
use Database\Factories\OrganizationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * The tenant and the Spatie permissions team. Membership is a role held in
 * this team (`members()` reads model_has_roles); a user may belong to several
 * organizations. No membership column or pivot exists besides that.
 *
 * Ownership is `owner_id` and nothing else; it is not fillable and changes only
 * through `TenantMembership::transferOwnership()`.
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
    use HasMembers;
    use HasPolicy;
    use HasRelationTypeName;
    use SoftDeletes;

    /** @return array<int, OrganizationAbility> */
    public static function getCustomAbilities(): array
    {
        return OrganizationAbility::cases();
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
