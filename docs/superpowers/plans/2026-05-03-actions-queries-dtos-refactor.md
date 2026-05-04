# Actions + Queries + DTOs Refactor — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the generic `Service → Repository` layer with single-purpose Action classes (writes), Query classes (reads), Eloquent scopes (predicates), and DTOs at boundaries — refactor `UserList` and add Create/Edit/Delete for `User` so the result becomes the canonical pattern for future resources.

**Architecture:** `Livewire/Controller → Action | Query → Eloquent Model (with scopes)`. Livewire validates and builds DTOs; actions perform writes inside `DB::transaction`; queries take a filters DTO and return a paginator. Authorization at the entry boundary (controller + Livewire), never in actions. Validation in Livewire components, never in DTOs.

**Tech Stack:** Laravel 13, Livewire 4 + Flux UI, Spatie Permission, PHPUnit 13, Sail (Docker).

**Spec:** [`docs/superpowers/specs/2026-05-03-actions-queries-dtos-refactor-design.md`](../specs/2026-05-03-actions-queries-dtos-refactor-design.md).

**Out-of-scope for this plan (deferred to a follow-up PR):** deletion of `app/Services/Models/`, `app/Repositories/Models/`, their contracts, `ModelRepositoryServiceProvider`, `tests/Generators/`, and the old service/repository test classes. Listed at the end of the spec.

---

## Setup

- [ ] **Step 0: Create a feature branch**

```bash
git checkout -b refactor/actions-queries-dtos
```

Confirm:
```bash
git status   # clean working tree apart from the previously-tracked User.php / migration mods
git branch   # * refactor/actions-queries-dtos
```

---

## Task 1: Add `badgeColor()` to `Role` enum

**Why:** The existing `user-list.blade.php` view calls `$user->roleCache->badgeColor()` — `roleCache` doesn't exist anywhere in the codebase, so the page currently errors. After Task 8 the view will use `$user->appRole->badgeColor()`; this task adds the method.

**Files:**
- Modify: `app/Enums/Policies/Role.php`
- Test: `tests/Unit/Enums/Policies/RoleTest.php`

- [ ] **Step 1.1: Write the failing test**

Create `tests/Unit/Enums/Policies/RoleTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Enums\Policies;

use App\Enums\Policies\Role;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class RoleTest extends TestCase
{
    #[Test]
    public function admin_uses_red_badge_color(): void
    {
        $this->assertSame('red', Role::ADMIN->badgeColor());
    }

    #[Test]
    public function member_uses_zinc_badge_color(): void
    {
        $this->assertSame('zinc', Role::MEMBER->badgeColor());
    }
}
```

- [ ] **Step 1.2: Run the test — expect failure**

```bash
vendor/bin/sail artisan test --filter=RoleTest
```

Expected: failure with `Error: Call to undefined method App\Enums\Policies\Role::badgeColor()`.

- [ ] **Step 1.3: Add the method**

In `app/Enums/Policies/Role.php`, after `public function label()`, add:

```php
public function badgeColor(): string
{
    return match ($this) {
        self::ADMIN => 'red',
        self::MEMBER => 'zinc',
    };
}
```

- [ ] **Step 1.4: Run the test — expect pass**

```bash
vendor/bin/sail artisan test --filter=RoleTest
```

Expected: 2 passed.

- [ ] **Step 1.5: Commit**

```bash
vendor/bin/sail bin pint --dirty --format agent
git add app/Enums/Policies/Role.php tests/Unit/Enums/Policies/RoleTest.php
git commit -m "feat(role): add badgeColor() for list views"
```

---

## Task 2: Fix `UserPolicy::viewAny` (pre-existing bug)

**Why:** The current `viewAny` references `Role::SUPER_ADMIN` and `Role::TEAM_LEAD`, neither of which exist on the `Role` enum. When an admin loads `/admin/users`, this throws `Error: Undefined constant`. The refactor depends on `Gate::authorize(Ability::VIEW_ANY, User::class)` working.

**Files:**
- Modify: `app/Policies/UserPolicy.php`
- Test: `tests/Unit/Policies/UserPolicyTest.php`

- [ ] **Step 2.1: Write the failing test**

Create `tests/Unit/Policies/UserPolicyTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Models\User;
use App\Policies\UserPolicy;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class UserPolicyTest extends TestCase
{
    #[Test]
    public function admin_can_view_any_users(): void
    {
        $admin = User::factory()->admin()->create();

        $this->assertTrue((new UserPolicy())->viewAny($admin));
    }

    #[Test]
    public function member_cannot_view_any_users(): void
    {
        $member = User::factory()->member()->create();

        $this->assertFalse((new UserPolicy())->viewAny($member));
    }
}
```

- [ ] **Step 2.2: Run the test — expect failure**

```bash
vendor/bin/sail artisan test --filter=UserPolicyTest
```

Expected: failure with `Error: Undefined constant App\Enums\Policies\Role::SUPER_ADMIN`.

- [ ] **Step 2.3: Replace `viewAny`**

In `app/Policies/UserPolicy.php`, replace the `viewAny` method body:

```php
public function viewAny(User $user): bool
{
    return $user->app_role === Role::ADMIN;
}
```

(The existing `use App\Enums\Policies\Role;` line at the top of the file is already in place.)

- [ ] **Step 2.4: Run the test — expect pass**

```bash
vendor/bin/sail artisan test --filter=UserPolicyTest
```

Expected: 2 passed.

- [ ] **Step 2.5: Commit**

```bash
vendor/bin/sail bin pint --dirty --format agent
git add app/Policies/UserPolicy.php tests/Unit/Policies/UserPolicyTest.php
git commit -m "fix(policy): UserPolicy::viewAny referenced undefined Role cases"
```

---

## Task 3: `User` model scopes — `search` and `withRole`

**Files:**
- Modify: `app/Models/User.php`
- Test: `tests/Unit/Models/UserScopesTest.php`

- [ ] **Step 3.1: Write the failing test**

Create `tests/Unit/Models/UserScopesTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\Policies\Role;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class UserScopesTest extends TestCase
{
    #[Test]
    public function search_matches_first_name_last_name_or_email_case_insensitively(): void
    {
        User::factory()->create(['first_name' => 'Alice', 'last_name' => 'Cooper', 'email' => 'a@example.test']);
        User::factory()->create(['first_name' => 'Bob', 'last_name' => 'Dylan', 'email' => 'b@example.test']);
        User::factory()->create(['first_name' => 'Carol', 'last_name' => 'Smith', 'email' => 'carol@example.test']);

        $this->assertSame(2, User::query()->search('co')->count(), 'Cooper + Carol');
        $this->assertSame(1, User::query()->search('dylan')->count());
        $this->assertSame(1, User::query()->search('CAROL@')->count());
    }

    #[Test]
    public function search_with_empty_string_returns_all_users(): void
    {
        User::factory()->count(3)->create();

        $this->assertSame(3, User::query()->search('')->count());
    }

    #[Test]
    public function with_role_filters_users_by_role(): void
    {
        User::factory()->admin()->count(2)->create();
        User::factory()->member()->count(3)->create();

        $this->assertSame(2, User::query()->withRole(Role::ADMIN)->count());
        $this->assertSame(3, User::query()->withRole(Role::MEMBER)->count());
    }
}
```

- [ ] **Step 3.2: Run the test — expect failure**

```bash
vendor/bin/sail artisan test --filter=UserScopesTest
```

Expected: failure (`BadMethodCallException: Call to undefined method ...::search()`).

- [ ] **Step 3.3: Add the scopes**

In `app/Models/User.php`, add the imports and methods. Add to imports near the top:

```php
use App\Enums\Policies\Role as RoleEnum;   // already present — verify
use Illuminate\Database\Eloquent\Builder;
```

Then add these two methods inside the `User` class (e.g. just before `casts()`):

```php
public function scopeSearch(Builder $query, string $term): void
{
    if ($term === '') {
        return;
    }

    $like = '%'.$term.'%';

    $query->where(function (Builder $q) use ($like): void {
        $q->where('first_name', 'ilike', $like)
            ->orWhere('last_name', 'ilike', $like)
            ->orWhere('email', 'ilike', $like);
    });
}

public function scopeWithRole(Builder $query, RoleEnum $role): void
{
    $query->whereHas('roles', fn (Builder $q) => $q->where('name', $role->value));
}
```

- [ ] **Step 3.4: Run the test — expect pass**

```bash
vendor/bin/sail artisan test --filter=UserScopesTest
```

Expected: 3 passed.

- [ ] **Step 3.5: Commit**

```bash
vendor/bin/sail bin pint --dirty --format agent
git add app/Models/User.php tests/Unit/Models/UserScopesTest.php
git commit -m "feat(user): add search and withRole scopes"
```

---

## Task 4: `ListUsersFilters` DTO

**Files:**
- Create: `app/DTO/Users/ListUsersFilters.php`

This DTO is a plain readonly value with all-default-able properties. There is no behavior to test in isolation — its correctness is proven by `ListUsersQuery` tests in Task 7. Skip per-DTO unit test; do not write a placeholder test.

- [ ] **Step 4.1: Create the DTO**

Create `app/Dto/Users/ListUsersFilters.php`:

```php
<?php

declare(strict_types=1);

namespace App\Dto\Users;

final readonly class ListUsersFilters
{
    public function __construct(
        public string $search = '',
        public string $sort = 'created_at',
        public string $direction = 'desc',
        public int $perPage = 15,
    ) {}
}
```

- [ ] **Step 4.2: Verify it autoloads**

```bash
vendor/bin/sail artisan tinker --execute 'dump(new App\Dto\Users\ListUsersFilters(search: "x"));'
```

Expected: dumps the object showing `search: "x"`.

- [ ] **Step 4.3: Commit**

```bash
vendor/bin/sail bin pint --dirty --format agent
git add app/Dto/Users/ListUsersFilters.php
git commit -m "feat(dto): add ListUsersFilters"
```

---

## Task 5: `CreateUserInput` DTO

**Files:**
- Create: `app/Dto/Users/CreateUserInput.php`
- Test: `tests/Unit/Dto/Users/CreateUserInputTest.php`

- [ ] **Step 5.1: Write the failing test**

Create `tests/Unit/Dto/Users/CreateUserInputTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Dto\Users;

use App\Dto\Users\CreateUserInput;
use App\Enums\Policies\Role;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class CreateUserInputTest extends TestCase
{
    #[Test]
    public function from_array_maps_validated_data_to_typed_dto(): void
    {
        $dto = CreateUserInput::fromArray([
            'first_name' => 'Alice',
            'last_name' => 'Cooper',
            'email' => 'alice@example.test',
            'password' => 'secret-pass',
            'role' => 'admin',
        ]);

        $this->assertSame('Alice', $dto->firstName);
        $this->assertSame('Cooper', $dto->lastName);
        $this->assertSame('alice@example.test', $dto->email);
        $this->assertSame('secret-pass', $dto->password);
        $this->assertSame(Role::ADMIN, $dto->role);
    }

    #[Test]
    public function from_array_throws_on_unknown_role(): void
    {
        $this->expectException(\ValueError::class);

        CreateUserInput::fromArray([
            'first_name' => 'A',
            'last_name' => 'B',
            'email' => 'a@b.test',
            'password' => 'x',
            'role' => 'super-admin',
        ]);
    }
}
```

- [ ] **Step 5.2: Run the test — expect failure**

```bash
vendor/bin/sail artisan test --filter=CreateUserInputTest
```

Expected: failure (class does not exist).

- [ ] **Step 5.3: Create the DTO**

Create `app/Dto/Users/CreateUserInput.php`:

```php
<?php

declare(strict_types=1);

namespace App\Dto\Users;

use App\Enums\Policies\Role;

final readonly class CreateUserInput
{
    public function __construct(
        public string $firstName,
        public string $lastName,
        public string $email,
        public string $password,
        public Role $role,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            firstName: $data['first_name'],
            lastName: $data['last_name'],
            email: $data['email'],
            password: $data['password'],
            role: Role::from($data['role']),
        );
    }
}
```

- [ ] **Step 5.4: Run the test — expect pass**

```bash
vendor/bin/sail artisan test --filter=CreateUserInputTest
```

Expected: 2 passed.

- [ ] **Step 5.5: Commit**

```bash
vendor/bin/sail bin pint --dirty --format agent
git add app/Dto/Users/CreateUserInput.php tests/Unit/Dto/Users/CreateUserInputTest.php
git commit -m "feat(dto): add CreateUserInput"
```

---

## Task 6: `UpdateUserInput` DTO

**Files:**
- Create: `app/Dto/Users/UpdateUserInput.php`
- Test: `tests/Unit/Dto/Users/UpdateUserInputTest.php`

- [ ] **Step 6.1: Write the failing test**

Create `tests/Unit/Dto/Users/UpdateUserInputTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Dto\Users;

use App\Dto\Users\UpdateUserInput;
use App\Enums\Policies\Role;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class UpdateUserInputTest extends TestCase
{
    #[Test]
    public function from_array_returns_provided_password_as_is(): void
    {
        $dto = UpdateUserInput::fromArray([
            'first_name' => 'Alice',
            'last_name' => 'Cooper',
            'email' => 'a@b.test',
            'password' => 'new-pass',
            'role' => 'member',
        ]);

        $this->assertSame('new-pass', $dto->password);
        $this->assertSame(Role::MEMBER, $dto->role);
    }

    #[Test]
    public function from_array_normalizes_empty_string_password_to_null(): void
    {
        $dto = UpdateUserInput::fromArray([
            'first_name' => 'Alice',
            'last_name' => 'Cooper',
            'email' => 'a@b.test',
            'password' => '',
            'role' => 'member',
        ]);

        $this->assertNull($dto->password);
    }

    #[Test]
    public function from_array_treats_missing_password_as_null(): void
    {
        $dto = UpdateUserInput::fromArray([
            'first_name' => 'Alice',
            'last_name' => 'Cooper',
            'email' => 'a@b.test',
            'role' => 'member',
        ]);

        $this->assertNull($dto->password);
    }
}
```

- [ ] **Step 6.2: Run the test — expect failure**

```bash
vendor/bin/sail artisan test --filter=UpdateUserInputTest
```

Expected: failure (class does not exist).

- [ ] **Step 6.3: Create the DTO**

Create `app/Dto/Users/UpdateUserInput.php`:

```php
<?php

declare(strict_types=1);

namespace App\Dto\Users;

use App\Enums\Policies\Role;

final readonly class UpdateUserInput
{
    public function __construct(
        public string $firstName,
        public string $lastName,
        public string $email,
        public ?string $password,
        public Role $role,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $password = $data['password'] ?? null;

        return new self(
            firstName: $data['first_name'],
            lastName: $data['last_name'],
            email: $data['email'],
            password: $password === '' ? null : $password,
            role: Role::from($data['role']),
        );
    }
}
```

- [ ] **Step 6.4: Run the test — expect pass**

```bash
vendor/bin/sail artisan test --filter=UpdateUserInputTest
```

Expected: 3 passed.

- [ ] **Step 6.5: Commit**

```bash
vendor/bin/sail bin pint --dirty --format agent
git add app/Dto/Users/UpdateUserInput.php tests/Unit/Dto/Users/UpdateUserInputTest.php
git commit -m "feat(dto): add UpdateUserInput"
```

---

## Task 7: `ListUsersQuery`

**Files:**
- Create: `app/Queries/Users/ListUsersQuery.php`
- Test: `tests/Unit/Queries/Users/ListUsersQueryTest.php`

- [ ] **Step 7.1: Write the failing test**

Create `tests/Unit/Queries/Users/ListUsersQueryTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Queries\Users;

use App\Dto\Users\ListUsersFilters;
use App\Models\User;
use App\Queries\Users\ListUsersQuery;
use Illuminate\Pagination\LengthAwarePaginator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ListUsersQueryTest extends TestCase
{
    #[Test]
    public function returns_paginator_of_all_users_when_filters_are_default(): void
    {
        User::factory()->count(3)->create();

        $result = (new ListUsersQuery())->handle(new ListUsersFilters());

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertSame(3, $result->total());
    }

    #[Test]
    public function search_filter_is_applied(): void
    {
        User::factory()->create(['first_name' => 'Alice']);
        User::factory()->create(['first_name' => 'Bob']);

        $result = (new ListUsersQuery())->handle(new ListUsersFilters(search: 'alice'));

        $this->assertSame(1, $result->total());
    }

    #[Test]
    public function sorts_by_name_ascending(): void
    {
        User::factory()->create(['first_name' => 'Charlie', 'last_name' => 'Z']);
        User::factory()->create(['first_name' => 'Alice', 'last_name' => 'A']);
        User::factory()->create(['first_name' => 'Bob', 'last_name' => 'B']);

        $result = (new ListUsersQuery())->handle(new ListUsersFilters(sort: 'name', direction: 'asc'));

        $names = $result->getCollection()->map(fn (User $u): string => $u->first_name)->all();
        $this->assertSame(['Alice', 'Bob', 'Charlie'], $names);
    }

    #[Test]
    public function respects_per_page(): void
    {
        User::factory()->count(20)->create();

        $result = (new ListUsersQuery())->handle(new ListUsersFilters(perPage: 5));

        $this->assertSame(5, $result->perPage());
        $this->assertSame(20, $result->total());
    }
}
```

- [ ] **Step 7.2: Run the test — expect failure**

```bash
vendor/bin/sail artisan test --filter=ListUsersQueryTest
```

Expected: failure (class does not exist).

- [ ] **Step 7.3: Create the query**

Create `app/Queries/Users/ListUsersQuery.php`:

```php
<?php

declare(strict_types=1);

namespace App\Queries\Users;

use App\Dto\Users\ListUsersFilters;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

final readonly class ListUsersQuery
{
    /** @return LengthAwarePaginator<int, User> */
    public function handle(ListUsersFilters $filters): LengthAwarePaginator
    {
        $query = User::query()->search($filters->search);

        if ($filters->sort === 'name') {
            $query->orderBy('first_name', $filters->direction)
                ->orderBy('last_name', $filters->direction);
        } else {
            $query->orderBy($filters->sort, $filters->direction);
        }

        return $query->paginate($filters->perPage);
    }
}
```

- [ ] **Step 7.4: Run the test — expect pass**

```bash
vendor/bin/sail artisan test --filter=ListUsersQueryTest
```

Expected: 4 passed.

- [ ] **Step 7.5: Commit**

```bash
vendor/bin/sail bin pint --dirty --format agent
git add app/Queries/Users/ListUsersQuery.php tests/Unit/Queries/Users/ListUsersQueryTest.php
git commit -m "feat(query): add ListUsersQuery"
```

---

## Task 8: Refactor `UserList` Livewire + fix view

**Why:** Replace direct `User::query()->where(...)` calls with the new `ListUsersQuery`/`ListUsersFilters`. The view also references `$user->roleCache->badgeColor()` (undefined) — replace with `$user->appRole->badgeColor()`.

**Files:**
- Modify: `app/Livewire/Admin/Users/UserList.php`
- Modify: `resources/views/livewire/admin/users/user-list.blade.php`
- Test: `tests/Feature/Admin/Users/UserListTest.php`

- [ ] **Step 8.1: Write the failing test**

Create `tests/Feature/Admin/Users/UserListTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Users;

use App\Livewire\Admin\Users\UserList;
use App\Models\User;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class UserListTest extends TestCase
{
    #[Test]
    public function renders_users_for_admin(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->create(['first_name' => 'Alice']);
        User::factory()->create(['first_name' => 'Bob']);

        $this->actingAs($admin);

        Livewire::test(UserList::class)
            ->assertOk()
            ->assertSee('Alice')
            ->assertSee('Bob');
    }

    #[Test]
    public function search_filters_users(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->create(['first_name' => 'Alice']);
        User::factory()->create(['first_name' => 'Bob']);

        $this->actingAs($admin);

        Livewire::test(UserList::class)
            ->set('search', 'Alice')
            ->assertSee('Alice')
            ->assertDontSee('Bob');
    }

    #[Test]
    public function sort_by_toggles_direction(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        Livewire::test(UserList::class)
            ->call('sortBy', 'name')
            ->assertSet('sort', 'name')
            ->assertSet('direction', 'desc')
            ->call('sortBy', 'name')
            ->assertSet('direction', 'asc');
    }

    #[Test]
    public function clear_filters_resets_state(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        Livewire::test(UserList::class)
            ->set('search', 'x')
            ->set('sort', 'name')
            ->set('direction', 'asc')
            ->call('clearFilters')
            ->assertSet('search', '')
            ->assertSet('sort', 'created_at')
            ->assertSet('direction', 'desc');
    }
}
```

- [ ] **Step 8.2: Run the test — expect failure**

```bash
vendor/bin/sail artisan test --filter=UserListTest
```

Expected: failure — likely the render error due to `roleCache` (current view) and/or assertions on the new behavior.

- [ ] **Step 8.3: Refactor the component**

Replace the contents of `app/Livewire/Admin/Users/UserList.php` with:

```php
<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Users;

use App\Dto\Users\ListUsersFilters;
use App\Models\User;
use App\Queries\Users\ListUsersQuery;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

final class UserList extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    public string $sort = 'created_at';

    public string $direction = 'desc';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $column): void
    {
        if ($this->sort === $column) {
            $this->direction = $this->direction === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sort = $column;
            $this->direction = 'desc';
        }

        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->sort = 'created_at';
        $this->direction = 'desc';

        $this->resetPage();
    }

    /**
     * @return LengthAwarePaginator<int, User>
     */
    #[Computed]
    public function users(ListUsersQuery $query): LengthAwarePaginator
    {
        return $query->handle(new ListUsersFilters(
            search: $this->search,
            sort: $this->sort,
            direction: $this->direction,
        ));
    }

    public function render(): View
    {
        return view('livewire.admin.users.user-list');
    }
}
```

- [ ] **Step 8.4: Fix the view's role rendering**

In `resources/views/livewire/admin/users/user-list.blade.php`, replace the role `<td>` block (the one currently using `$user->roleCache`) with:

```blade
<td class="px-6 py-4">
    <flux:badge :color="$user->appRole->badgeColor()" size="sm">
        {{ $user->appRole->label() }}
    </flux:badge>
</td>
```

(Leave the rest of the file untouched.)

- [ ] **Step 8.5: Run the test — expect pass**

```bash
vendor/bin/sail artisan test --filter=UserListTest
```

Expected: 4 passed.

- [ ] **Step 8.6: Commit**

```bash
vendor/bin/sail bin pint --dirty --format agent
git add app/Livewire/Admin/Users/UserList.php resources/views/livewire/admin/users/user-list.blade.php tests/Feature/Admin/Users/UserListTest.php
git commit -m "refactor(user-list): use ListUsersQuery + DTO; fix role rendering"
```

---

## Task 9: `CreateUser` action

**Files:**
- Create: `app/Actions/Users/CreateUser.php`
- Test: `tests/Unit/Actions/Users/CreateUserTest.php`

- [ ] **Step 9.1: Write the failing test**

Create `tests/Unit/Actions/Users/CreateUserTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\Users;

use App\Actions\Users\CreateUser;
use App\Dto\Users\CreateUserInput;
use App\Enums\Policies\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class CreateUserTest extends TestCase
{
    #[Test]
    public function creates_user_with_hashed_password_and_role(): void
    {
        $action = $this->app->make(CreateUser::class);

        $user = $action(new CreateUserInput(
            firstName: 'Alice',
            lastName: 'Cooper',
            email: 'alice@example.test',
            password: 'plain-pass',
            role: Role::ADMIN,
        ));

        $this->assertInstanceOf(User::class, $user);
        $this->assertSame('Alice', $user->first_name);
        $this->assertSame('Cooper', $user->last_name);
        $this->assertSame('alice@example.test', $user->email);
        $this->assertNotSame('plain-pass', $user->password);
        $this->assertTrue(Hash::check('plain-pass', $user->password));
        $this->assertSame(Role::ADMIN, $user->appRole);
    }
}
```

- [ ] **Step 9.2: Run the test — expect failure**

```bash
vendor/bin/sail artisan test --filter=CreateUserTest
```

Expected: failure (class does not exist).

- [ ] **Step 9.3: Create the action**

Create `app/Actions/Users/CreateUser.php`:

```php
<?php

declare(strict_types=1);

namespace App\Actions\Users;

use App\Dto\Users\CreateUserInput;
use App\Models\User;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Support\Facades\DB;

final readonly class CreateUser
{
    public function __construct(private Hasher $hasher) {}

    public function __invoke(CreateUserInput $input): User
    {
        return DB::transaction(function () use ($input): User {
            $user = User::query()->create([
                'first_name' => $input->firstName,
                'last_name' => $input->lastName,
                'email' => $input->email,
                'password' => $this->hasher->make($input->password),
            ]);

            $user->syncRoles([$input->role->value]);

            return $user;
        });
    }
}
```

- [ ] **Step 9.4: Run the test — expect pass**

```bash
vendor/bin/sail artisan test --filter=CreateUserTest
```

Expected: 1 passed.

- [ ] **Step 9.5: Commit**

```bash
vendor/bin/sail bin pint --dirty --format agent
git add app/Actions/Users/CreateUser.php tests/Unit/Actions/Users/CreateUserTest.php
git commit -m "feat(action): add CreateUser"
```

---

## Task 10: `UpdateUser` action

**Files:**
- Create: `app/Actions/Users/UpdateUser.php`
- Test: `tests/Unit/Actions/Users/UpdateUserTest.php`

- [ ] **Step 10.1: Write the failing test**

Create `tests/Unit/Actions/Users/UpdateUserTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\Users;

use App\Actions\Users\UpdateUser;
use App\Dto\Users\UpdateUserInput;
use App\Enums\Policies\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class UpdateUserTest extends TestCase
{
    #[Test]
    public function updates_fields_and_role_and_rehashes_password_when_provided(): void
    {
        $user = User::factory()->member()->create([
            'first_name' => 'Old',
            'last_name' => 'Name',
            'email' => 'old@example.test',
        ]);
        $originalHash = $user->password;
        $action = $this->app->make(UpdateUser::class);

        $updated = $action($user, new UpdateUserInput(
            firstName: 'New',
            lastName: 'Name',
            email: 'new@example.test',
            password: 'fresh-pass',
            role: Role::ADMIN,
        ));

        $this->assertSame('New', $updated->first_name);
        $this->assertSame('new@example.test', $updated->email);
        $this->assertNotSame($originalHash, $updated->password);
        $this->assertTrue(Hash::check('fresh-pass', $updated->password));
        $this->assertSame(Role::ADMIN, $updated->appRole);
    }

    #[Test]
    public function leaves_password_unchanged_when_input_password_is_null(): void
    {
        $user = User::factory()->member()->create();
        $originalHash = $user->password;
        $action = $this->app->make(UpdateUser::class);

        $updated = $action($user, new UpdateUserInput(
            firstName: $user->first_name,
            lastName: $user->last_name,
            email: $user->email,
            password: null,
            role: Role::MEMBER,
        ));

        $this->assertSame($originalHash, $updated->password);
    }

    #[Test]
    public function does_not_resync_roles_when_role_unchanged(): void
    {
        $user = User::factory()->member()->create();
        $action = $this->app->make(UpdateUser::class);

        $updated = $action($user, new UpdateUserInput(
            firstName: $user->first_name,
            lastName: $user->last_name,
            email: $user->email,
            password: null,
            role: Role::MEMBER,
        ));

        $this->assertSame(Role::MEMBER, $updated->appRole);
    }
}
```

- [ ] **Step 10.2: Run the test — expect failure**

```bash
vendor/bin/sail artisan test --filter=UpdateUserTest
```

Expected: failure (class does not exist).

- [ ] **Step 10.3: Create the action**

Create `app/Actions/Users/UpdateUser.php`:

```php
<?php

declare(strict_types=1);

namespace App\Actions\Users;

use App\Dto\Users\UpdateUserInput;
use App\Models\User;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Support\Facades\DB;

final readonly class UpdateUser
{
    public function __construct(private Hasher $hasher) {}

    public function __invoke(User $user, UpdateUserInput $input): User
    {
        return DB::transaction(function () use ($user, $input): User {
            $attributes = [
                'first_name' => $input->firstName,
                'last_name' => $input->lastName,
                'email' => $input->email,
            ];

            if ($input->password !== null) {
                $attributes['password'] = $this->hasher->make($input->password);
            }

            $user->update($attributes);

            if ($user->appRole !== $input->role) {
                $user->syncRoles([$input->role->value]);
            }

            return $user->refresh();
        });
    }
}
```

- [ ] **Step 10.4: Run the test — expect pass**

```bash
vendor/bin/sail artisan test --filter=UpdateUserTest
```

Expected: 3 passed.

- [ ] **Step 10.5: Commit**

```bash
vendor/bin/sail bin pint --dirty --format agent
git add app/Actions/Users/UpdateUser.php tests/Unit/Actions/Users/UpdateUserTest.php
git commit -m "feat(action): add UpdateUser"
```

---

## Task 11: `DeleteUser` action

**Files:**
- Create: `app/Actions/Users/DeleteUser.php`
- Test: `tests/Unit/Actions/Users/DeleteUserTest.php`

- [ ] **Step 11.1: Write the failing test**

Create `tests/Unit/Actions/Users/DeleteUserTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\Users;

use App\Actions\Users\DeleteUser;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class DeleteUserTest extends TestCase
{
    #[Test]
    public function soft_deletes_the_user(): void
    {
        $user = User::factory()->member()->create();
        $action = new DeleteUser();

        $action($user);

        $this->assertSoftDeleted('users', ['id' => $user->id]);
    }
}
```

- [ ] **Step 11.2: Run the test — expect failure**

```bash
vendor/bin/sail artisan test --filter=DeleteUserTest
```

Expected: failure (class does not exist).

- [ ] **Step 11.3: Create the action**

Create `app/Actions/Users/DeleteUser.php`:

```php
<?php

declare(strict_types=1);

namespace App\Actions\Users;

use App\Models\User;

final readonly class DeleteUser
{
    public function __invoke(User $user): void
    {
        $user->delete();
    }
}
```

- [ ] **Step 11.4: Run the test — expect pass**

```bash
vendor/bin/sail artisan test --filter=DeleteUserTest
```

Expected: 1 passed.

- [ ] **Step 11.5: Commit**

```bash
vendor/bin/sail bin pint --dirty --format agent
git add app/Actions/Users/DeleteUser.php tests/Unit/Actions/Users/DeleteUserTest.php
git commit -m "feat(action): add DeleteUser"
```

---

## Task 12: `CreateUser` Livewire component, page, route, layout button

**Files:**
- Create: `app/Livewire/Admin/Users/CreateUser.php`
- Create: `resources/views/livewire/admin/users/create-user.blade.php`
- Create: `resources/views/livewire/admin/users/partials/form.blade.php`
- Create: `resources/views/pages/admin/users/create.blade.php`
- Modify: `app/Http/Controllers/Admin/UserController.php`
- Modify: `routes/admin/platform.php`
- Modify: `resources/views/components/admin/users/layout.blade.php`
- Test: `tests/Feature/Admin/Users/CreateUserTest.php`

- [ ] **Step 12.1: Write the failing test**

Create `tests/Feature/Admin/Users/CreateUserTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Users;

use App\Enums\Policies\Role;
use App\Livewire\Admin\Users\CreateUser;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class CreateUserTest extends TestCase
{
    #[Test]
    public function admin_can_view_the_create_page(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.platform.users.create'))
            ->assertOk()
            ->assertSeeLivewire(CreateUser::class);
    }

    #[Test]
    public function member_cannot_view_the_create_page(): void
    {
        $member = User::factory()->member()->create();

        $this->actingAs($member)
            ->get(route('admin.platform.users.create'))
            ->assertForbidden();
    }

    #[Test]
    public function admin_can_create_a_user(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        Livewire::test(CreateUser::class)
            ->set('first_name', 'Alice')
            ->set('last_name', 'Cooper')
            ->set('email', 'alice@example.test')
            ->set('password', 'secret-pass')
            ->set('role', Role::MEMBER->value)
            ->call('store')
            ->assertHasNoErrors()
            ->assertRedirect();

        $created = User::query()->where('email', 'alice@example.test')->firstOrFail();
        $this->assertSame('Alice', $created->first_name);
        $this->assertTrue(Hash::check('secret-pass', $created->password));
        $this->assertSame(Role::MEMBER, $created->appRole);
    }

    #[Test]
    public function validation_requires_required_fields(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        Livewire::test(CreateUser::class)
            ->call('store')
            ->assertHasErrors(['first_name', 'last_name', 'email', 'password', 'role']);
    }

    #[Test]
    public function validation_rejects_duplicate_email(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->create(['email' => 'taken@example.test']);
        $this->actingAs($admin);

        Livewire::test(CreateUser::class)
            ->set('first_name', 'A')
            ->set('last_name', 'B')
            ->set('email', 'taken@example.test')
            ->set('password', 'secret-pass')
            ->set('role', Role::MEMBER->value)
            ->call('store')
            ->assertHasErrors(['email']);
    }
}
```

- [ ] **Step 12.2: Run the test — expect failure**

```bash
vendor/bin/sail artisan test --filter='Tests\\Feature\\Admin\\Users\\CreateUserTest'
```

Expected: failure (route does not exist; component does not exist).

- [ ] **Step 12.3: Add the route**

Replace the body of `routes/admin/platform.php` with:

```php
<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('users')
    ->name('users.')
    ->controller(UserController::class)
    ->group(function (): void {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
    });
```

- [ ] **Step 12.4: Add the controller method**

Replace `app/Http/Controllers/Admin/UserController.php` with:

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\Policies\Ability;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

final class UserController extends Controller
{
    public function index(): View
    {
        Gate::authorize(Ability::VIEW_ANY, User::class);

        return view('pages.admin.users.index');
    }

    public function create(): View
    {
        Gate::authorize(Ability::CREATE, User::class);

        return view('pages.admin.users.create');
    }
}
```

- [ ] **Step 12.5: Create the page view**

Create `resources/views/pages/admin/users/create.blade.php`:

```blade
<x-admin.users.layout :mainHeading="__('Create User')">
    <livewire:admin.users.create-user />
</x-admin.users.layout>
```

- [ ] **Step 12.6: Create the form partial**

Create `resources/views/livewire/admin/users/partials/form.blade.php`:

```blade
@props(['mode' => 'create'])

@php
    $passwordLabel = $mode === 'edit' ? __('New Password') : __('Password');
    $passwordHelp = $mode === 'edit' ? __('Leave blank to keep current password.') : null;
@endphp

<div class="space-y-6">
    <div class="grid gap-6 sm:grid-cols-2">
        <flux:field>
            <flux:label>{{ __('First name') }}</flux:label>
            <flux:input wire:model="first_name" />
            <flux:error name="first_name" />
        </flux:field>

        <flux:field>
            <flux:label>{{ __('Last name') }}</flux:label>
            <flux:input wire:model="last_name" />
            <flux:error name="last_name" />
        </flux:field>
    </div>

    <flux:field>
        <flux:label>{{ __('Email') }}</flux:label>
        <flux:input type="email" wire:model="email" />
        <flux:error name="email" />
    </flux:field>

    <flux:field>
        <flux:label>{{ $passwordLabel }}</flux:label>
        <flux:input type="password" wire:model="password" />
        @if ($passwordHelp)
            <flux:description>{{ $passwordHelp }}</flux:description>
        @endif
        <flux:error name="password" />
    </flux:field>

    <flux:field>
        <flux:label>{{ __('Role') }}</flux:label>
        <flux:select wire:model="role">
            @foreach (\App\Enums\Policies\Role::cases() as $case)
                <option value="{{ $case->value }}">{{ $case->label() }}</option>
            @endforeach
        </flux:select>
        <flux:error name="role" />
    </flux:field>
</div>
```

- [ ] **Step 12.7: Create the Livewire view**

Create `resources/views/livewire/admin/users/create-user.blade.php`:

```blade
<div class="w-full max-w-2xl">
    <form wire:submit="store" class="space-y-6">
        @include('livewire.admin.users.partials.form', ['mode' => 'create'])

        <div class="border-t border-neutral-200 pt-6 dark:border-neutral-700">
            <flux:button type="submit" variant="primary">
                {{ __('Create User') }}
            </flux:button>
        </div>
    </form>
</div>
```

- [ ] **Step 12.8: Create the Livewire component**

Create `app/Livewire/Admin/Users/CreateUser.php`:

```php
<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Users;

use App\Actions\Users\CreateUser as CreateUserAction;
use App\Dto\Users\CreateUserInput;
use App\Enums\Policies\Ability;
use App\Enums\Policies\Role;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Component;

final class CreateUser extends Component
{
    public string $first_name = '';

    public string $last_name = '';

    public string $email = '';

    public string $password = '';

    public string $role = '';

    public function mount(): void
    {
        $this->role = Role::MEMBER->value;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', Rule::enum(Role::class)],
        ];
    }

    public function store(CreateUserAction $action): void
    {
        Gate::authorize(Ability::CREATE, User::class);

        $user = $action(CreateUserInput::fromArray($this->validate()));

        session()?->flash('toast', [
            'message' => __('User created successfully'),
            'variant' => 'success',
        ]);

        $this->redirect(route('admin.platform.users.edit', $user), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.admin.users.create-user');
    }
}
```

(Note: `admin.platform.users.edit` is added in Task 13. The redirect URL won't resolve until then, but the test passing `assertRedirect()` is satisfied by *any* redirect. If the test fails at this step due to the missing route, run Task 13 first or temporarily redirect to `admin.platform.users.index`.)

- [ ] **Step 12.9: Update the resource layout to add an "Add user" button**

Replace the body of `resources/views/components/admin/users/layout.blade.php` with:

```blade
@php /** @var \App\Models\User $user */ @endphp

@props([
    'mainHeading' => isset($user) ? $user->name : null,
    'hasTabs' => false,
    'tabHeading' => null,
    'tabSubheading' => null,
    'user' => null,
])

<x-admin.ui.layouts.main :$mainHeading :$tabHeading :$tabSubheading>
    <x-slot:headerButtons>
        @isset($user)
            <flux:button variant="primary" size="sm" icon="pencil"
                :href="route('admin.platform.users.edit', $user)" wire:navigate>
                {{ __('Edit') }}
            </flux:button>
        @else
            @can('create', \App\Models\User::class)
                <flux:button variant="primary" size="sm" icon="plus"
                    :href="route('admin.platform.users.create')" wire:navigate>
                    {{ __('Add User') }}
                </flux:button>
            @endcan
        @endisset
    </x-slot:headerButtons>

    @if($hasTabs)
        <x-slot:tabs>
            <flux:navlist>
                <flux:navlist.item :href="route('admin.platform.users.edit', $user)"
                    icon="pencil" wire:navigate>
                    {{ __('Edit') }}
                </flux:navlist.item>
            </flux:navlist>
        </x-slot:tabs>
    @endif

    {{ $slot }}
</x-admin.ui.layouts.main>
```

(Note: `admin.platform.users.edit` is added in Task 13. The Edit button block is only rendered when `$user` is set, which doesn't happen until edit pages exist — safe to add now.)

- [ ] **Step 12.10: Run the test — expect pass**

```bash
vendor/bin/sail artisan test --filter='Tests\\Feature\\Admin\\Users\\CreateUserTest'
```

Expected: 5 passed. If test 3 fails at the redirect with `RouteNotFoundException` for `admin.platform.users.edit`, proceed to Task 13 first and re-run. Alternatively, temporarily change the redirect target in `store()` to `admin.platform.users.index` and revert it after Task 13.

- [ ] **Step 12.11: Commit**

```bash
vendor/bin/sail bin pint --dirty --format agent
git add app/Livewire/Admin/Users/CreateUser.php \
        resources/views/livewire/admin/users/create-user.blade.php \
        resources/views/livewire/admin/users/partials/form.blade.php \
        resources/views/pages/admin/users/create.blade.php \
        resources/views/components/admin/users/layout.blade.php \
        app/Http/Controllers/Admin/UserController.php \
        routes/admin/platform.php \
        tests/Feature/Admin/Users/CreateUserTest.php
git commit -m "feat(users): add CreateUser Livewire CRUD"
```

---

## Task 13: `EditUser` Livewire component, page, route, list "edit" links

**Files:**
- Create: `app/Livewire/Admin/Users/EditUser.php`
- Create: `resources/views/livewire/admin/users/edit-user.blade.php`
- Create: `resources/views/pages/admin/users/edit.blade.php`
- Modify: `app/Http/Controllers/Admin/UserController.php`
- Modify: `routes/admin/platform.php`
- Modify: `resources/views/livewire/admin/users/user-list.blade.php` (add edit link in name cell)
- Test: `tests/Feature/Admin/Users/EditUserTest.php`

- [ ] **Step 13.1: Write the failing test**

Create `tests/Feature/Admin/Users/EditUserTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Users;

use App\Enums\Policies\Role;
use App\Livewire\Admin\Users\EditUser;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class EditUserTest extends TestCase
{
    #[Test]
    public function admin_can_view_the_edit_page(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->member()->create();

        $this->actingAs($admin)
            ->get(route('admin.platform.users.edit', $target))
            ->assertOk()
            ->assertSeeLivewire(EditUser::class);
    }

    #[Test]
    public function member_cannot_view_the_edit_page(): void
    {
        $member = User::factory()->member()->create();
        $target = User::factory()->create();

        $this->actingAs($member)
            ->get(route('admin.platform.users.edit', $target))
            ->assertForbidden();
    }

    #[Test]
    public function admin_can_update_a_user(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->member()->create([
            'first_name' => 'Old',
            'email' => 'old@example.test',
        ]);
        $this->actingAs($admin);

        Livewire::test(EditUser::class, ['user' => $target])
            ->set('first_name', 'New')
            ->set('email', 'new@example.test')
            ->set('password', 'fresh-pass')
            ->set('role', Role::ADMIN->value)
            ->call('update')
            ->assertHasNoErrors();

        $target->refresh();
        $this->assertSame('New', $target->first_name);
        $this->assertSame('new@example.test', $target->email);
        $this->assertTrue(Hash::check('fresh-pass', $target->password));
        $this->assertSame(Role::ADMIN, $target->appRole);
    }

    #[Test]
    public function blank_password_leaves_existing_hash_unchanged(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->member()->create();
        $originalHash = $target->password;
        $this->actingAs($admin);

        Livewire::test(EditUser::class, ['user' => $target])
            ->set('password', '')
            ->call('update')
            ->assertHasNoErrors();

        $this->assertSame($originalHash, $target->fresh()->password);
    }

    #[Test]
    public function unique_email_rule_ignores_current_user(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->member()->create(['email' => 'self@example.test']);
        $this->actingAs($admin);

        Livewire::test(EditUser::class, ['user' => $target])
            ->set('email', 'self@example.test')
            ->call('update')
            ->assertHasNoErrors('email');
    }
}
```

- [ ] **Step 13.2: Run the test — expect failure**

```bash
vendor/bin/sail artisan test --filter='Tests\\Feature\\Admin\\Users\\EditUserTest'
```

Expected: failure (route + component do not exist).

- [ ] **Step 13.3: Add the route**

In `routes/admin/platform.php`, add the edit route inside the same group:

```php
Route::prefix('users')
    ->name('users.')
    ->controller(UserController::class)
    ->group(function (): void {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::get('/{user}/edit', 'edit')->name('edit');
    });
```

- [ ] **Step 13.4: Add the controller method**

In `app/Http/Controllers/Admin/UserController.php`, add inside the class:

```php
public function edit(User $user): View
{
    Gate::authorize(Ability::UPDATE, $user);

    return view('pages.admin.users.edit', ['user' => $user]);
}
```

- [ ] **Step 13.5: Create the page view**

Create `resources/views/pages/admin/users/edit.blade.php`:

```blade
<x-admin.users.layout
    :mainHeading="$user->name"
    :user="$user"
    :hasTabs="false"
    :tabHeading="__('Edit')"
>
    <livewire:admin.users.edit-user :user="$user" />
</x-admin.users.layout>
```

- [ ] **Step 13.6: Create the Livewire view**

Create `resources/views/livewire/admin/users/edit-user.blade.php`:

```blade
<div class="w-full max-w-2xl">
    <form wire:submit="update" class="space-y-6">
        @include('livewire.admin.users.partials.form', ['mode' => 'edit'])

        <div class="border-t border-neutral-200 pt-6 dark:border-neutral-700">
            <flux:button type="submit" variant="primary">
                {{ __('Save Changes') }}
            </flux:button>
        </div>
    </form>
</div>
```

- [ ] **Step 13.7: Create the Livewire component**

Create `app/Livewire/Admin/Users/EditUser.php`:

```php
<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Users;

use App\Actions\Users\UpdateUser as UpdateUserAction;
use App\Dto\Users\UpdateUserInput;
use App\Enums\Policies\Ability;
use App\Enums\Policies\Role;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Component;

final class EditUser extends Component
{
    public User $user;

    public string $first_name = '';

    public string $last_name = '';

    public string $email = '';

    public string $password = '';

    public string $role = '';

    public function mount(User $user): void
    {
        $this->user = $user;
        $this->first_name = $user->first_name ?? '';
        $this->last_name = $user->last_name ?? '';
        $this->email = $user->email ?? '';
        $this->role = $user->appRole->value;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->user->id)],
            'password' => ['nullable', 'string', 'min:8'],
            'role' => ['required', Rule::enum(Role::class)],
        ];
    }

    public function update(UpdateUserAction $action): void
    {
        Gate::authorize(Ability::UPDATE, $this->user);

        $action($this->user, UpdateUserInput::fromArray($this->validate()));

        session()?->flash('toast', [
            'message' => __('User updated successfully'),
            'variant' => 'success',
        ]);

        $this->redirect(route('admin.platform.users.edit', $this->user), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.admin.users.edit-user');
    }
}
```

- [ ] **Step 13.8: Add edit links from the list**

In `resources/views/livewire/admin/users/user-list.blade.php`, replace the name cell (the one rendering `$user->name()` and the email) with:

```blade
<td class="px-6 py-4">
    <div>
        <a href="{{ route('admin.platform.users.edit', $user) }}" wire:navigate
           class="text-sm font-medium text-neutral-900 hover:underline dark:text-neutral-100">
            {{ $user->name }}
        </a>
        <div class="text-xs text-neutral-500 dark:text-neutral-400">
            {{ $user->email }}
        </div>
    </div>
</td>
```

(Also fixes the existing `$user->name()` call — `name` is an Attribute accessor, so it should be a property, not a method. Drop the `()`.)

- [ ] **Step 13.9: Run the test — expect pass**

```bash
vendor/bin/sail artisan test --filter='Tests\\Feature\\Admin\\Users\\EditUserTest'
```

Expected: 5 passed.

Then re-run Task 12's tests to confirm the redirect from `store()` now resolves:

```bash
vendor/bin/sail artisan test --filter='Tests\\Feature\\Admin\\Users\\CreateUserTest'
```

Expected: 5 passed.

- [ ] **Step 13.10: Commit**

```bash
vendor/bin/sail bin pint --dirty --format agent
git add app/Livewire/Admin/Users/EditUser.php \
        resources/views/livewire/admin/users/edit-user.blade.php \
        resources/views/pages/admin/users/edit.blade.php \
        resources/views/livewire/admin/users/user-list.blade.php \
        app/Http/Controllers/Admin/UserController.php \
        routes/admin/platform.php \
        tests/Feature/Admin/Users/EditUserTest.php
git commit -m "feat(users): add EditUser Livewire CRUD"
```

---

## Task 14: `DeleteUser` Livewire modal + integrate into list

**Files:**
- Create: `app/Livewire/Admin/Users/DeleteUser.php`
- Create: `resources/views/livewire/admin/users/delete-user.blade.php`
- Modify: `resources/views/livewire/admin/users/user-list.blade.php` (add Actions column with delete trigger + modal include)
- Test: `tests/Feature/Admin/Users/DeleteUserTest.php`

- [ ] **Step 14.1: Write the failing test**

Create `tests/Feature/Admin/Users/DeleteUserTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Users;

use App\Livewire\Admin\Users\DeleteUser;
use App\Models\User;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class DeleteUserTest extends TestCase
{
    #[Test]
    public function admin_can_soft_delete_a_user(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->member()->create();
        $this->actingAs($admin);

        Livewire::test(DeleteUser::class, [
            'user' => $target,
            'modalName' => 'delete-user-'.$target->id,
        ])
            ->call('delete')
            ->assertRedirect(route('admin.platform.users.index'));

        $this->assertSoftDeleted('users', ['id' => $target->id]);
    }

    #[Test]
    public function member_cannot_delete_a_user(): void
    {
        $member = User::factory()->member()->create();
        $target = User::factory()->create();
        $this->actingAs($member);

        Livewire::test(DeleteUser::class, [
            'user' => $target,
            'modalName' => 'delete-user-'.$target->id,
        ])
            ->call('delete')
            ->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $target->id, 'deleted_at' => null]);
    }
}
```

- [ ] **Step 14.2: Run the test — expect failure**

```bash
vendor/bin/sail artisan test --filter='Tests\\Feature\\Admin\\Users\\DeleteUserTest'
```

Expected: failure (component does not exist).

- [ ] **Step 14.3: Create the Livewire component**

Create `app/Livewire/Admin/Users/DeleteUser.php`:

```php
<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Users;

use App\Actions\Users\DeleteUser as DeleteUserAction;
use App\Enums\Policies\Ability;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

final class DeleteUser extends Component
{
    public User $user;

    public string $modalName;

    public function mount(User $user, string $modalName): void
    {
        $this->user = $user;
        $this->modalName = $modalName;
    }

    public function delete(DeleteUserAction $action): void
    {
        Gate::authorize(Ability::DELETE, $this->user);

        $action($this->user);

        self::modal($this->modalName)->close();

        session()?->flash('toast', [
            'message' => __('User deleted successfully'),
            'variant' => 'success',
        ]);

        $this->redirect(route('admin.platform.users.index'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.admin.users.delete-user');
    }
}
```

- [ ] **Step 14.4: Create the modal view**

Create `resources/views/livewire/admin/users/delete-user.blade.php`:

```blade
<flux:modal :name="$modalName" class="w-full max-w-md">
    <div class="space-y-6">
        <flux:heading size="lg">{{ __('Delete User') }}</flux:heading>

        <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-900/20">
            <div class="flex gap-3">
                <flux:icon.exclamation-triangle class="h-5 w-5 text-amber-500" />
                <div>
                    <flux:text class="font-medium text-amber-800 dark:text-amber-200">
                        {{ __('Are you sure you want to delete :name? This action cannot be undone.', ['name' => $user->name]) }}
                    </flux:text>
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <flux:modal.close>
                <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
            </flux:modal.close>
            <flux:button wire:click="delete" variant="danger">{{ __('Delete') }}</flux:button>
        </div>
    </div>
</flux:modal>
```

- [ ] **Step 14.5: Add Actions column + modal include to the list**

In `resources/views/livewire/admin/users/user-list.blade.php`:

1. Add a new `<th>` at the end of the `<tr>` inside `<thead>`:

```blade
<th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400">
    {{ __('Actions') }}
</th>
```

2. Add a new `<td>` at the end of each row inside the `@foreach`:

```blade
<td class="px-6 py-4 text-right">
    @can('update', $user)
        <flux:button size="sm" variant="ghost" icon="pencil"
            :href="route('admin.platform.users.edit', $user)" wire:navigate>
            {{ __('Edit') }}
        </flux:button>
    @endcan
    @can('delete', $user)
        <flux:modal.trigger :name="'delete-user-'.$user->id">
            <flux:button size="sm" variant="ghost" icon="trash">
                {{ __('Delete') }}
            </flux:button>
        </flux:modal.trigger>
        <livewire:admin.users.delete-user
            :user="$user"
            :modal-name="'delete-user-'.$user->id"
            wire:key="delete-user-{{ $user->id }}" />
    @endcan
</td>
```

- [ ] **Step 14.6: Run the test — expect pass**

```bash
vendor/bin/sail artisan test --filter='Tests\\Feature\\Admin\\Users\\DeleteUserTest'
```

Expected: 2 passed.

- [ ] **Step 14.7: Commit**

```bash
vendor/bin/sail bin pint --dirty --format agent
git add app/Livewire/Admin/Users/DeleteUser.php \
        resources/views/livewire/admin/users/delete-user.blade.php \
        resources/views/livewire/admin/users/user-list.blade.php \
        tests/Feature/Admin/Users/DeleteUserTest.php
git commit -m "feat(users): add DeleteUser modal and list actions"
```

---

## Task 15: Add Users link to platform navigation

**Files:**
- Modify: `resources/views/components/admin/platform/navigation/menu.blade.php`

- [ ] **Step 15.1: Add the link**

Replace the body of `resources/views/components/admin/platform/navigation/menu.blade.php` with:

```blade
<flux:navlist variant="outline">
    <flux:navlist.group :heading="__('Platform')" class="grid">
        <flux:navlist.item icon="arrow-up-tray"
                           :href="route('admin.platform.dashboard')"
                           :current="request()->is('admin.platform.dashboard')"
                           wire:navigate
        >
            {{ __('Platform Dashboard') }}
        </flux:navlist.item>

        <flux:navlist.item icon="users"
                           :href="route('admin.platform.users.index')"
                           :current="request()->routeIs('admin.platform.users.*')"
                           wire:navigate
        >
            {{ __('Users') }}
        </flux:navlist.item>
    </flux:navlist.group>
</flux:navlist>
```

(The existing `admin.platform.dashboard` link is preserved as-is. If `admin.platform.dashboard` route does not exist and breaks the page render, that is a pre-existing bug outside this PR's scope — flag it to the user but do not fix it here.)

- [ ] **Step 15.2: Smoke-test the page**

```bash
vendor/bin/sail artisan test --filter='Tests\\Feature\\Admin\\Users\\UserListTest'
```

Expected: 4 passed (the nav menu renders inside the layout, so a broken nav would surface as a render failure here).

- [ ] **Step 15.3: Commit**

```bash
vendor/bin/sail bin pint --dirty --format agent
git add resources/views/components/admin/platform/navigation/menu.blade.php
git commit -m "feat(nav): add Users link to platform navigation"
```

---

## Task 16: Update `admin-structure.md` to allow flat namespacing

**Files:**
- Modify: `.claude/rules/admin-structure.md`

- [ ] **Step 16.1: Add a "Flat (shared) resources" subsection**

In `.claude/rules/admin-structure.md`, immediately after the "Two Role Areas" section (after line 22, before the `---` separator), insert:

```markdown
### Flat (shared) resources

Some resources are not bound to a single audience — for example, a `Users`
admin screen managed from the platform area but conceptually a shared
catalog. For these, the resource files live at the top level of each
subtree (no `Member/` or `Platform/` segment), while the route stays inside
the relevant area group:

| Element              | Path                                                   |
|----------------------|--------------------------------------------------------|
| Controller           | `app/Http/Controllers/Admin/{Resource}Controller.php`  |
| Livewire             | `app/Livewire/Admin/{Resources}/`                      |
| Resource layout      | `resources/views/components/admin/{resources}/`        |
| Pages                | `resources/views/pages/admin/{resources}/`             |
| Livewire views       | `resources/views/livewire/admin/{resources}/`          |
| Route                | `routes/admin/platform.php` (resolves under `admin.platform.{resources}.*`) or `routes/admin/member.php` |

The reference resource for this layout is `Users` — see
`app/Livewire/Admin/Users/` and `resources/views/livewire/admin/users/`.

Choose the flat layout when the resource is owned by one area but its
**namespace** has no natural area suffix; use the role-scoped layout
(`Admin/Member/{Resources}` or `Admin/Platform/{Resources}`) when the
resource genuinely differs by audience.
```

- [ ] **Step 16.2: Commit**

```bash
git add .claude/rules/admin-structure.md
git commit -m "docs(admin-structure): allow flat namespacing for shared resources"
```

---

## Final verification

- [ ] **Step F.1: Run the full test suite**

```bash
vendor/bin/sail artisan test
```

Expected: green. If the existing `tests/Unit/Services/Models/Users/UserInteractionServiceTest` and `tests/Unit/Repositories/Models/Users/UserRepositoryTest` are still green, leave them — they are deferred to the cleanup PR.

- [ ] **Step F.2: Pint pass**

```bash
vendor/bin/sail bin pint --dirty --format agent
```

Expected: no remaining changes (all formatting committed inline). If pint reformats anything, commit it:

```bash
git add -u
git commit -m "style: pint pass"
```

- [ ] **Step F.3: Manual smoke check**

Open the dev server (`vendor/bin/sail composer run dev` or `vendor/bin/sail yarn run dev`), log in as an admin, and walk through:
1. `/admin/users` — list renders, search filters, sort toggles, edit/delete buttons appear in rows, "Add User" button appears in header.
2. `/admin/users/create` — form renders, validation rejects empty submit, success creates user and redirects to edit.
3. `/admin/users/{id}/edit` — form prefilled, blank password keeps current, role change persists.
4. List → delete modal → confirm → user disappears (soft-deleted), toast appears.

If any step fails, the failure is a bug in this PR — open it as a follow-up step and fix before merging.

---

## Spec coverage check

| Spec section | Implemented in |
|---|---|
| Architecture & layering | Tasks 4–11 (DTOs, query, scopes, actions) |
| DTO placement (`app/Dto/Users/`) | Tasks 4, 5, 6 |
| `CreateUserInput` shape | Task 5 |
| `UpdateUserInput` shape (null = unchanged password, '' → null) | Task 6 |
| `ListUsersFilters` shape | Task 4 |
| `CreateUser` action (transaction, hash, syncRoles) | Task 9 |
| `UpdateUser` action (conditional rehash, conditional role sync) | Task 10 |
| `DeleteUser` action (soft delete) | Task 11 |
| `scopeSearch`, `scopeWithRole` | Task 3 |
| `ListUsersQuery` | Task 7 |
| `UserList` refactor + view fixes | Task 8 |
| `CreateUser` Livewire (validation, Gate, action call) | Task 12 |
| `EditUser` Livewire (mount, ignore-self unique, optional password) | Task 13 |
| `DeleteUser` Livewire modal | Task 14 |
| Resource layout add/edit buttons | Tasks 12, 13 |
| Routes (`create`, `edit`) | Tasks 12, 13 |
| Controller `create`/`edit` methods + `Gate::authorize` | Tasks 12, 13 |
| Form partial shared by create/edit | Task 12 |
| Nav menu Users link | Task 15 |
| Update `admin-structure.md` for flat shared resources | Task 16 |
| `Ability::CREATE/UPDATE/DELETE` cases exist | Pre-existing — verified during context-gathering |
| `UserPolicy::create/update/delete` methods exist | Pre-existing — verified during context-gathering |
| Pre-existing `viewAny` bug in `UserPolicy` | Task 2 (fix bundled because we depend on it) |
| Pre-existing `roleCache` bug in list view | Task 8 + Task 1 (`badgeColor()`) |
| Cleanup of services/repositories/generators | Deferred to follow-up PR (per spec) |
