<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\Models\HasRelationTypeName;
use BackedEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission as SpatiePermission;

/**
 * @mixin IdeHelperPermission
 */
final class Permission extends SpatiePermission
{
    use HasFactory;
    use HasRelationTypeName;

    /**
     * Build a human-readable permission name from an ability + model.
     *
     * Ability values are camelCase so `Gate::authorize(Ability::VIEW_ANY, $model)`
     * resolves to the matching policy method directly. For DB-stored permission
     * names we convert back to space-separated — `viewAny` + `leads` -> "view any leads".
     *
     * Accepts any string-backed enum so per-model ability enums (e.g. UserAbility)
     * flow through the same pipeline as the global Ability enum.
     */
    public static function makeNameFromAbility(BackedEnum $ability, Model|string $model): string
    {
        $modelTable = $model instanceof Model ? $model->getTable() : get_model_table($model);

        return sprintf('%s %s', Str::snake($ability->value, ' '), $modelTable);
    }
}
