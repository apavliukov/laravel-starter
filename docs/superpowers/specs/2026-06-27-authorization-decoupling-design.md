# Authorization Decoupling — Design

- **Date:** 2026-06-27
- **Status:** Approved — open decisions resolved 2026-06-27 (see §10)
- **Scope:** This project only. Package-ready decoupling of the authorization
  module, performed in-place under `app/Authorization/`. No package is created
  yet — this project becomes the reference implementation that a future internal
  Composer package is lifted from.

---

## 1. Context & Goal

The authorization module (Spatie-permission-based policies, abilities, roles,
a permission registry, and an admin bypass) is currently copy-pasted from
project to project. Projects have drifted apart in two places: the admin-bypass
strategy (`is_admin` attribute here vs a `SUPER_ADMIN` role in the `meg`
project) and team scoping (none here, Spatie native teams in `meg`, a custom
column approach in a third project).

**Goal of this pass:** decouple the authorization code so the app-specific parts
(concrete policies, the role set, the user model) are clearly separated from the
generic, reusable core, and the generic core sits in a single subtree that can
later be lifted into a package almost unchanged. Unify the two divergence points
on a single canonical approach.

**This pass does NOT create a package.** It restructures in-place, keeps
everything in the `App\` namespace, and is fully covered by tests.

---

## 2. Locked-in Decisions

These came out of the brainstorming session and are fixed for this design:

1. **Teams = single backend (Spatie native).** When teams are enabled, the only
   supported backend is Spatie native teams. The custom-column project will be
   migrated to Spatie teams separately (out of scope here). The teams on/off flag
   is **read from Spatie** (`config('permission.teams')`) — never duplicated.

2. **Admin bypass = role-based.** `Gate::before` short-circuits for users holding
   a role marked as super-admin. The role is identified through a typed contract
   method on the role enum (`isSuperAdmin(): bool`), **not** a hardcoded role
   name and **not** a boolean `is_admin` column.

3. **Package-ready decoupling, in-place, no package yet.** Contracts + a manager
   + a publishable-style provider, all under `app/Authorization/`, with tests.

4. **No config file.** App-specific wiring lives in a service provider (the
   Telescope/Horizon pattern), not a `config/*.php` array. Spatie's own
   `config/permission.php` remains the single source of truth for everything it
   already owns (teams flag, team foreign key, model classes, tables, cache).

5. **Hybrid contract/config boundary.** Config (Spatie's) for storage mechanics;
   typed contracts for behavior that genuinely varies (role semantics, team
   resolution); a fluent manager for app declarations (role enum, authorizable
   models, team resolver).

---

## 3. Non-Goals (YAGNI)

- No package, no `packages/` path repo, no Composer wiring in this pass.
- No `AuthorizableUser` contract — the core relies on Spatie's `HasRoles` trait
  (a Composer dependency of the future package) plus the framework
  `Authenticatable`. A custom user interface adds no value.
- No configurable permission-name format — all projects share one format today.
  The format stays inside `PermissionRegistry` and can be promoted to a contract
  later if a project ever needs to diverge.
- No migration of role *names* / data is required by the mechanism (see §6.4).
- Teams remain **off** in this project. The teams-on code path is built and unit
  tested, but this project keeps `config('permission.teams') === false`.

---

## 4. Architecture Overview

```
┌─────────────────────────────────────────────────────────────┐
│ App-specific (per project)                                    │
│   Role enum  (implements AuthorizationRole)                   │
│   RolePresentation (label/layout/badgeColor — split out)      │
│   UserPolicy, PostPolicy, … (extend AbstractPolicy)           │
│   User model (HasPolicy + Spatie HasRoles)                    │
│   UserAbility (custom abilities)                              │
│   AuthorizationServiceProvider (declares enum, models,        │
│       team resolver via the manager)                          │
└───────────────┬─────────────────────────────────────────────┘
                │ declares via
┌───────────────▼─────────────────────────────────────────────┐
│ Generic core  →  app/Authorization/  (future package)        │
│   Contracts\AuthorizationRole                                 │
│   Contracts\TeamResolver                                      │
│   AuthorizationManager (+ Authorization facade)              │
│   AbstractPolicy                                             │
│   Concerns\HasPolicy                                         │
│   Enums\Ability, Enums\SystemAbility                         │
│   PermissionRegistry                                         │
│   Teams\DefaultTeamResolver, Teams\SetPermissionsTeam mw     │
│   Support\BypassStrategy (Gate::before logic)               │
│   Console\MakePolicyCommand (+ stub)                        │
│   Database\PermissionSync (seeding source)                  │
└───────────────┬─────────────────────────────────────────────┘
                │ depends on
┌───────────────▼─────────────────────────────────────────────┐
│ spatie/laravel-permission  (config/permission.php)           │
│   roles/permissions storage, teams flag, team_foreign_key    │
└──────────────────────────────────────────────────────────────┘
```

---

## 5. Components

### 5.1 Contracts

**`App\Authorization\Contracts\AuthorizationRole`** — implemented by the app's
role enum. This is the typed seam that lets the generic core reason about an
app-specific enum.

```php
<?php

declare(strict_types=1);

namespace App\Authorization\Contracts;

interface AuthorizationRole
{
    /** Whether holders of this role bypass every gate check via Gate::before. */
    public function isSuperAdmin(): bool;

    /**
     * Permission names granted to this role, consumed by the seeder.
     *
     * @return array<int, string>
     */
    public function permissions(): array;
}
```

**`App\Authorization\Contracts\TeamResolver`** — resolves the current team id for
a request. Only consulted when Spatie teams are enabled. Spatie consumes the id
(via `setPermissionsTeamId()`); resolving *where it comes from* is our concern.

```php
<?php

declare(strict_types=1);

namespace App\Authorization\Contracts;

use Illuminate\Http\Request;

interface TeamResolver
{
    public function resolve(Request $request): int|string|null;
}
```

### 5.2 AuthorizationManager + facade

A singleton holding the app declarations. The generic core reads from it
instead of hardcoding `User::class` / `Role::class`.

```php
<?php

declare(strict_types=1);

namespace App\Authorization;

use App\Authorization\Contracts\AuthorizationRole;
use BackedEnum;
use RuntimeException;

final class AuthorizationManager
{
    /** @var class-string<AuthorizationRole&BackedEnum>|null */
    private ?string $roleEnum = null;

    /** @var array<int, class-string> */
    private array $models = [];

    /** @param class-string<AuthorizationRole&BackedEnum> $roleEnum */
    public function useRoleEnum(string $roleEnum): void
    {
        $this->roleEnum = $roleEnum;
    }

    /** @return class-string<AuthorizationRole&BackedEnum> */
    public function roleEnum(): string
    {
        return $this->roleEnum ?? throw new RuntimeException('Role enum not configured.');
    }

    /** @param array<int, class-string> $models */
    public function authorizableModels(array $models): void
    {
        $this->models = $models;
    }

    /** @return array<int, class-string> */
    public function models(): array
    {
        return $this->models;
    }

    /**
     * Role cases that bypass Gate::before.
     *
     * @return array<int, AuthorizationRole&BackedEnum>
     */
    public function superAdminRoles(): array
    {
        $roleEnum = $this->roleEnum();

        return array_values(array_filter(
            $roleEnum::cases(),
            static fn (AuthorizationRole $role): bool => $role->isSuperAdmin(),
        ));
    }
}
```

A thin `Authorization` facade proxies to the singleton so the provider reads
`Authorization::useRoleEnum(...)`. The team resolver is bound in the container
(`TeamResolver::class`), not stored on the manager — `Authorization::resolveTeamsUsing()`
is sugar over a container binding so closures are supported too.

### 5.3 Bypass strategy

`Gate::before` returns `true` for super-admins, `null` otherwise (so the chain
falls through to Spatie and then policies). The role set comes from the manager.

```php
// App\Authorization\Support\BypassStrategy::register()
Gate::before(function (Authenticatable $user, string $ability) use ($manager): ?bool {
    $superAdminRoleNames = array_map(
        static fn (BackedEnum $role): string => $role->value,
        $manager->superAdminRoles(),
    );

    return method_exists($user, 'hasAnyRole') && $user->hasAnyRole($superAdminRoleNames)
        ? true
        : null;
});
```

This replaces `Gate::before(fn (User $user) => $user->is_admin ? true : null)` in
`AppServiceProvider`.

### 5.4 Teams path

Activated only when `config('permission.teams') === true`:

- The core provider registers a `SetPermissionsTeam` middleware that calls
  `setPermissionsTeamId($resolver->resolve($request))` early in the request.
- `DefaultTeamResolver` returns `$request->user()?->{config('permission.team_foreign_key')}`.
- A policy needing ownership checks overrides the relevant `AbstractPolicy`
  method and compares the team key before delegating to `userCan()`.

With teams off (this project) none of this is wired — the middleware is not
registered and `DefaultTeamResolver` is never resolved.

### 5.5 Role: authorization vs presentation

Today `App\Enums\Policies\Role` mixes authorization with presentation
(`label()`, `layout()`, `badgeColor()`). Presentation does not belong in the
generic core.

- **Authorization** stays on the enum via the contract: add `isSuperAdmin()` and
  `permissions()`; the enum `implements AuthorizationRole`.
- **Presentation** moves to a dedicated app concern, e.g.
  `App\Support\Roles\RolePresentation` or a `HasRolePresentation` trait the enum
  uses. `label()/layout()/badgeColor()` live there and never leave the app.

`Role::ADMIN->isSuperAdmin()` returns `true`; `Role::MEMBER` returns `false`.
`permissions()` returns the per-role list currently encoded in
`PermissionRegistry::forRole()` (admin = `[]`, member = `[]` today).

### 5.6 PermissionRegistry + seeding

`PermissionRegistry` keeps name generation but sources the model list from the
manager instead of a hardcoded `permissionsFor(User::class)`. The
`forRole()` match is removed — role permissions now come from
`AuthorizationRole::permissions()`.

- `nameFromAbility()` is unchanged (absorbing the `get_model_table()` global
  helper's logic so the core has no dependency on a global function).
- `allPermissions()` iterates `manager->models()`.
- Seeders (`PermissionSeeder`, `RoleSeeder`) call a core `PermissionSync` source:
  permissions from `registry->allPermissions()`, role grants from each enum
  case's `permissions()`. The seeders stay in `database/seeders` (app), thin.

### 5.7 Policy base + generator

- `AbstractPolicy` moves to the core unchanged (constructor-injected
  `PermissionRegistry`, `userCan()` helper, the seven CRUD methods).
- Concrete policies stay in the app and extend it. `UserPolicy` remains as the
  worked example.
- The core ships a `make:authorization-policy {Model}` command + stub that
  scaffolds `App\Policies\{Model}Policy` with `getModelClass()` filled in — the
  "starter base" for new resources.

---

## 6. Migration Plan (this project)

### 6.1 New subtree

```
app/Authorization/
├── AuthorizationManager.php
├── Authorization.php                  (facade)
├── Contracts/
│   ├── AuthorizationRole.php
│   └── TeamResolver.php
├── Concerns/HasPolicy.php             (moved from app/Traits/Models/HasPolicy.php)
├── Enums/
│   ├── Ability.php                    (moved from app/Enums/Policies/Abilities/Ability.php)
│   └── SystemAbility.php              (moved from app/Enums/Policies/Abilities/SystemAbility.php)
├── AbstractPolicy.php                 (moved from app/Policies/AbstractPolicy.php)
├── PermissionRegistry.php            (moved from app/Helpers/Policies/PermissionRegistry.php)
├── Support/BypassStrategy.php
├── Teams/
│   ├── DefaultTeamResolver.php
│   └── SetPermissionsTeam.php         (middleware)
├── Database/PermissionSync.php
└── Console/
    ├── MakePolicyCommand.php
    └── stubs/policy.stub
```

> Note: the move of `Ability`/`SystemAbility` into the core changes their
> namespace. This is the most invasive find/replace of the pass (used across
> policies, middleware, providers, blade `@can`), but mechanical. Resolved: move
> to core (§10.1).

### 6.2 Provider

New `App\Providers\AuthorizationServiceProvider`:

```php
public function register(): void
{
    $this->app->singleton(AuthorizationManager::class);
    $this->app->bind(TeamResolver::class, DefaultTeamResolver::class);
}

public function boot(): void
{
    Authorization::useRoleEnum(Role::class);

    Authorization::authorizableModels([
        User::class,
    ]);

    BypassStrategy::register($this->app->make(AuthorizationManager::class));

    Gate::define(SystemAbility::ACCESS_PLATFORM_ADMIN, static fn (): bool => false);

    if (config('permission.teams') === true) {
        // register SetPermissionsTeam middleware
    }
}
```

Register it in `bootstrap/providers.php`. Remove `registerAdminAccessGate()`
from `AppServiceProvider` (the `Gate::before` + `ACCESS_PLATFORM_ADMIN`
definition move into the new provider).

### 6.3 Touch list

- `app/Providers/AppServiceProvider.php` — drop `registerAdminAccessGate()`.
- `app/Models/User.php` — `HasPolicy` import path; `is_admin`/`is_member`
  attributes are **kept** as conveniences (§10.3) but are no longer used by
  `Gate::before`. The stale team-id docblock on `appRole()` is removed (no teams
  here).
- `database/seeders/PermissionSeeder.php`, `RoleSeeder.php` — call
  `PermissionSync` / `Role::permissions()` instead of `registry->forRole()`.
- `app/Helpers/helpers.php` — `get_model_table()` becomes unused once the
  registry absorbs it; remove if no other callers (verify with grep).
- All references to `App\Enums\Policies\Abilities\Ability` /
  `SystemAbility` / `App\Traits\Models\HasPolicy` / `App\Policies\AbstractPolicy`
  / `App\Helpers\Policies\PermissionRegistry` updated to the new namespace.
- `.claude/rules/authorization.md` — update to reflect role-based bypass and the
  new layout (currently documents `is_admin`).

### 6.4 Role naming

The bypass mechanism is identified by `isSuperAdmin()`, not by a literal role
name. This project keeps `Role::ADMIN` and marks `ADMIN->isSuperAdmin() === true`
— **no role rename, no data migration**. `ADMIN` and `meg`'s `SUPER_ADMIN` are
conceptually distinct roles; `meg` is only a reference example, not a naming
target. No cross-project name symmetry is pursued (§10.2).

---

## 7. Package Extraction Boundary (future, informational)

When extracted: `app/Authorization/` → package `src/`, namespace
`App\Authorization\` → `Vendor\Authorization\`. The provider splits into a core
provider (in package: bindings, `Gate::before`, middleware, command, publishes)
and a thin published `App\Providers\AuthorizationServiceProvider` stub (the
declarations in §6.2 `boot()`). Composer deps: `spatie/laravel-permission`.
Nothing in `app/Authorization/` may reference `App\Models\*` or `App\Enums\*`
directly — the manager + contracts are the only seams. This invariant is the
acceptance test for "package-ready".

---

## 8. Testing Strategy

PHPUnit, following `.claude/rules/testing.md` (groups + `#[CoversClass]`).

- **BypassStrategy** (`policies`): super-admin role → `Gate::before` returns true;
  non-super-admin → falls through to Spatie/policy. Multi-role user with a
  super-admin role bypasses.
- **AuthorizationManager** (`policies`): `superAdminRoles()` filters by contract;
  `models()` round-trips; unconfigured role enum throws.
- **PermissionRegistry** (`policies`): `nameFromAbility()` for simple and
  underscored tables; `allPermissions()` reflects manager model list.
- **AbstractPolicy / UserPolicy** (`policies`, `users`): each ability delegates to
  the right permission string; granted vs denied.
- **Seeding** (`policies`): `PermissionSync` creates the expected permission set
  and grants `Role::permissions()` per role.
- **Role contract** (`policies`): `ADMIN->isSuperAdmin()` true, `MEMBER` false;
  `permissions()` returns expected lists.
- **Teams (off)**: with `permission.teams=false`, no team middleware registered,
  resolver never invoked.
- **Teams (on, unit)**: `DefaultTeamResolver` returns the user's team key;
  `SetPermissionsTeam` calls `setPermissionsTeamId()` — covered as a unit test
  flipping the config, without enabling teams app-wide.
- **make:authorization-policy** (`policies`): generates a file extending
  `AbstractPolicy` with the right model.

---

## 9. Implementation Phases (high level)

1. Contracts + manager + facade (no behavior change yet).
2. Split `Role` presentation out; add `isSuperAdmin()` / `permissions()`.
3. Move core classes into `app/Authorization/`, update namespaces.
4. New provider + `BypassStrategy`; remove old `registerAdminAccessGate()`.
5. Rework seeders onto `PermissionSync` / `Role::permissions()`.
6. Teams path (resolver + middleware) behind the Spatie flag.
7. `make:authorization-policy` command + stub.
8. Tests for each of the above; update `.claude/rules/authorization.md`.

(Detailed, ordered steps are produced by the implementation plan, not here.)

---

## 10. Resolved Decisions

1. **Ability enums location → core.** `Ability`/`SystemAbility` move into
   `app/Authorization/Enums/`. Largest namespace churn of the pass, but
   mechanical.
2. **No `Role::ADMIN` rename.** `ADMIN` stays and is the super-admin for this
   project via `isSuperAdmin()`. `ADMIN` and `meg`'s `SUPER_ADMIN` are distinct
   roles; `meg` is only a reference example. No data migration.
3. **Keep `is_admin` / `is_member` attributes** as conveniences. They are no
   longer consulted by `Gate::before` (which is now role-based via the bypass
   strategy), and per the project rule must not be used for authorization outside
   that one historical spot.
4. **Spec language: English**, matching the repo's existing `authorization.md`.