# Authorization

## How It Works

Authorization runs on two layers:

**Layer 1 — Gate/Policy (who can do what):**
`BypassStrategy::register()` is called from `AuthorizationServiceProvider::boot()` and registers a `Gate::before()` callback. It resolves all role enum cases where `isSuperAdmin()` returns `true` (via `AuthorizationManager::superAdminRoles()`) and short-circuits to `true` for users holding any of those roles via `hasAnyRole()`. Non-matching users return `null` and fall through to the policy.

**Layer 2 — Spatie permissions (DB-stored fine-grained control):**
Policy methods call `userCan($user, Ability::UPDATE, $model)` → builds a permission string (`"update users"`) → Spatie checks whether the user has that permission in the DB. Spatie intercepts this via its own `Gate::before()` callback.

**Resolution order for any `can()` / `cannot()` call:**
1. `Gate::before` (BypassStrategy) — super-admin role bypass returns `true`; others return `null` → falls through
2. Spatie's `Gate::before` — checks DB permission string; found → `true`; not found → `null` → falls through
3. If a model was passed → policy method is called
4. If no model was passed → `Gate::define()` registration is called

## Abilities

Standard resource abilities live in `App\Authorization\Enums\Ability`. Values are camelCase to match Laravel policy method names exactly:

```php
case VIEW_ANY = 'viewAny';   // → policy::viewAny()
case VIEW     = 'view';      // → policy::view()
case CREATE   = 'create';    // → policy::create()
case UPDATE   = 'update';    // → policy::update()
case DELETE   = 'delete';    // → policy::delete()
case RESTORE  = 'restore';   // → policy::restore()
case FORCE_DELETE = 'forceDelete'; // → policy::forceDelete()
```

System abilities (no model, standalone gate checks) live in `App\Authorization\Enums\SystemAbility`. These never generate model permissions.

Model-specific custom abilities (e.g. `UserAbility`) can be placed in `app/Authorization/Enums/` alongside the standard ones:

```
app/Authorization/Enums/
├── Ability.php          ← standard CRUD set
├── SystemAbility.php    ← standalone gate checks
└── UserAbility.php      ← custom abilities for User model (example)
```

## Permission Names

`PermissionRegistry::nameFromAbility(BackedEnum $ability, Model|string $model)` converts any ability + model to a DB permission string:

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

This generates `app/Policies/PostPolicy.php` extending `App\Authorization\AbstractPolicy`:

```php
final readonly class PostPolicy extends AbstractPolicy
{
    protected function getModelClass(): string
    {
        return Post::class;
    }
}
```

Override any method that needs custom logic (e.g. `view()` checking subscription, `update()` checking ownership).

Laravel auto-discovers the policy by naming convention (`App\Policies\PostPolicy` for `App\Models\Post`). No explicit `Gate::policy()` call is needed.

### 2. Add `HasPolicy` to the model

```php
use App\Authorization\Concerns\HasPolicy;

class Post extends Model
{
    use HasPolicy;
}
```

`HasPolicy` provides `getBasicAbilities()`, `getCustomAbilities()`, and is used by `PermissionRegistry` for permission seeding.

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

No permission will be seeded for excluded abilities — `userCan()` returns `false` naturally.

### 4. Register the model with the authorization system

In `AuthorizationServiceProvider::boot()`, add the model to `Authorization::authorizableModels()`:

```php
Authorization::authorizableModels([
    User::class,
    Post::class, // ← add this
]);
```

`PermissionSync` iterates these models when seeding permissions.

### 5. Re-seed

```bash
vendor/bin/sail artisan db:seed --class=RoleAndPermissionSeeder
```

## Adding a New Role

### 1. Add the case to the `Role` enum

```php
// app/Enums/Policies/Role.php
case MANAGER = 'manager';
```

Fill all required arms. The enum implements `AuthorizationRole` and uses `HasRolePresentation`:

| Method | Location | Notes |
|--------|----------|-------|
| `label()` | `Role` enum | Translated display name |
| `isSuperAdmin()` | `Role` enum | `true` → bypasses all gate checks |
| `permissions()` | `Role` enum | Array of permission name strings granted to this role |
| `layout()` | `HasRolePresentation` trait | Area layout key (e.g. `'platform'`, `'member'`) |
| `badgeColor()` | `HasRolePresentation` trait | Flux badge colour string |

Because all `match($this)` expressions are exhaustive (no `default`), forgetting any arm throws an `UnhandledMatchError` at seeding time.

Example:

```php
public function isSuperAdmin(): bool
{
    return match ($this) {
        self::ADMIN   => true,
        self::MANAGER => false,
        self::MEMBER  => false,
    };
}

/** @return array<int, string> */
public function permissions(): array
{
    return match ($this) {
        self::ADMIN, self::MANAGER => [],  // ADMIN bypasses; MANAGER seeded explicitly
        self::MEMBER               => [],
    };
}
```

Return explicit permission name strings from `permissions()` to grant them to the role on seeding. Super-admin roles can return `[]` — `Gate::before` makes seeding permissions for them unnecessary.

### 2. Re-seed

```bash
vendor/bin/sail artisan db:seed --class=RoleAndPermissionSeeder
```

### 3. Wire up the area (if the role gets its own UI)

Add middleware, route group, and layout following the existing `admin-structure.md` patterns.

---

## Adding a New System Ability

System abilities are standalone gate checks with no model attached — never stored as Spatie permissions.

### 1. Add the case to `SystemAbility`

```php
// app/Authorization/Enums/SystemAbility.php
case ACCESS_MANAGER_AREA = 'accessManagerArea';
```

### 2. Register the gate in `AuthorizationServiceProvider`

```php
// app/Providers/AuthorizationServiceProvider.php — inside boot()
Gate::define(SystemAbility::ACCESS_MANAGER_AREA, static fn (): bool => false);
```

The `false` default blocks non-super-admin users. `BypassStrategy::register()` handles the bypass automatically — no extra logic needed.

### 3. Use it

```php
// Middleware / controller
$user->can(SystemAbility::ACCESS_MANAGER_AREA);
Gate::authorize(SystemAbility::ACCESS_MANAGER_AREA);

// Blade
@can(SystemAbility::ACCESS_MANAGER_AREA) ... @endcan
```

No seeding required — system abilities live entirely in PHP.

---

## Custom Abilities

When a model needs abilities beyond the standard CRUD set:

**1. Create a model-specific enum** in `app/Authorization/Enums/`:

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

**3. Add the policy method** — the method name must match the enum value:

```php
public function impersonate(User $user, User $model): bool
{
    return $this->userCan($user, UserAbility::IMPERSONATE, $model);
}
```

`PermissionRegistry` merges basic and custom abilities automatically when seeding — no extra seeder work needed.

---

## `is_admin` / `is_member` Convenience Properties

`$user->is_admin` and `$user->is_member` are convenience properties on the `User` model. **They must not be used for authorization decisions.** Instead, use the Gate:

```php
// Correct
$user->can(SystemAbility::ACCESS_PLATFORM_ADMIN)

// Wrong — bypasses the authorization layer
$user->is_admin
```

The super-admin bypass in `Gate::before` is handled entirely by `BypassStrategy`, which checks Spatie roles via `hasAnyRole()`, not the boolean flag.

Third-party package gates follow the same pattern — `Gate::define('viewHorizon', static fn (): bool => false)` and let `BypassStrategy` handle the bypass via the `Gate::before` callback.

---

## Seeding & Consistency

`App\Authorization\Database\PermissionSync` is the idempotent seeding engine:

- `permissions()` — iterates models registered via `Authorization::authorizableModels()`, calls `PermissionRegistry::allPermissions()`, and runs `firstOrCreate` per permission.
- `roles()` — iterates all `Role` cases, runs `firstOrCreate` per role, then calls `role->permissions()` and syncs them to the Spatie role via `syncPermissions()`.

Seeders are thin wrappers:

| Seeder | Calls |
|--------|-------|
| `PermissionSeeder` | `PermissionSync::permissions()` |
| `RoleSeeder` | `PermissionSync::roles()` |
| `RoleAndPermissionSeeder` | `PermissionSeeder`, `RoleSeeder` |
| `ConsistencySeeder` | `RoleAndPermissionSeeder` (run on deploy) |

No destructive prune — sync is additive by design.

---

## Teams (Multi-Tenancy)

Team-scoped permissions are controlled by a single config key:

```php
// config/permission.php
'teams' => false,  // set to true to enable
```

When `'teams' => true`, `AuthorizationServiceProvider` pushes `App\Authorization\Teams\SetPermissionsTeam` middleware onto the `web` group. That middleware calls `Authorization::teamResolver()->resolve($request)` to get the current team ID, then passes it to Spatie via `setPermissionsTeamId()`.

The default resolver (`App\Authorization\Teams\DefaultTeamResolver`) reads `$request->user()->{team_foreign_key}`. Provide a custom resolver by implementing `App\Authorization\Contracts\TeamResolver` and registering it:

```php
// In AuthorizationServiceProvider::boot()
Authorization::resolveTeamsUsing(MyCustomTeamResolver::class);
```

The super-admin `Gate::before` bypass remains unconditional — scoped team-admin bypasses must be added as additional `Gate::before` callbacks with explicit scope checks.

---

## Calling Authorization

In controllers:

```php
// Class-level (no model instance)
$this->authorize(Ability::VIEW_ANY, User::class);
$this->authorize(Ability::CREATE, User::class);

// Instance-level
$this->authorize(Ability::UPDATE, $user);
```

In Livewire components and elsewhere:

```php
Gate::authorize(Ability::UPDATE, $user);
Gate::check(Ability::DELETE, $user);
```

In Blade:

```blade
@can(Ability::UPDATE, $user)
@cannot(Ability::DELETE, $user)
```
