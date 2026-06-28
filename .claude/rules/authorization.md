# Authorization

The authorization core lives in the internal package **`apavliukov/laravel-authorization`** (namespace `AlexPavliukov\Authorization\`). This app only holds its own bits: the `Role` enum, concrete policies, the `User` model, the app-specific `SystemAbility` enum, and a thin declarations provider. Don't re-implement the core here — extend it.

## How It Works

Authorization runs on two layers:

**Layer 1 — Gate/Policy (who can do what):**
The package's core service provider registers a single `Gate::before()` via `BypassGate`, backed by a pluggable `BypassStrategy`. The default `RoleBypass` resolves all role-enum cases where `isSuperAdmin()` returns `true` (via `AuthorizationManager::superAdminRoles()`) and short-circuits to `true` for users holding any of them (`hasAnyRole()`). Non-matching users → `null` (fall through). The bypass **never returns `false`** (that would veto a legitimately-granted permission).

**Layer 2 — Spatie permissions (DB-stored fine-grained control):**
Policy methods call `userCan($user, Ability::UPDATE, $model)` → builds a permission string (`"update users"`) → Spatie checks whether the user has that permission in the DB.

**Resolution order for any `can()` / `cannot()` call:**
1. `Gate::before` (BypassGate → strategy) — super-admin bypass returns `true`; others `null` → fall through
2. Spatie's `Gate::before` — checks DB permission string; found → `true`; not found → `null` → fall through
3. If a model was passed → policy method is called
4. If no model was passed → `Gate::define()` registration is called

## Wiring — `App\Providers\AuthorizationServiceProvider`

The **package** auto-registers (auto-discovery) the bypass `Gate::before`, the teams middleware (behind the Spatie flag), and the `make:authorization-policy` command. The **app** provider only declares app specifics:

```php
public function boot(): void
{
    Authorization::useRoleEnum(Role::class);

    Authorization::authorizableModels([
        User::class,
    ]);

    Gate::define(SystemAbility::ACCESS_PLATFORM_ADMIN, static fn (): bool => false);
}
```

`Authorization` is `AlexPavliukov\Authorization\Authorization` (facade over `AuthorizationManager`). No `register()` / bindings here — the package owns them.

## Abilities

Standard CRUD abilities live in the package: `AlexPavliukov\Authorization\Enums\Ability`. Values are camelCase to match Laravel policy method names exactly:

```php
case VIEW_ANY = 'viewAny';   // → policy::viewAny()
case VIEW     = 'view';      // → policy::view()
case CREATE   = 'create';    // → policy::create()
case UPDATE   = 'update';    // → policy::update()
case DELETE   = 'delete';    // → policy::delete()
case RESTORE  = 'restore';   // → policy::restore()
case FORCE_DELETE = 'forceDelete'; // → policy::forceDelete()
```

**System abilities are app-owned** — `App\Enums\Policies\SystemAbility` (the package ships no `SystemAbility`, since standalone gates are app-specific). They never generate model permissions:

```php
// app/Enums/Policies/SystemAbility.php
enum SystemAbility: string
{
    case ACCESS_PLATFORM_ADMIN = 'accessPlatformAdmin';
}
```

Model-specific **custom abilities** are also app-owned, placed next to `Role`/`SystemAbility` in `app/Enums/Policies/` (see [Custom Abilities](#custom-abilities)).

## Permission Names

`AlexPavliukov\Authorization\PermissionRegistry::nameFromAbility(BackedEnum $ability, Model|string $model)` converts any ability + model to a DB permission string:

```
Ability::VIEW_ANY + User  →  "view any users"
Ability::UPDATE   + Post  →  "update posts"
UserAbility::IMPERSONATE + User  →  "impersonate users"
```

## Adding a New Model

### 1. Create the policy

```bash
vendor/bin/sail artisan make:authorization-policy Post
```

Generates `app/Policies/PostPolicy.php` extending the package's `AbstractPolicy`:

```php
use AlexPavliukov\Authorization\AbstractPolicy;

final readonly class PostPolicy extends AbstractPolicy
{
    protected function getModelClass(): string
    {
        return Post::class;
    }
}
```

For ownership/tenancy fencing, override the `ownsModel()` hook instead of the CRUD methods (see [Ownership](#ownership--tenancy-fencing)). Laravel auto-discovers the policy by naming convention — no `Gate::policy()` call needed.

### 2. Add `HasPolicy` to the model

```php
use AlexPavliukov\Authorization\Concerns\HasPolicy;

class Post extends Model
{
    use HasPolicy;
}
```

`HasPolicy` provides `getBasicAbilities()` / `getCustomAbilities()`, consumed by `PermissionRegistry` for seeding.

### 3. Exclude abilities the model doesn't need

If the model doesn't use SoftDeletes, override `getBasicAbilities()`:

```php
public static function getBasicAbilities(): array
{
    return array_filter(
        Ability::cases(),
        fn (Ability $ability) => ! in_array($ability, [Ability::RESTORE, Ability::FORCE_DELETE]),
    );
}
```

### 4. Register the model

In `AuthorizationServiceProvider::boot()`, add it to `Authorization::authorizableModels([...])`. `PermissionSync` iterates these when seeding.

### 5. Re-seed

```bash
vendor/bin/sail artisan db:seed --class=ConsistencySeeder
```

## Ownership / Tenancy Fencing

The package's `AbstractPolicy` exposes an `ownsModel()` hook (default `true`). The model-bound methods are `ownsModel($user, $model) && userCan(...)`; `viewAny`/`create` only check the permission. Override `ownsModel()` to fence a model to the current tenant/owner — **don't** re-implement the CRUD methods for ownership:

```php
protected function ownsModel(Authenticatable $user, Model $model): bool
{
    return $model->company_id === $user->company?->id;
}
```

The CRUD methods are intentionally **not `final`**, so a policy that needs richer logic (e.g. "manage-across-org OR own") can still override a method and call `parent::view(...)`. Prefer the hook; override only when the hook can't express it.

## Adding a New Role

### 1. Add the case to the `Role` enum

`app/Enums/Policies/Role.php` implements `AlexPavliukov\Authorization\Contracts\AuthorizationRole` and uses `App\Support\Roles\HasRolePresentation`:

| Method | Location | Notes |
|--------|----------|-------|
| `label()` | `Role` enum | Translated display name |
| `isSuperAdmin()` | `Role` enum | `true` → bypasses all gate checks |
| `permissions()` | `Role` enum | Permission name strings granted to this role (the role owns its perms) |
| `layout()` | `HasRolePresentation` trait | Area layout key (`'platform'`, `'member'`) |
| `badgeColor()` | `HasRolePresentation` trait | Flux badge colour |

All `match($this)` arms are exhaustive (no `default`) → forgetting one throws `UnhandledMatchError` at seed time.

```php
public function isSuperAdmin(): bool
{
    return match ($this) {
        self::ADMIN  => true,
        self::MEMBER => false,
    };
}

/** @return array<int, string> */
public function permissions(): array
{
    return match ($this) {
        self::ADMIN, self::MEMBER => [],  // ADMIN bypasses; non-super roles list their grants here
    };
}
```

Super-admin roles can return `[]` — the bypass makes seeding their permissions unnecessary.

### 2. Re-seed

```bash
vendor/bin/sail artisan db:seed --class=ConsistencySeeder
```

## Adding a New System Ability

System abilities are app-owned standalone gate checks — never stored as Spatie permissions.

### 1. Add the case to the app enum

```php
// app/Enums/Policies/SystemAbility.php
case ACCESS_MANAGER_AREA = 'accessManagerArea';
```

### 2. Register the gate in the app provider

```php
// AuthorizationServiceProvider::boot()
Gate::define(SystemAbility::ACCESS_MANAGER_AREA, static fn (): bool => false);
```

The `false` default blocks non-super-admins; the package's bypass grants super-admins automatically. No seeding.

### 3. Use it

```php
$user->can(SystemAbility::ACCESS_MANAGER_AREA);
Gate::authorize(SystemAbility::ACCESS_MANAGER_AREA);
// Blade: @can(SystemAbility::ACCESS_MANAGER_AREA) ... @endcan
```

## Custom Abilities

When a model needs abilities beyond the CRUD set, the enum is **app-owned**:

**1. Create a model-specific enum** in `app/Enums/Policies/`:

```php
enum UserAbility: string
{
    use HasValues;

    case IMPERSONATE = 'impersonate';
}
```

**2. Declare it on the model** via `getCustomAbilities()`:

```php
public static function getCustomAbilities(): array
{
    return UserAbility::cases();
}
```

**3. Add the policy method** (name = enum value; CRUD methods are non-final so adding methods is fine):

```php
public function impersonate(Authenticatable $user, User $model): bool
{
    return $this->userCan($user, UserAbility::IMPERSONATE, $model);
}
```

`PermissionRegistry` merges basic + custom abilities automatically when seeding.

## Pluggable Bypass

The default `RoleBypass` (super-admin roles short-circuit) is registered by the package. Override per project in `AuthorizationServiceProvider::boot()`:

```php
// no god-mode at all — every check goes through policies/Spatie
Authorization::bypassUsing(\AlexPavliukov\Authorization\Support\NoBypass::class);

// super-admin bypasses everything EXCEPT specific abilities (authorization-level carve-outs)
Authorization::bypassUsing(new \AlexPavliukov\Authorization\Support\RoleBypass($manager, protected: [Ability::FORCE_DELETE]));
```

**Principle:** a super-admin has the *right* to do everything; real "can't"s (e.g. "an org can't be without an owner") are **business invariants** enforced in the Action/domain layer, not authorization. Use `protected` / a custom strategy only for genuine authorization carve-outs (separation of duties, break-glass).

## `is_admin` / `is_member` Convenience Properties

`$user->is_admin` / `$user->is_member` are conveniences on `User`. **Never use them for authorization** — use the Gate:

```php
// Correct
$user->can(SystemAbility::ACCESS_PLATFORM_ADMIN)

// Wrong — bypasses the authorization layer
$user->is_admin
```

The super-admin bypass is handled by the package's `RoleBypass` (Spatie roles via `hasAnyRole()`), not the boolean. Third-party gates follow the same pattern — `Gate::define('viewHorizon', static fn (): bool => false)` and let the bypass grant super-admins.

## Seeding & Consistency

The package's `AlexPavliukov\Authorization\Database\AuthorizationSeeder` is the entry point; it drives the idempotent `PermissionSync`:

- `PermissionSync::permissions()` — flushes Spatie's cache, iterates `Authorization::authorizableModels()`, and `firstOrCreate`s each permission from `PermissionRegistry::allPermissions()`.
- `PermissionSync::roles()` — `firstOrCreate`s each `Role` case, then `syncPermissions($role->permissions())`.

The app's orchestrator seeders just call it:

| Seeder | Calls |
|--------|-------|
| `ConsistencySeeder` | `AuthorizationSeeder` (run on deploy) |
| `DatabaseSeeder` | `AuthorizationSeeder` |

No destructive prune — sync is additive by design.

## Teams (Multi-Tenancy)

Team-scoped permissions are controlled by Spatie's flag (single source of truth):

```php
// config/permission.php
'teams' => false,  // set to true to enable
```

When `true`, the package pushes `AlexPavliukov\Authorization\Teams\SetPermissionsTeam` onto the `web` group; it resolves the team id via `Authorization::teamResolver()` and calls `setPermissionsTeamId()`. The default `DefaultTeamResolver` reads `$request->user()->{team_foreign_key}`. Provide a custom one:

```php
Authorization::resolveTeamsUsing(MyCustomTeamResolver::class); // implements AlexPavliukov\Authorization\Contracts\TeamResolver
```

With teams on, **tenant scoping belongs in the permission layer (team-scoped roles) + `ownsModel()` — not a second bypass.** The `Gate::before` bypass stays god-mode-only (super-admin). The bypass contract is deliberately model-less, so model/team-scoped bypasses are impossible by design.

## Calling Authorization

```php
// Controller
$this->authorize(Ability::VIEW_ANY, User::class);
$this->authorize(Ability::UPDATE, $user);

// Livewire / elsewhere
Gate::authorize(Ability::UPDATE, $user);
Gate::check(Ability::DELETE, $user);

// Blade
@can(Ability::UPDATE, $user) ... @endcan
```
