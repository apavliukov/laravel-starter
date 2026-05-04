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

### 5. Seed permissions

Call `Post::makeAllPermissions()` in your permission seeder to get the full list of permission strings to create.

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
