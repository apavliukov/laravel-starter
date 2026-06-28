# Authorization Decoupling Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Decouple the Spatie-based authorization module into a single generic subtree (`app/Authorization/`) separated from app-specific code, unifying on role-based super-admin bypass and a manager-driven configuration, so it can later be lifted into a package almost unchanged.

**Architecture:** A generic core under `app/Authorization/` (contracts, manager, abstract policy, registry, bypass strategy, enums, teams path, generator) reads app declarations from an `AuthorizationManager` configured in a dedicated `AuthorizationServiceProvider`. The app keeps its `Role` enum (implementing `AuthorizationRole`), concrete policies, and `User` model. Spatie's `config/permission.php` stays the single source of truth for storage and the teams flag.

**Tech Stack:** PHP 8.4, Laravel 13, spatie/laravel-permission, Livewire/Flux, PHPUnit 13, Laravel Sail.

## Global Constraints

- Every PHP file: `declare(strict_types=1);`.
- `final` classes; `readonly` where the class holds only injected state; typed properties; explicit return types; short nullable `?Type`.
- Blank line before every control structure; full descriptive variable names; type-hinted closure params.
- Tests: PHPUnit only, extend `Tests\TestCase` (already has `RefreshDatabase`, `Http::preventStrayRequests()`). Class-level `#[Group(...)]` + `#[CoversClass(...)]`. Do not duplicate base traits.
- Run PHP via Sail: `vendor/bin/sail ...`. Format dirty files only after edits: `vendor/bin/sail bin pint --dirty --format agent`.
- No new Composer dependencies. No config file is added (`config/permission.php` already exists from Spatie).
- The generic subtree `app/Authorization/` MUST NOT reference `App\Models\*` or `App\Enums\Policies\Role` directly — only the contracts and the manager. This invariant is the package-readiness acceptance test.
- Keep the test suite green at every commit. Moves update all referrers in the same task.
- Do not commit unless a step says to; commit messages as shown.

---

## File Structure

**New (generic core, `app/Authorization/`):**
- `Contracts/AuthorizationRole.php` — role semantics seam (`isSuperAdmin`, `permissions`)
- `Contracts/TeamResolver.php` — current-team resolution seam
- `AuthorizationManager.php` — holds role enum, authorizable models, team resolver
- `Authorization.php` — facade over the manager singleton
- `Support/BypassStrategy.php` — registers `Gate::before`
- `Enums/Ability.php`, `Enums/SystemAbility.php` — moved from `app/Enums/Policies/Abilities/`
- `Concerns/HasPolicy.php` — moved from `app/Traits/Models/`
- `AbstractPolicy.php` — moved from `app/Policies/`
- `PermissionRegistry.php` — moved from `app/Helpers/Policies/`, rewired to the manager
- `Database/PermissionSync.php` — reusable seeding engine
- `Teams/DefaultTeamResolver.php`, `Teams/SetPermissionsTeam.php` — teams path (off here)
- `Console/MakePolicyCommand.php`, `Console/stubs/policy.stub` — policy generator

**New (app side):**
- `app/Providers/AuthorizationServiceProvider.php` — declarations + wiring
- `app/Support/Roles/HasRolePresentation.php` — `layout()`/`badgeColor()` extracted from `Role`

**Modified:** `app/Enums/Policies/Role.php`, `app/Models/User.php`, `app/Policies/UserPolicy.php`, `app/Providers/AppServiceProvider.php`, `database/seeders/PermissionSeeder.php`, `database/seeders/RoleSeeder.php`, `bootstrap/providers.php`, `app/Helpers/helpers.php`, several blades and Users-domain files (import path updates), `.claude/rules/authorization.md`, and the existing auth tests.

---

## Task 1: AuthorizationRole contract + Role implements it

**Files:**
- Create: `app/Authorization/Contracts/AuthorizationRole.php`
- Modify: `app/Enums/Policies/Role.php`
- Test: `tests/Unit/Enums/Policies/RoleTest.php`

**Interfaces:**
- Produces: `App\Authorization\Contracts\AuthorizationRole` with `isSuperAdmin(): bool` and `permissions(): array` (`array<int, string>`). `App\Enums\Policies\Role` implements it; `Role::ADMIN->isSuperAdmin() === true`, `Role::MEMBER->isSuperAdmin() === false`, both `permissions()` return `[]`.

- [ ] **Step 1: Write the failing test**

Create `tests/Unit/Enums/Policies/RoleTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Enums\Policies;

use App\Authorization\Contracts\AuthorizationRole;
use App\Enums\Policies\Role;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[Group('policies')]
#[CoversClass(Role::class)]
final class RoleTest extends TestCase
{
    #[Test]
    public function it_implements_the_authorization_role_contract(): void
    {
        $this->assertInstanceOf(AuthorizationRole::class, Role::ADMIN);
    }

    #[Test]
    public function admin_is_super_admin(): void
    {
        $this->assertTrue(Role::ADMIN->isSuperAdmin());
    }

    #[Test]
    public function member_is_not_super_admin(): void
    {
        $this->assertFalse(Role::MEMBER->isSuperAdmin());
    }

    #[Test]
    public function roles_grant_no_extra_permissions_by_default(): void
    {
        $this->assertSame([], Role::ADMIN->permissions());
        $this->assertSame([], Role::MEMBER->permissions());
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/sail artisan test --filter=RoleTest`
Expected: FAIL — `App\Authorization\Contracts\AuthorizationRole` not found.

- [ ] **Step 3: Create the contract**

Create `app/Authorization/Contracts/AuthorizationRole.php`:

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

- [ ] **Step 4: Make Role implement the contract**

In `app/Enums/Policies/Role.php`, add the import and `implements`, then add the two methods. The `implements` line becomes:

```php
use App\Authorization\Contracts\AuthorizationRole;
// ...
enum Role: string implements AuthorizationRole, HasLabelsInterface, StringMatchInterface
```

Add these methods to the enum body (after `badgeColor()`):

```php
public function isSuperAdmin(): bool
{
    return match ($this) {
        self::ADMIN => true,
        self::MEMBER => false,
    };
}

/** @return array<int, string> */
public function permissions(): array
{
    return match ($this) {
        self::ADMIN, self::MEMBER => [],
    };
}
```

- [ ] **Step 5: Run test to verify it passes**

Run: `vendor/bin/sail artisan test --filter=RoleTest`
Expected: PASS (4 tests).

- [ ] **Step 6: Format and commit**

```bash
vendor/bin/sail bin pint --dirty --format agent
git add app/Authorization/Contracts/AuthorizationRole.php app/Enums/Policies/Role.php tests/Unit/Enums/Policies/RoleTest.php
git commit -m "feat(auth): add AuthorizationRole contract and implement on Role enum"
```

---

## Task 2: AuthorizationManager + Authorization facade

**Files:**
- Create: `app/Authorization/AuthorizationManager.php`
- Create: `app/Authorization/Authorization.php`
- Test: `tests/Unit/Authorization/AuthorizationManagerTest.php`

**Interfaces:**
- Consumes: `App\Authorization\Contracts\AuthorizationRole` (Task 1).
- Produces:
  - `AuthorizationManager`: `useRoleEnum(string $roleEnum): void`, `roleEnum(): string` (throws `RuntimeException` if unset), `authorizableModels(array $models): void`, `models(): array`, `superAdminRoles(): array` (`array<int, AuthorizationRole&BackedEnum>`), `resolveTeamsUsing(string $resolverClass): void`, `teamResolver(): TeamResolver`.
  - `Authorization` facade resolving the `AuthorizationManager` singleton.

- [ ] **Step 1: Write the failing test**

Create `tests/Unit/Authorization/AuthorizationManagerTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Authorization;

use App\Authorization\AuthorizationManager;
use App\Enums\Policies\Role;
use App\Models\User;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

#[Group('policies')]
#[CoversClass(AuthorizationManager::class)]
final class AuthorizationManagerTest extends TestCase
{
    #[Test]
    public function it_round_trips_the_role_enum(): void
    {
        $manager = new AuthorizationManager();
        $manager->useRoleEnum(Role::class);

        $this->assertSame(Role::class, $manager->roleEnum());
    }

    #[Test]
    public function it_throws_when_role_enum_is_not_configured(): void
    {
        $manager = new AuthorizationManager();

        $this->expectException(RuntimeException::class);

        $manager->roleEnum();
    }

    #[Test]
    public function it_round_trips_authorizable_models(): void
    {
        $manager = new AuthorizationManager();
        $manager->authorizableModels([User::class]);

        $this->assertSame([User::class], $manager->models());
    }

    #[Test]
    public function it_returns_only_super_admin_roles(): void
    {
        $manager = new AuthorizationManager();
        $manager->useRoleEnum(Role::class);

        $this->assertSame([Role::ADMIN], $manager->superAdminRoles());
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/sail artisan test --filter=AuthorizationManagerTest`
Expected: FAIL — `App\Authorization\AuthorizationManager` not found.

- [ ] **Step 3: Create the manager**

Create `app/Authorization/AuthorizationManager.php`:

```php
<?php

declare(strict_types=1);

namespace App\Authorization;

use App\Authorization\Contracts\AuthorizationRole;
use App\Authorization\Contracts\TeamResolver;
use App\Authorization\Teams\DefaultTeamResolver;
use BackedEnum;
use RuntimeException;

final class AuthorizationManager
{
    /** @var class-string<AuthorizationRole&BackedEnum>|null */
    private ?string $roleEnum = null;

    /** @var array<int, class-string> */
    private array $models = [];

    /** @var class-string<TeamResolver> */
    private string $teamResolver = DefaultTeamResolver::class;

    /** @param class-string<AuthorizationRole&BackedEnum> $roleEnum */
    public function useRoleEnum(string $roleEnum): void
    {
        $this->roleEnum = $roleEnum;
    }

    /** @return class-string<AuthorizationRole&BackedEnum> */
    public function roleEnum(): string
    {
        return $this->roleEnum ?? throw new RuntimeException('Role enum is not configured. Call Authorization::useRoleEnum() in AuthorizationServiceProvider.');
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

    /** @param class-string<TeamResolver> $resolverClass */
    public function resolveTeamsUsing(string $resolverClass): void
    {
        $this->teamResolver = $resolverClass;
    }

    public function teamResolver(): TeamResolver
    {
        return app($this->teamResolver);
    }
}
```

> Note: this file references `Teams\DefaultTeamResolver` and `Contracts\TeamResolver`, created in Task 9. To keep this task self-contained and green, create both stubs now:
>
> `app/Authorization/Contracts/TeamResolver.php`:
> ```php
> <?php
>
> declare(strict_types=1);
>
> namespace App\Authorization\Contracts;
>
> use Illuminate\Http\Request;
>
> interface TeamResolver
> {
>     public function resolve(Request $request): int|string|null;
> }
> ```
>
> `app/Authorization/Teams/DefaultTeamResolver.php`:
> ```php
> <?php
>
> declare(strict_types=1);
>
> namespace App\Authorization\Teams;
>
> use App\Authorization\Contracts\TeamResolver;
> use Illuminate\Http\Request;
>
> final class DefaultTeamResolver implements TeamResolver
> {
>     public function resolve(Request $request): int|string|null
>     {
>         $foreignKey = config('permission.team_foreign_key', 'team_id');
>
>         return $request->user()?->getAttribute($foreignKey);
>     }
> }
> ```

- [ ] **Step 4: Create the facade**

Create `app/Authorization/Authorization.php`:

```php
<?php

declare(strict_types=1);

namespace App\Authorization;

use Illuminate\Support\Facades\Facade;

/**
 * @method static void useRoleEnum(string $roleEnum)
 * @method static string roleEnum()
 * @method static void authorizableModels(array $models)
 * @method static array models()
 * @method static array superAdminRoles()
 * @method static void resolveTeamsUsing(string $resolverClass)
 * @method static \App\Authorization\Contracts\TeamResolver teamResolver()
 *
 * @see AuthorizationManager
 */
final class Authorization extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return AuthorizationManager::class;
    }
}
```

- [ ] **Step 5: Run test to verify it passes**

Run: `vendor/bin/sail artisan test --filter=AuthorizationManagerTest`
Expected: PASS (4 tests).

- [ ] **Step 6: Format and commit**

```bash
vendor/bin/sail bin pint --dirty --format agent
git add app/Authorization/AuthorizationManager.php app/Authorization/Authorization.php app/Authorization/Contracts/TeamResolver.php app/Authorization/Teams/DefaultTeamResolver.php tests/Unit/Authorization/AuthorizationManagerTest.php
git commit -m "feat(auth): add AuthorizationManager, facade, and team-resolver seam"
```

---

## Task 3: Move Ability & SystemAbility into the core

**Files:**
- Move: `app/Enums/Policies/Abilities/Ability.php` → `app/Authorization/Enums/Ability.php`
- Move: `app/Enums/Policies/Abilities/SystemAbility.php` → `app/Authorization/Enums/SystemAbility.php`
- Modify: every referrer of the old FQCNs (see mapping)
- Test: existing suite (no new test)

**Interfaces:**
- Produces: `App\Authorization\Enums\Ability`, `App\Authorization\Enums\SystemAbility` (same cases, same `HasValues` trait usage).

FQCN mapping (old → new):
- `App\Enums\Policies\Abilities\Ability` → `App\Authorization\Enums\Ability`
- `App\Enums\Policies\Abilities\SystemAbility` → `App\Authorization\Enums\SystemAbility`

- [ ] **Step 1: Create the moved files with new namespace**

Create `app/Authorization/Enums/Ability.php`:

```php
<?php

declare(strict_types=1);

namespace App\Authorization\Enums;

use App\Traits\Enums\HasValues;

enum Ability: string
{
    use HasValues;

    case VIEW_ANY = 'viewAny';
    case VIEW = 'view';
    case CREATE = 'create';
    case UPDATE = 'update';
    case DELETE = 'delete';
    case RESTORE = 'restore';
    case FORCE_DELETE = 'forceDelete';
}
```

Create `app/Authorization/Enums/SystemAbility.php`:

```php
<?php

declare(strict_types=1);

namespace App\Authorization\Enums;

use App\Traits\Enums\HasValues;

enum SystemAbility: string
{
    use HasValues;

    case ACCESS_PLATFORM_ADMIN = 'accessPlatformAdmin';
}
```

> Note: `HasValues` stays in `App\Traits\Enums` for now (a general enum helper, not authorization-specific). Promoting it to the core is out of scope.

- [ ] **Step 2: Delete the old files**

```bash
rm app/Enums/Policies/Abilities/Ability.php app/Enums/Policies/Abilities/SystemAbility.php
```

- [ ] **Step 3: Find all referrers**

Run: `grep -rln "Policies\\\\Abilities\\\\Ability\|Policies\\\\Abilities\\\\SystemAbility" app/ database/ tests/ routes/ config/`
Update each hit's `use` statement using the FQCN mapping above. Known referrers include: `app/Http/Middleware/EnsurePlatformAdminAccessMiddleware.php`, `app/Providers/AppServiceProvider.php`, `app/Policies/AbstractPolicy.php`, `app/Helpers/Policies/PermissionRegistry.php`, `tests/Unit/Helpers/Policies/PermissionRegistryTest.php`, and any Users-domain Livewire/controller files that import `Ability`.

- [ ] **Step 4: Verify no stale references remain**

Run: `grep -rn "Policies\\\\Abilities" app/ database/ tests/ routes/ config/`
Expected: no output.

- [ ] **Step 5: Remove the now-empty directory**

```bash
rmdir app/Enums/Policies/Abilities 2>/dev/null || true
```

- [ ] **Step 6: Run the affected groups**

Run: `vendor/bin/sail artisan test --group=policies`
Expected: PASS (existing policy/registry tests still green with new imports).

- [ ] **Step 7: Format and commit**

```bash
vendor/bin/sail bin pint --dirty --format agent
git add -A
git commit -m "refactor(auth): move Ability and SystemAbility enums into app/Authorization/Enums"
```

---

## Task 4: Move HasPolicy trait into the core

**Files:**
- Move: `app/Traits/Models/HasPolicy.php` → `app/Authorization/Concerns/HasPolicy.php`
- Modify: `app/Models/User.php` and any other model using it
- Test: existing suite

**Interfaces:**
- Produces: `App\Authorization\Concerns\HasPolicy` with `getBasicAbilities(): array` and `getCustomAbilities(): array`, referencing `App\Authorization\Enums\Ability`.

- [ ] **Step 1: Create the moved trait**

Create `app/Authorization/Concerns/HasPolicy.php`:

```php
<?php

declare(strict_types=1);

namespace App\Authorization\Concerns;

use App\Authorization\Enums\Ability;
use BackedEnum;

trait HasPolicy
{
    /** @return array<int, BackedEnum> */
    public static function getBasicAbilities(): array
    {
        return Ability::cases();
    }

    /** @return array<int, BackedEnum> */
    public static function getCustomAbilities(): array
    {
        return [];
    }
}
```

- [ ] **Step 2: Delete the old trait**

```bash
rm app/Traits/Models/HasPolicy.php
```

- [ ] **Step 3: Update referrers**

Run: `grep -rln "Traits\\\\Models\\\\HasPolicy" app/ tests/ database/`
In `app/Models/User.php` change `use App\Traits\Models\HasPolicy;` → `use App\Authorization\Concerns\HasPolicy;` (keep the `use HasPolicy;` line in the trait list). Apply the same to any other hits.

- [ ] **Step 4: Verify no stale references**

Run: `grep -rn "Traits\\\\Models\\\\HasPolicy" app/ tests/ database/`
Expected: no output.

- [ ] **Step 5: Run tests**

Run: `vendor/bin/sail artisan test --group=policies --group=users`
Expected: PASS.

- [ ] **Step 6: Format and commit**

```bash
vendor/bin/sail bin pint --dirty --format agent
git add -A
git commit -m "refactor(auth): move HasPolicy trait into app/Authorization/Concerns"
```

---

## Task 5: Move AbstractPolicy into the core

**Files:**
- Move: `app/Policies/AbstractPolicy.php` → `app/Authorization/AbstractPolicy.php`
- Modify: `app/Policies/UserPolicy.php`, `tests/Unit/Policies/BasePolicyTest.php`
- Test: existing policy tests

**Interfaces:**
- Produces: `App\Authorization\AbstractPolicy` (constructor-injected `PermissionRegistry`, `userCan()`, seven CRUD methods). `App\Policies\UserPolicy` extends it.

> At this point `PermissionRegistry` still lives at `App\Helpers\Policies\PermissionRegistry` (moved in Task 6). Reference it from there in this task.

- [ ] **Step 1: Create the moved AbstractPolicy**

Create `app/Authorization/AbstractPolicy.php` (namespace + imports updated; body unchanged):

```php
<?php

declare(strict_types=1);

namespace App\Authorization;

use App\Authorization\Enums\Ability;
use App\Helpers\Policies\PermissionRegistry;
use App\Models\User;
use BackedEnum;
use Illuminate\Database\Eloquent\Model;

abstract readonly class AbstractPolicy
{
    protected string $modelClass;

    public function __construct(private PermissionRegistry $registry)
    {
        $this->modelClass = $this->getModelClass();
    }

    abstract protected function getModelClass(): string;

    public function viewAny(User $user): bool
    {
        return $this->userCan($user, Ability::VIEW_ANY);
    }

    public function view(User $user, Model $model): bool
    {
        return $this->userCan($user, Ability::VIEW, $model);
    }

    public function create(User $user): bool
    {
        return $this->userCan($user, Ability::CREATE);
    }

    public function update(User $user, Model $model): bool
    {
        return $this->userCan($user, Ability::UPDATE, $model);
    }

    public function delete(User $user, Model $model): bool
    {
        return $this->userCan($user, Ability::DELETE, $model);
    }

    public function restore(User $user, Model $model): bool
    {
        return $this->userCan($user, Ability::RESTORE, $model);
    }

    public function forceDelete(User $user, Model $model): bool
    {
        return $this->userCan($user, Ability::FORCE_DELETE, $model);
    }

    protected function userCan(User $user, BackedEnum $ability, Model|string|null $model = null): bool
    {
        $modelToCheck = $model ?? $this->modelClass;

        return $user->can($this->registry->nameFromAbility($ability, $modelToCheck));
    }
}
```

> Note: `AbstractPolicy` references `App\Models\User`. This is acceptable for this in-app phase. When extracting to a package, `User` becomes `Illuminate\Contracts\Auth\Authenticatable`. Recorded for the extraction pass; not changed now to keep the diff minimal and the suite green.

- [ ] **Step 2: Delete the old AbstractPolicy**

```bash
rm app/Policies/AbstractPolicy.php
```

- [ ] **Step 3: Update UserPolicy and BasePolicyTest**

In `app/Policies/UserPolicy.php` add `use App\Authorization\AbstractPolicy;` (the class already references `AbstractPolicy` unqualified within `App\Policies`, so the import is now required):

```php
<?php

declare(strict_types=1);

namespace App\Policies;

use App\Authorization\AbstractPolicy;
use App\Models\User;

final readonly class UserPolicy extends AbstractPolicy
{
    protected function getModelClass(): string
    {
        return User::class;
    }
}
```

In `tests/Unit/Policies/BasePolicyTest.php` change `use App\Policies\AbstractPolicy;` → `use App\Authorization\AbstractPolicy;`.

- [ ] **Step 4: Verify no stale references**

Run: `grep -rn "App\\\\Policies\\\\AbstractPolicy" app/ tests/`
Expected: no output.

- [ ] **Step 5: Run policy tests**

Run: `vendor/bin/sail artisan test --group=policies`
Expected: PASS.

- [ ] **Step 6: Format and commit**

```bash
vendor/bin/sail bin pint --dirty --format agent
git add -A
git commit -m "refactor(auth): move AbstractPolicy into app/Authorization"
```

---

## Task 6: Move PermissionRegistry into the core and rewire to the manager

**Files:**
- Move: `app/Helpers/Policies/PermissionRegistry.php` → `app/Authorization/PermissionRegistry.php`
- Modify: `app/Authorization/AbstractPolicy.php`, `database/seeders/PermissionSeeder.php`, `database/seeders/RoleSeeder.php`, `app/Helpers/helpers.php`
- Move test: `tests/Unit/Helpers/Policies/PermissionRegistryTest.php` → `tests/Unit/Authorization/PermissionRegistryTest.php`

**Interfaces:**
- Produces: `App\Authorization\PermissionRegistry` with `__construct(AuthorizationManager $manager)`, `nameFromAbility(BackedEnum $ability, Model|string $model): string`, `allPermissions(): array` (iterates `manager->models()`). `forRole()`/`adminPermissions()`/`memberPermissions()` are **removed**.
- Consumes: `AuthorizationManager` (Task 2).

> This task depends on the `AuthorizationManager` being configured at runtime. The configuring provider lands in Task 7, but the manager is also configurable in tests directly. To keep this task green standalone, the moved test injects a manager it configures itself (it does not rely on the provider yet).

- [ ] **Step 1: Move and rewire the registry**

Create `app/Authorization/PermissionRegistry.php`:

```php
<?php

declare(strict_types=1);

namespace App\Authorization;

use BackedEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

final readonly class PermissionRegistry
{
    public function __construct(private AuthorizationManager $manager) {}

    public function nameFromAbility(BackedEnum $ability, Model|string $model): string
    {
        $table = $model instanceof Model ? $model->getTable() : $this->tableFor($model);

        return sprintf('%s %s', Str::snake($ability->value, ' '), str_replace('_', ' ', $table));
    }

    /** @return array<int, string> */
    public function allPermissions(): array
    {
        $permissions = [];

        foreach ($this->manager->models() as $modelClass) {
            foreach ($this->permissionsFor($modelClass) as $permission) {
                $permissions[] = $permission;
            }
        }

        return $permissions;
    }

    /**
     * @param  class-string  $modelClass
     * @return array<int, string>
     */
    private function permissionsFor(string $modelClass): array
    {
        $abilities = array_merge(
            $modelClass::getBasicAbilities(),
            $modelClass::getCustomAbilities(),
        );

        return array_map(
            fn (BackedEnum $ability): string => $this->nameFromAbility($ability, $modelClass),
            $abilities,
        );
    }

    /** @param class-string $modelClass */
    private function tableFor(string $modelClass): string
    {
        if (! class_exists($modelClass)) {
            return '';
        }

        return $modelClass::query()->newModelInstance()->getTable();
    }
}
```

- [ ] **Step 2: Delete the old registry**

```bash
rm app/Helpers/Policies/PermissionRegistry.php
rmdir app/Helpers/Policies 2>/dev/null || true
```

- [ ] **Step 3: Update AbstractPolicy import**

In `app/Authorization/AbstractPolicy.php` change `use App\Helpers\Policies\PermissionRegistry;` → `use App\Authorization\PermissionRegistry;`.

- [ ] **Step 4: Update seeders' import**

In `database/seeders/PermissionSeeder.php` and `database/seeders/RoleSeeder.php` change `use App\Helpers\Policies\PermissionRegistry;` → `use App\Authorization\PermissionRegistry;`. (Behavior is reworked in Task 7c; only the import changes here. `RoleSeeder` still calls `$this->registry->forRole(...)` which no longer exists — so temporarily, within this task, replace that call to keep green: see Step 5.)

- [ ] **Step 5: Keep RoleSeeder compiling**

`forRole()` is removed. In `database/seeders/RoleSeeder.php`, replace the line:

```php
$rolePermissions = $this->registry->forRole($roleEnum);
```

with:

```php
$rolePermissions = $roleEnum->permissions();
```

(This is the final form from the spec; Task 7c only adds the `PermissionSync` wrapper around it.)

- [ ] **Step 6: Remove the obsolete global helper**

Confirm `get_model_table()` has no other callers:

Run: `grep -rn "get_model_table" app/ database/ tests/ routes/`
Expected: only `app/Helpers/helpers.php` (the definition). Delete that function block from `app/Helpers/helpers.php`. If the file becomes empty of functions, leave the `<?php`/`declare` header intact.

- [ ] **Step 7: Move and update the registry test**

```bash
git mv tests/Unit/Helpers/Policies/PermissionRegistryTest.php tests/Unit/Authorization/PermissionRegistryTest.php
rmdir tests/Unit/Helpers/Policies 2>/dev/null || true
```

Replace the file contents of `tests/Unit/Authorization/PermissionRegistryTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Authorization;

use App\Authorization\AuthorizationManager;
use App\Authorization\Enums\Ability;
use App\Authorization\PermissionRegistry;
use App\Models\User;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[Group('policies')]
#[CoversClass(PermissionRegistry::class)]
final class PermissionRegistryTest extends TestCase
{
    private PermissionRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();

        $manager = new AuthorizationManager();
        $manager->useRoleEnum(\App\Enums\Policies\Role::class);
        $manager->authorizableModels([User::class]);

        $this->registry = new PermissionRegistry($manager);
    }

    /** @return array<string, array{Ability, string}> */
    public static function abilityToPermissionStringProvider(): array
    {
        return [
            'viewAny → view any users' => [Ability::VIEW_ANY, 'view any users'],
            'view → view users' => [Ability::VIEW, 'view users'],
            'create → create users' => [Ability::CREATE, 'create users'],
            'update → update users' => [Ability::UPDATE, 'update users'],
            'delete → delete users' => [Ability::DELETE, 'delete users'],
            'restore → restore users' => [Ability::RESTORE, 'restore users'],
            'forceDelete → force delete users' => [Ability::FORCE_DELETE, 'force delete users'],
        ];
    }

    #[Test]
    #[DataProvider('abilityToPermissionStringProvider')]
    public function it_converts_ability_and_model_class_to_permission_string(Ability $ability, string $expected): void
    {
        $this->assertSame($expected, $this->registry->nameFromAbility($ability, User::class));
    }

    #[Test]
    public function it_converts_ability_and_model_instance_to_permission_string(): void
    {
        $user = User::factory()->make();

        $this->assertSame('view users', $this->registry->nameFromAbility(Ability::VIEW, $user));
    }

    #[Test]
    public function all_permissions_reflects_the_configured_models(): void
    {
        $permissions = $this->registry->allPermissions();

        foreach (Ability::cases() as $ability) {
            $this->assertContains(
                $this->registry->nameFromAbility($ability, User::class),
                $permissions,
            );
        }
    }
}
```

- [ ] **Step 8: Verify no stale references**

Run: `grep -rn "Helpers\\\\Policies\\\\PermissionRegistry\|forRole(" app/ database/ tests/`
Expected: no output.

- [ ] **Step 9: Run tests**

Run: `vendor/bin/sail artisan test --group=policies`
Expected: PASS.

- [ ] **Step 10: Format and commit**

```bash
vendor/bin/sail bin pint --dirty --format agent
git add -A
git commit -m "refactor(auth): move PermissionRegistry into core and source models from manager"
```

---

## Task 7a: AuthorizationServiceProvider + BypassStrategy (role-based Gate::before)

**Files:**
- Create: `app/Authorization/Support/BypassStrategy.php`
- Create: `app/Providers/AuthorizationServiceProvider.php`
- Modify: `bootstrap/providers.php`, `app/Providers/AppServiceProvider.php`
- Test: `tests/Unit/Authorization/BypassStrategyTest.php`

**Interfaces:**
- Consumes: `AuthorizationManager` (Task 2), `Authorization` facade.
- Produces: `App\Authorization\Support\BypassStrategy::register(AuthorizationManager $manager): void` registering `Gate::before`. `AuthorizationServiceProvider` binds the manager singleton, configures it (`Role::class`, `[User::class]`), registers the bypass, and defines `SystemAbility::ACCESS_PLATFORM_ADMIN`.

- [ ] **Step 1: Write the failing test**

Create `tests/Unit/Authorization/BypassStrategyTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Authorization;

use App\Authorization\Enums\SystemAbility;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use App\Authorization\Support\BypassStrategy;
use Tests\TestCase;

#[Group('policies')]
#[CoversClass(BypassStrategy::class)]
final class BypassStrategyTest extends TestCase
{
    #[Test]
    public function super_admin_bypasses_any_ability(): void
    {
        $admin = User::factory()->admin()->create();

        $this->assertTrue(Gate::forUser($admin)->allows('any.unregistered.ability'));
    }

    #[Test]
    public function non_super_admin_does_not_bypass(): void
    {
        $member = User::factory()->member()->create();

        $this->assertFalse(Gate::forUser($member)->allows('any.unregistered.ability'));
    }

    #[Test]
    public function platform_admin_access_is_granted_to_super_admin_only(): void
    {
        $admin = User::factory()->admin()->create();
        $member = User::factory()->member()->create();

        $this->assertTrue(Gate::forUser($admin)->allows(SystemAbility::ACCESS_PLATFORM_ADMIN));
        $this->assertFalse(Gate::forUser($member)->allows(SystemAbility::ACCESS_PLATFORM_ADMIN));
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/sail artisan test --filter=BypassStrategyTest`
Expected: FAIL — `App\Authorization\Support\BypassStrategy` not found (and `member` still bypasses via the old `is_admin` gate until Step 5).

- [ ] **Step 3: Create BypassStrategy**

Create `app/Authorization/Support/BypassStrategy.php`:

```php
<?php

declare(strict_types=1);

namespace App\Authorization\Support;

use App\Authorization\AuthorizationManager;
use BackedEnum;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Gate;

final class BypassStrategy
{
    public static function register(AuthorizationManager $manager): void
    {
        Gate::before(static function (Authenticatable $user) use ($manager): ?bool {
            $superAdminRoleNames = array_map(
                static fn (BackedEnum $role): string => $role->value,
                $manager->superAdminRoles(),
            );

            if (! method_exists($user, 'hasAnyRole')) {
                return null;
            }

            return $user->hasAnyRole($superAdminRoleNames) ? true : null;
        });
    }
}
```

- [ ] **Step 4: Create AuthorizationServiceProvider**

Create `app/Providers/AuthorizationServiceProvider.php`:

```php
<?php

declare(strict_types=1);

namespace App\Providers;

use App\Authorization\Authorization;
use App\Authorization\AuthorizationManager;
use App\Authorization\Contracts\TeamResolver;
use App\Authorization\Enums\SystemAbility;
use App\Authorization\Support\BypassStrategy;
use App\Authorization\Teams\DefaultTeamResolver;
use App\Enums\Policies\Role;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

final class AuthorizationServiceProvider extends ServiceProvider
{
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
    }
}
```

- [ ] **Step 5: Register the provider and remove the old gate**

In `bootstrap/providers.php`, add `App\Providers\AuthorizationServiceProvider::class` to the returned array (after `AppServiceProvider::class`).

In `app/Providers/AppServiceProvider.php`:
- Remove the `registerAdminAccessGate()` method entirely.
- Remove its call from `boot()`.
- Remove the now-unused imports: `use App\Authorization\Enums\SystemAbility;` (if only used there) and `use Illuminate\Support\Facades\Gate;` (verify Gate isn't used elsewhere in the file — `registerLogViewerAuth()` uses `LogViewer::auth`, not `Gate`, so Gate becomes unused). Keep `use App\Models\User;` if still used by `enforceMorphMap()`.

- [ ] **Step 6: Run tests**

Run: `vendor/bin/sail artisan test --filter=BypassStrategyTest`
Expected: PASS (3 tests).

Then the full policy/users groups:

Run: `vendor/bin/sail artisan test --group=policies --group=users`
Expected: PASS.

- [ ] **Step 7: Format and commit**

```bash
vendor/bin/sail bin pint --dirty --format agent
git add -A
git commit -m "feat(auth): role-based Gate::before via BypassStrategy and AuthorizationServiceProvider"
```

---

## Task 7b: PermissionSync seeding engine

**Files:**
- Create: `app/Authorization/Database/PermissionSync.php`
- Modify: `database/seeders/PermissionSeeder.php`, `database/seeders/RoleSeeder.php`
- Test: `tests/Feature/Authorization/PermissionSyncTest.php`

**Interfaces:**
- Consumes: `PermissionRegistry` (Task 6), `AuthorizationManager` (Task 2), `AuthorizationRole::permissions()` (Task 1).
- Produces: `App\Authorization\Database\PermissionSync` with `permissions(): void` (firstOrCreate all permission names) and `roles(): void` (firstOrCreate each role, sync its `permissions()`).

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/Authorization/PermissionSyncTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Authorization;

use App\Authorization\Database\PermissionSync;
use App\Enums\Policies\Role;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role as SpatieRole;
use Tests\TestCase;

#[Group('policies')]
#[CoversClass(PermissionSync::class)]
final class PermissionSyncTest extends TestCase
{
    #[Test]
    public function it_creates_a_permission_for_every_user_ability(): void
    {
        resolve(PermissionSync::class)->permissions();

        $this->assertDatabaseHas('permissions', ['name' => 'view any users', 'guard_name' => 'web']);
        $this->assertDatabaseHas('permissions', ['name' => 'force delete users', 'guard_name' => 'web']);
        $this->assertSame(7, Permission::query()->count());
    }

    #[Test]
    public function it_creates_every_role(): void
    {
        resolve(PermissionSync::class)->permissions();
        resolve(PermissionSync::class)->roles();

        foreach (Role::cases() as $role) {
            $this->assertDatabaseHas('roles', ['name' => $role->value, 'guard_name' => 'web']);
        }

        $this->assertSame(count(Role::cases()), SpatieRole::query()->count());
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/sail artisan test --filter=PermissionSyncTest`
Expected: FAIL — `App\Authorization\Database\PermissionSync` not found.

- [ ] **Step 3: Create PermissionSync**

Create `app/Authorization/Database/PermissionSync.php`:

```php
<?php

declare(strict_types=1);

namespace App\Authorization\Database;

use App\Authorization\AuthorizationManager;
use App\Authorization\PermissionRegistry;

final readonly class PermissionSync
{
    public function __construct(
        private PermissionRegistry $registry,
        private AuthorizationManager $manager,
    ) {}

    public function permissions(): void
    {
        $permissionClass = config('permission.models.permission');

        foreach ($this->registry->allPermissions() as $permissionName) {
            $permissionClass::query()->firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);
        }
    }

    public function roles(): void
    {
        $roleClass = config('permission.models.role');
        $roleEnum = $this->manager->roleEnum();

        foreach ($roleEnum::cases() as $role) {
            $spatieRole = $roleClass::query()->firstOrCreate([
                'name' => $role->value,
                'guard_name' => 'web',
            ]);

            $rolePermissions = $role->permissions();

            if ($rolePermissions !== []) {
                $spatieRole->syncPermissions($rolePermissions);
            }
        }
    }
}
```

- [ ] **Step 4: Thin out the seeders**

Replace `database/seeders/PermissionSeeder.php`:

```php
<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Authorization\Database\PermissionSync;
use Illuminate\Database\Seeder;

final class PermissionSeeder extends Seeder
{
    public function __construct(private readonly PermissionSync $sync) {}

    public function run(): void
    {
        $this->sync->permissions();
    }
}
```

Replace `database/seeders/RoleSeeder.php`:

```php
<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Authorization\Database\PermissionSync;
use Illuminate\Database\Seeder;

final class RoleSeeder extends Seeder
{
    public function __construct(private readonly PermissionSync $sync) {}

    public function run(): void
    {
        $this->sync->roles();
    }
}
```

- [ ] **Step 5: Run tests**

Run: `vendor/bin/sail artisan test --filter=PermissionSyncTest`
Expected: PASS (2 tests).

Then confirm seeding still runs end to end:

Run: `vendor/bin/sail artisan db:seed --class=RoleAndPermissionSeeder --no-interaction`
Expected: completes without error.

- [ ] **Step 6: Format and commit**

```bash
vendor/bin/sail bin pint --dirty --format agent
git add -A
git commit -m "refactor(auth): add PermissionSync engine and thin the permission/role seeders"
```

---

## Task 8: Extract Role presentation out of the enum

**Files:**
- Create: `app/Support/Roles/HasRolePresentation.php`
- Modify: `app/Enums/Policies/Role.php`
- Test: existing blade-backed feature tests + `tests/Unit/Enums/Policies/RoleTest.php`

**Interfaces:**
- Produces: `App\Support\Roles\HasRolePresentation` trait providing `layout(): string` and `badgeColor(): string`. `Role` uses the trait; `label()` stays on the enum (it is part of `HasLabelsInterface`).

> Rationale: `Role` stays in the app, so presentation never reaches the package regardless. This extraction is tidiness — it keeps the enum body focused on values + the authorization contract. `layout()`/`badgeColor()` are admin-UI routing concerns and move to a trait; `label()` remains because it is governed by `HasLabelsInterface`/`HasLabels`.

- [ ] **Step 1: Add presentation assertions to RoleTest**

Append to `tests/Unit/Enums/Policies/RoleTest.php` (inside the class):

```php
#[Test]
public function admin_uses_the_platform_layout(): void
{
    $this->assertSame('platform', Role::ADMIN->layout());
}

#[Test]
public function member_uses_the_member_layout(): void
{
    $this->assertSame('member', Role::MEMBER->layout());
}

#[Test]
public function each_role_exposes_a_badge_color(): void
{
    $this->assertSame('red', Role::ADMIN->badgeColor());
    $this->assertSame('zinc', Role::MEMBER->badgeColor());
}
```

- [ ] **Step 2: Run to verify current state still passes (regression guard)**

Run: `vendor/bin/sail artisan test --filter=RoleTest`
Expected: PASS (methods still exist on the enum before extraction).

- [ ] **Step 3: Create the trait**

Create `app/Support/Roles/HasRolePresentation.php`:

```php
<?php

declare(strict_types=1);

namespace App\Support\Roles;

trait HasRolePresentation
{
    public function layout(): string
    {
        return match ($this) {
            self::ADMIN => 'platform',
            self::MEMBER => 'member',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::ADMIN => 'red',
            self::MEMBER => 'zinc',
        };
    }
}
```

- [ ] **Step 4: Use the trait in Role and remove the inline methods**

In `app/Enums/Policies/Role.php`:
- Add `use App\Support\Roles\HasRolePresentation;` (top imports) and `use HasRolePresentation;` in the trait-use block alongside `HasLabels`/`HasValues`.
- Delete the inline `layout()` and `badgeColor()` method bodies (now provided by the trait).
- Keep `label()`, `isSuperAdmin()`, `permissions()`, `default()`, `fromString()`, `toString()`.

- [ ] **Step 5: Run tests**

Run: `vendor/bin/sail artisan test --filter=RoleTest`
Expected: PASS (7 tests).

Then the admin feature group (blades call `layout()`/`badgeColor()`):

Run: `vendor/bin/sail artisan test --group=admin`
Expected: PASS.

- [ ] **Step 6: Format and commit**

```bash
vendor/bin/sail bin pint --dirty --format agent
git add -A
git commit -m "refactor(auth): extract Role layout/badge presentation into a trait"
```

---

## Task 9: Teams path (resolver + middleware, behind the Spatie flag)

**Files:**
- (Already created in Task 2: `Contracts/TeamResolver.php`, `Teams/DefaultTeamResolver.php`)
- Create: `app/Authorization/Teams/SetPermissionsTeam.php`
- Modify: `app/Providers/AuthorizationServiceProvider.php`
- Test: `tests/Unit/Authorization/Teams/DefaultTeamResolverTest.php`, `tests/Unit/Authorization/Teams/SetPermissionsTeamTest.php`

**Interfaces:**
- Consumes: `TeamResolver`, `AuthorizationManager::teamResolver()`.
- Produces: `App\Authorization\Teams\SetPermissionsTeam` middleware calling `setPermissionsTeamId()` from the resolved team id. Provider registers the middleware only when `config('permission.teams') === true`.

> Teams stay OFF in this project (`config('permission.teams') === false`). These units are tested in isolation by flipping config/state; the app is not switched to teams.

- [ ] **Step 1: Write the failing resolver test**

Create `tests/Unit/Authorization/Teams/DefaultTeamResolverTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Authorization\Teams;

use App\Authorization\Teams\DefaultTeamResolver;
use App\Models\User;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[Group('policies')]
#[CoversClass(DefaultTeamResolver::class)]
final class DefaultTeamResolverTest extends TestCase
{
    #[Test]
    public function it_returns_null_when_there_is_no_user(): void
    {
        $request = Request::create('/');

        $this->assertNull(resolve(DefaultTeamResolver::class)->resolve($request));
    }

    #[Test]
    public function it_reads_the_configured_team_foreign_key_from_the_user(): void
    {
        config(['permission.team_foreign_key' => 'team_id']);

        $user = User::factory()->make();
        $user->setAttribute('team_id', 42);

        $request = Request::create('/');
        $request->setUserResolver(static fn (): User => $user);

        $this->assertSame(42, resolve(DefaultTeamResolver::class)->resolve($request));
    }
}
```

- [ ] **Step 2: Run to verify it passes**

Run: `vendor/bin/sail artisan test --filter=DefaultTeamResolverTest`
Expected: PASS (resolver was created in Task 2). If it fails, the Task 2 stub is missing — create it as shown in Task 2 Step 3 note.

- [ ] **Step 3: Write the failing middleware test**

Create `tests/Unit/Authorization/Teams/SetPermissionsTeamTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Authorization\Teams;

use App\Authorization\Teams\SetPermissionsTeam;
use App\Models\User;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

#[Group('policies')]
#[CoversClass(SetPermissionsTeam::class)]
final class SetPermissionsTeamTest extends TestCase
{
    #[Test]
    public function it_sets_the_spatie_team_id_from_the_resolver(): void
    {
        config(['permission.team_foreign_key' => 'team_id']);

        $user = User::factory()->make();
        $user->setAttribute('team_id', 7);

        $request = Request::create('/');
        $request->setUserResolver(static fn (): User => $user);

        resolve(SetPermissionsTeam::class)->handle(
            $request,
            static fn (Request $request): Response => new Response(),
        );

        $this->assertSame(7, getPermissionsTeamId());
    }
}
```

- [ ] **Step 4: Run test to verify it fails**

Run: `vendor/bin/sail artisan test --filter=SetPermissionsTeamTest`
Expected: FAIL — `App\Authorization\Teams\SetPermissionsTeam` not found.

- [ ] **Step 5: Create the middleware**

Create `app/Authorization/Teams/SetPermissionsTeam.php`:

```php
<?php

declare(strict_types=1);

namespace App\Authorization\Teams;

use App\Authorization\AuthorizationManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class SetPermissionsTeam
{
    public function __construct(private AuthorizationManager $manager) {}

    public function handle(Request $request, Closure $next): Response
    {
        $teamId = $this->manager->teamResolver()->resolve($request);

        if ($teamId !== null) {
            setPermissionsTeamId($teamId);
        }

        return $next($request);
    }
}
```

- [ ] **Step 6: Wire the middleware behind the teams flag**

In `app/Providers/AuthorizationServiceProvider.php`, add to the end of `boot()`:

```php
if (config('permission.teams') === true) {
    $this->app->make(\Illuminate\Routing\Router::class)
        ->pushMiddlewareToGroup('web', SetPermissionsTeam::class);
}
```

Add `use App\Authorization\Teams\SetPermissionsTeam;` to the provider imports.

- [ ] **Step 7: Run tests**

Run: `vendor/bin/sail artisan test --filter=SetPermissionsTeamTest --filter=DefaultTeamResolverTest`
Expected: PASS.

Confirm teams is off and nothing regressed:

Run: `vendor/bin/sail artisan test --group=policies`
Expected: PASS.

- [ ] **Step 8: Format and commit**

```bash
vendor/bin/sail bin pint --dirty --format agent
git add -A
git commit -m "feat(auth): add teams resolver and SetPermissionsTeam middleware behind spatie flag"
```

---

## Task 10: make:authorization-policy generator

**Files:**
- Create: `app/Authorization/Console/MakePolicyCommand.php`
- Create: `app/Authorization/Console/stubs/policy.stub`
- Test: `tests/Feature/Authorization/MakePolicyCommandTest.php`

**Interfaces:**
- Produces: artisan command `make:authorization-policy {model}` generating `app/Policies/{Model}Policy.php` extending `App\Authorization\AbstractPolicy` with `getModelClass()` returning `App\Models\{Model}::class`.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/Authorization/MakePolicyCommandTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Authorization;

use App\Authorization\Console\MakePolicyCommand;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[Group('policies')]
#[CoversClass(MakePolicyCommand::class)]
final class MakePolicyCommandTest extends TestCase
{
    private string $generatedPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->generatedPath = app_path('Policies/WidgetPolicy.php');

        if (File::exists($this->generatedPath)) {
            File::delete($this->generatedPath);
        }
    }

    protected function tearDown(): void
    {
        if (File::exists($this->generatedPath)) {
            File::delete($this->generatedPath);
        }

        parent::tearDown();
    }

    #[Test]
    public function it_generates_a_policy_extending_the_abstract_policy(): void
    {
        $this->artisan('make:authorization-policy', ['model' => 'Widget'])
            ->assertExitCode(0);

        $this->assertTrue(File::exists($this->generatedPath));

        $contents = File::get($this->generatedPath);

        $this->assertStringContainsString('namespace App\Policies;', $contents);
        $this->assertStringContainsString('use App\Authorization\AbstractPolicy;', $contents);
        $this->assertStringContainsString('final readonly class WidgetPolicy extends AbstractPolicy', $contents);
        $this->assertStringContainsString('return Widget::class;', $contents);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/sail artisan test --filter=MakePolicyCommandTest`
Expected: FAIL — command/class not found.

- [ ] **Step 3: Create the stub**

Create `app/Authorization/Console/stubs/policy.stub`:

```text
<?php

declare(strict_types=1);

namespace App\Policies;

use App\Authorization\AbstractPolicy;
use App\Models\{{ model }};

final readonly class {{ model }}Policy extends AbstractPolicy
{
    protected function getModelClass(): string
    {
        return {{ model }}::class;
    }
}
```

- [ ] **Step 4: Create the command**

Create `app/Authorization/Console/MakePolicyCommand.php`:

```php
<?php

declare(strict_types=1);

namespace App\Authorization\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

final class MakePolicyCommand extends Command
{
    protected $signature = 'make:authorization-policy {model : The model class short name (e.g. Post)}';

    protected $description = 'Create a policy extending the authorization AbstractPolicy';

    public function handle(): int
    {
        $model = Str::studly((string) $this->argument('model'));
        $targetPath = app_path("Policies/{$model}Policy.php");

        if (File::exists($targetPath)) {
            $this->error("Policy already exists: {$targetPath}");

            return self::FAILURE;
        }

        $stub = File::get(__DIR__.'/stubs/policy.stub');
        $contents = str_replace('{{ model }}', $model, $stub);

        File::ensureDirectoryExists(app_path('Policies'));
        File::put($targetPath, $contents);

        $this->info("Policy created: {$targetPath}");

        return self::SUCCESS;
    }
}
```

> Commands in `app/Console/Commands/` auto-register in Laravel 13, but this command lives under `app/Authorization/Console/`. Register it explicitly so it is package-lift-ready. In `app/Providers/AuthorizationServiceProvider.php` `boot()`, add:
>
> ```php
> if ($this->app->runningInConsole()) {
>     $this->commands([MakePolicyCommand::class]);
> }
> ```
>
> and `use App\Authorization\Console\MakePolicyCommand;`.

- [ ] **Step 5: Run test to verify it passes**

Run: `vendor/bin/sail artisan test --filter=MakePolicyCommandTest`
Expected: PASS.

- [ ] **Step 6: Format and commit**

```bash
vendor/bin/sail bin pint --dirty --format agent
git add -A
git commit -m "feat(auth): add make:authorization-policy generator command and stub"
```

---

## Task 11: Update authorization docs

**Files:**
- Modify: `.claude/rules/authorization.md`
- Test: none (docs)

- [ ] **Step 1: Rewrite the doc to match the new design**

Update `.claude/rules/authorization.md` so it reflects:
- Bypass is **role-based** via `Role::isSuperAdmin()` + `BypassStrategy` (not `is_admin`). Replace the "`is_admin` Rule" section with: `is_admin`/`is_member` remain as model conveniences but are never used for authorization; `Gate::before` is registered by `BypassStrategy` from `Authorization::superAdminRoles()`.
- New namespaces: `App\Authorization\AbstractPolicy`, `App\Authorization\PermissionRegistry`, `App\Authorization\Enums\Ability`/`SystemAbility`, `App\Authorization\Concerns\HasPolicy`.
- Permission name generation via `PermissionRegistry::nameFromAbility()` (unchanged format).
- Adding a model: create policy via `php artisan make:authorization-policy {Model}`, add `HasPolicy` (from `App\Authorization\Concerns`), register the model in `AuthorizationServiceProvider::boot()` `authorizableModels([...])`, add the model to the `Gate::policy` map if needed, re-seed.
- Adding a role: add the case to `Role`, fill `isSuperAdmin()`/`permissions()`/`label()`/trait `layout()`/`badgeColor()` match arms, re-seed.
- Roles' permissions come from `Role::permissions()` (the `PermissionRegistry::forRole()` section is removed).
- Teams: read from `config('permission.teams')`; when on, `SetPermissionsTeam` middleware + `TeamResolver` are active.

- [ ] **Step 2: Commit**

```bash
git add .claude/rules/authorization.md
git commit -m "docs(auth): document role-based bypass and app/Authorization layout"
```

---

## Task 12: Full-suite verification

**Files:** none (verification)

- [ ] **Step 1: Run the entire suite**

Run: `vendor/bin/sail artisan test`
Expected: PASS, no skipped/incomplete.

- [ ] **Step 2: Verify the package-readiness invariant**

Run: `grep -rn "App\\\\Models\\\\|App\\\\Enums\\\\Policies\\\\Role" app/Authorization/`
Expected output: only `app/Authorization/AbstractPolicy.php` (the documented `App\Models\User` reference, slated for `Authenticatable` at extraction). No `App\Enums\Policies\Role` references anywhere in `app/Authorization/`.

- [ ] **Step 3: Confirm no leftover old namespaces**

Run: `grep -rn "Helpers\\\\Policies\|Policies\\\\Abilities\|Traits\\\\Models\\\\HasPolicy\|App\\\\Policies\\\\AbstractPolicy" app/ database/ tests/ resources/ routes/ config/`
Expected: no output.

- [ ] **Step 4: Final format pass**

```bash
vendor/bin/sail bin pint --dirty --format agent
```

If anything changed:

```bash
git add -A
git commit -m "style(auth): final pint pass"
```

---

## Self-Review Notes

- **Spec coverage:** §5.1 contracts → T1/T2/T9; §5.2 manager+facade → T2; §5.3 bypass → T7a; §5.4 teams → T9; §5.5 role presentation → T8; §5.6 registry+seeding → T6/T7b; §5.7 policy base+generator → T5/T10; §6 migration (moves, provider, touch list, role naming) → T3–T7a, T11; §8 testing → tests across T1–T10, T12; §10 resolved decisions honored (abilities→core T3, no rename T1, keep is_admin noted T7a/T11, English docs).
- **Open follow-up (out of scope, recorded):** `AbstractPolicy` still imports `App\Models\User`; at package-extraction time this becomes `Authenticatable` (noted in T5 and verified-as-known in T12 Step 2).
- **Type consistency:** `superAdminRoles()`, `models()`, `roleEnum()`, `teamResolver()`, `nameFromAbility()`, `allPermissions()`, `PermissionSync::permissions()/roles()`, `BypassStrategy::register()`, `TeamResolver::resolve()` are used identically across tasks.
