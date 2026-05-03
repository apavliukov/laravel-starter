<?php

declare(strict_types=1);

namespace App\Enums\Policies;

use App\Traits\Enums\HasValues;

/**
 * Ability values match Laravel policy method names directly (camelCase) so
 * `Gate::authorize(Ability::VIEW_ANY, $model)` resolves to the expected
 * `viewAny` policy method without any conversion at call sites.
 *
 * Permission name generation (Permission::makeNameFromAbility) converts the
 * camelCase value back to a space-separated form so DB-stored permission
 * names stay readable ("view any leads", "force delete users").
 */
enum Ability: string
{
    use HasValues;

    // Resource abilities — paired with a model class via Gate::authorize($ability, $model)
    case MANAGE_ANY = 'manageAny';
    case VIEW_ANY = 'viewAny';
    case VIEW = 'view';
    case CREATE = 'create';
    case UPDATE = 'update';
    case DELETE = 'delete';
    case RESTORE = 'restore';
    case FORCE_DELETE = 'forceDelete';

    // System abilities — singleton Gate checks with no model
    case ACCESS_PLATFORM_ADMIN = 'accessPlatformAdmin';
}
