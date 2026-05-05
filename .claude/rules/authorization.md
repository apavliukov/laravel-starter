# Authorization

## How It Works

Authorization runs on two layers:

**Layer 1 — Gate/Policy (who can do what):**
`Gate::before()` in `AppServiceProvider` fires before every check. Admins short-circuit to `true` immediately. Non-admins fall through to the policy method, which checks a Spatie permission.

**Layer 2 — Spatie permissions (DB-stored fine-grained control):**
Policy methods call `userCan($user, Ability::UPDATE, $model)` → builds a permission string (`"update users"`) → Spatie checks whether the user has that permission in the DB. Spatie intercepts this via its own `Gate::before()` callback.

**Resolution order for any `can()` / `cannot()` call:**
1. `Gate::before` — admin bypass returns `true`; non-admin returns `null` → falls through
2. Spatie's `Gate::before` — checks DB permission string; found → `true`; not found → `null` → falls through
3. If a model was passed → policy method is called
4. If no model was passed → `Gate::define()` registration is called

## Abilities

Standard resource abilities live in `app/Enums/Policies/Abilities/Ability.php`. Values are camelCase to match Laravel policy method names exactly:

```php
case VIEW_ANY = 'viewAny';   // → policy::viewAny()
case VIEW     = 'view';      // → policy::view()
case CREATE   = 'create';    // → policy::create()
case UPDATE   = 'update';    // → policy::update()
case DELETE   = 'delete';    // → policy::delete()
case RESTORE  = 'restore';   // → policy::restore()
case FORCE_DELETE = 'forceDelete'; // → policy::forceDelete()
```

System abilities (no model, standalone gate checks) live in `app/Enums/Policies/Abilities/SystemAbility.php`. These never generate model permissions.

Model-specific custom abilities (e.g. `impersonate`) get their own enum in `app/Enums/Policies/Abilities/` alongside the standard ones:

```
app/Enums/Policies/Abilities/
├── Ability.php          ← standard CRUD set
├── SystemAbility.php    ← standalone gate checks
└── UserAbility.php      ← custom abilities for User model
```

## Permission Names

`Permission::makeNameFromAbility(BackedEnum $ability, Model|string $model)` converts any ability + model to a DB permission string:

```
Ability::VIEW_ANY + User  →  "view any users"
Ability::UPDATE   + Post  →  "update posts"
UserAbility::IMPERSONATE + User  →  "impersonate users"
```

## Adding a New Model

### 1. Create the policy

```bash
vendor/bin/sail artisan make:policy PostPolicy
```

Extend `AbstractPolicy`, implement `getModelClass()`. That's all for standard CRUD:

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

### 2. Add `HasPolicy` to the model

```php
use App\Traits\Models\HasPolicy;

class Post extends Model
{
    use HasPolicy;
}
```

`HasPolicy` provides `getBasicAbilities()`, `getCustomAbilities()`, and `makeAllPermissions()` for permission seeding.

### 3. Exclude abilities the model doesn't need

If the model doesn't use SoftDeletes, override `getBasicAbilities()`:

```php
public static function getBasicAbilities(): array
{
    return array_filter(
        Ability::cases(),
        fn (Ability $a) => ! in_array($a, [Ability::RESTORE, Ability::FORCE_DELETE]),
    );
}
```

No permission will be seeded for excluded abilities — `userCan()` returns `false` naturally.

### 4. Register the policy

In `AuthServiceProvider` (or `AppServiceProvider`):

```php
Gate::policy(Post::class, PostPolicy::class);
```

### 5. Register permissions in PermissionRegistry

Add the model to `allPermissions()` in `App\Helpers\Policies\PermissionRegistry`:

```php
public function allPermissions(): array
{
    return array_merge(
        User::makeAllPermissions(),
        Post::makeAllPermissions(), // ← add this
    );
}
```

Then re-seed: `vendor/bin/sail artisan db:seed --class=RoleAndPermissionSeeder`

## Adding a New Role

### 1. Add the case to the `Role` enum

```php
// app/Enums/Policies/Role.php
case MANAGER = 'manager';
```

Add the new case to every `match($this)` in the enum: `label()`, `layout()`, `badgeColor()`.

### 2. Add permissions to `PermissionRegistry`

Add a private method for the role's permissions and a case to `forRole()`:

```php
// app/Helpers/Policies/PermissionRegistry.php
public function forRole(RoleEnum $role): array
{
    return match($role) {
        RoleEnum::ADMIN   => $this->adminPermissions(),
        RoleEnum::MANAGER => $this->managerPermissions(), // ← add arm
        RoleEnum::MEMBER  => $this->memberPermissions(),
    };
}

private function managerPermissions(): array
{
    return [
        // e.g. User::makeModelPermission(Ability::VIEW_ANY),
    ];
}
```

Because the match is exhaustive (no `default`), forgetting this step throws an `UnhandledMatchError` at seeding time.

### 3. Re-seed

```bash
vendor/bin/sail artisan db:seed --class=RoleAndPermissionSeeder
```

### 4. Wire up the area (if the role gets its own UI)

- Add middleware, route group, and layout following the existing `admin-structure.md` patterns.

---

## Adding a New System Ability

System abilities are standalone gate checks with no model attached — never stored as Spatie permissions.

### 1. Add the case to `SystemAbility`

```php
// app/Enums/Policies/Abilities/SystemAbility.php
case ACCESS_MANAGER_AREA = 'accessManagerArea';
```

### 2. Register the gate in `AppServiceProvider`

```php
// app/Providers/AppServiceProvider.php — registerAdminAccessGate()
Gate::define(SystemAbility::ACCESS_MANAGER_AREA, static fn (): bool => false);
```

The `false` default blocks non-admins. `Gate::before` handles the admin bypass automatically — no extra logic needed.

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

**1. Create a model-specific enum** in `app/Enums/Policies/Abilities/`:

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

`makeAllPermissions()` merges basic and custom abilities automatically — no extra seeder work needed.

## System Abilities (no model)

For gates that don't relate to a specific model (e.g. access to an admin area):

```php
// AppServiceProvider
Gate::define(SystemAbility::ACCESS_PLATFORM_ADMIN, static fn (): bool => false);

// Middleware / Blade
$user->cannot(SystemAbility::ACCESS_PLATFORM_ADMIN);
@cannot(SystemAbility::ACCESS_PLATFORM_ADMIN)
```

The `false` default is the safety net for non-admins. `Gate::before` handles admins automatically.

## `is_admin` Rule

`$user->is_admin` must only appear inside the `Gate::before` callback in `AppServiceProvider`. **Everywhere else**, use the Gate:

```php
// Correct
$user->can(SystemAbility::ACCESS_PLATFORM_ADMIN)

// Wrong — bypasses the authorization layer
$user->is_admin
```

Third-party package gates follow the same pattern — `Gate::define('viewHorizon', static fn (): bool => false)` and let `Gate::before` handle the bypass.

## Advanced Scenarios

### Super Admin Role

The current `is_admin` flag + `Gate::before` bypass is designed for a **single, unconditional super admin** — one account that can do everything with no further checks. This is appropriate for most apps.

If you need a named `SUPER_ADMIN` role (e.g. visible in role management UI, assignable to multiple users):

1. Add `case SUPER_ADMIN = 'super_admin'` to the `Role` enum with all match arms filled.
2. Change the `Gate::before` check from the `is_admin` boolean to a role check:
   ```php
   Gate::before(static fn (User $user): ?bool =>
       $user->hasRole(Role::SUPER_ADMIN->value) ? true : null
   );
   ```
3. Keep `is_admin` as a fast-path boolean on the `users` table, synced when the super admin role is assigned/revoked — or drop it and rely solely on the role check (slightly slower, hits the DB).
4. `PermissionRegistry::forRole()` should return `[]` for `SUPER_ADMIN` — the bypass in `Gate::before` makes seeding permissions for it pointless and misleading.

---

### Multi-Tenancy

The global `Gate::before` bypass works for a true super admin but is **too broad for tenant-scoped admins**. A tenant admin should bypass checks only within their own tenant.

#### Option A — Spatie Teams (recommended)

Spatie supports team-scoped permissions natively. Enable it:

1. Set `'teams' => true` in `config/permission.php`.
2. Set the active team before any permission check (typically in middleware):
   ```php
   // app/Http/Middleware/SetPermissionsTeam.php
   setPermissionsTeamId($request->user()->team_id);
   ```
3. Seed permissions per team — `syncPermissions()` and `assignRole()` become team-aware automatically.
4. Tenant admin bypass: add a second, scoped gate check before Spatie's:
   ```php
   Gate::before(static fn (User $user): ?bool =>
       $user->is_admin ? true : null  // global super admin — unchanged
   );

   // Tenant admin bypass — fires after super admin check, before Spatie
   Gate::before(static function (User $user, string $ability) use ($tenantId): ?bool {
       if ($user->isTenantAdmin() && $user->team_id === $tenantId) {
           return true;
       }
       return null;
   });
   ```
5. Policy methods that need ownership checks override `AbstractPolicy` methods:
   ```php
   public function update(User $user, Post $post): bool
   {
       if ($post->team_id !== $user->team_id) {
           return false;
       }
       return $this->userCan($user, Ability::UPDATE, $post);
   }
   ```

#### Option B — Manual tenant scoping (no Spatie teams)

If Spatie teams add too much complexity, scope at the policy level only — no bypass for tenant admins. Every policy method checks `$model->team_id === $user->team_id` before delegating to `userCan()`. Simpler, but tenant admins are subject to full Spatie permission checks.

#### Key principle for both options

The `Gate::before` global bypass must remain **only** for the true super admin (`is_admin`). Any scoped bypass (tenant admin, team admin) must include an explicit scope check — never a blanket `return true`.

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
