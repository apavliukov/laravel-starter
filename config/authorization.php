<?php

declare(strict_types=1);

use App\Enums\Policies\Role;
use App\Models\Organization;

return [

    /*
    |--------------------------------------------------------------------------
    | Tenancy
    |--------------------------------------------------------------------------
    |
    | Leave `model` null for apps without tenants. When set, the tenant is also
    | the Spatie permissions team — `permission.teams` must be true and
    | `permission.models.team` should point at the same model.
    |
    */

    'tenancy' => [
        'model' => Organization::class,

        // Column on tenant-owned tables.
        'foreign_key' => 'organization_id',

        // Membership = a role in the organization's team (model_has_roles.team_id).
        // The active organization is the session choice (validated against
        // membership), else the user's sole organization, else none.
        'session_key' => 'current_organization_id',

        // Throw MissingTenantContextException when a tenant-owned query runs with
        // no tenant and no bypass. Off ⇒ the query silently yields nothing.
        'strict' => env('APP_ENV') !== 'production',

        // Route name EnsureTenantSelected redirects to when no tenant is active.
        'missing_tenant_redirect' => null,

        // Ownership: organizations.owner_id (NOT NULL, FK users, restrictOnDelete) is
        // the only record of the owner; the package derives the owner's OWNER role
        // row from it and refuses to edit or delete the owner outside
        // TenantMembership::transferOwnership().
        'owner_key' => 'owner_id',
        'owner_role' => Role::OWNER,

        // A user may belong to several organizations; 'single' restricts to one.
        'memberships' => 'multiple',
    ],

    /*
    |--------------------------------------------------------------------------
    | Areas
    |--------------------------------------------------------------------------
    |
    | area name => route-name pattern (Str::is wildcards). CurrentArea resolves
    | the area of the current route from these; a session view mode is the
    | fallback. Order matters: the first match wins, the first entry is the
    | default.
    |
    */

    'areas' => [
        'platform' => 'admin.platform.*',
        'member' => 'admin.member.*',
    ],

];
