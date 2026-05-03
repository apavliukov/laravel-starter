# Refactor: Actions + Queries + DTOs (Users as the Template)

**Date:** 2026-05-03
**Status:** Approved, ready for implementation plan

## Goal

Replace the generic `Service → Repository` layer with single-purpose **Action** classes (writes), **Query** classes (reads), **scopes** (reusable predicates), and **DTOs** at the layer boundaries. Refactor the existing `UserList` Livewire component and add simple Create/Edit/Delete components for `User` so the result becomes the canonical pattern for future resources. Removal of the old service/repository (and the test `Generator` layer) is staged into a follow-up PR.

## Why

- `BaseModelInteractionService` / `BaseModelRepository` are generic-CRUD wrappers that grow fat as soon as a real use case needs anything beyond `find / create / update / delete`. Each new query/request requires editing both the service and the repository.
- The supposed benefit (abstracting Eloquent) was never realized — Eloquent leaks through the return types and Active Record conventions.
- `tests/Generators/Models/` mirrors the same anti-pattern over `Illuminate\Database\Eloquent\Factories\Factory`, which is itself the right abstraction.

## Architecture

```
Livewire / Controller
        │  validates input ─→ DTO
        ▼
   Action  |  Query
        │  performs operation / fetches data
        ▼
   Eloquent Model (with scopes)
        │
        ▼
   Database
```

- **Actions** (`app/Actions/Users/`) — one class per write use case. Single `__invoke`. Receive a validated input DTO, perform the operation (including side effects like role sync), return the resulting model.
- **Query objects** (`app/Queries/Users/`) — one class per read use case. `handle(FiltersDTO)` returns an Eloquent paginator/collection. Compose model scopes.
- **Eloquent scopes** on the model — small reusable predicates (e.g. `scopeSearch`, `scopeWithRole`).
- **DTOs** (`app/DTO/Users/`) — hand-rolled `final readonly` classes; **all DTOs live in this directory**, regardless of whether they have one consumer or many. Same convention as Form Requests and Controllers.
- **Livewire components** are the HTTP boundary: validate via their own `rules()`, build the input DTO, invoke the action, then redirect/toast. They contain no business logic and do not call Eloquent for writes.
- **Page controllers** stay thin: `Gate::authorize()` plus `view(...)`. No data loading.

Validation lives in the Livewire component (Livewire is the entry boundary here, not a JSON controller + FormRequest). The action trusts the DTO it receives. Authorization is enforced at the entry boundary in two places: in the page controller (so unauthorized users can't render the page) and in the Livewire action method (so they can't dispatch the write). Actions do not call `Gate::authorize()`.

## Decisions Locked

| # | Decision | Choice |
|---|---|---|
| 1 | DTO library | Hand-rolled `final readonly` classes — no new dependency. Revisit `spatie/laravel-data` after 3–4 resources adopt the pattern. |
| 2 | CRUD scope | Fillable fields (`first_name`, `last_name`, `email`, `password`) **plus** role selection on create and edit. |
| 3 | Component location | Flat `App\Livewire\Admin\Users\` (no `Member/` or `Platform/` segment). The current admin-structure rule will be revised in a follow-up to allow flat namespacing for shared resources. |
| 4 | Route name | `admin.platform.users.*`, URL `/admin/users` (already in `routes/admin/platform.php` behind `EnsurePlatformAdminAccessMiddleware`). |
| 5 | DTO placement | All DTOs in `app/DTO/Users/`, mirroring the Form Request / Controller convention. |
| 6 | List query return type | `LengthAwarePaginator<int, User>` — Eloquent paginator. The list view reads model accessors (`name`, `initials`, `is_admin`); a list-row DTO would cost ergonomics with no boundary win. |

## Directory Layout

```
app/
├── Actions/Users/
│   ├── CreateUser.php           # __invoke(CreateUserInput): User
│   ├── UpdateUser.php           # __invoke(User, UpdateUserInput): User
│   └── DeleteUser.php           # __invoke(User): void
├── Queries/Users/
│   └── ListUsersQuery.php       # handle(ListUsersFilters): LengthAwarePaginator<int, User>
├── DTO/Users/
│   ├── CreateUserInput.php
│   ├── UpdateUserInput.php
│   └── ListUsersFilters.php
├── Models/User.php              # + scopeSearch, scopeWithRole
├── Http/Controllers/Admin/
│   └── UserController.php       # index, create, edit (each Gate::authorize + view)
└── Livewire/Admin/Users/
    ├── UserList.php             # refactored to use ListUsersQuery + ListUsersFilters
    ├── CreateUser.php           # form + validate + Actions\Users\CreateUser
    ├── EditUser.php             # form + validate + Actions\Users\UpdateUser
    └── DeleteUser.php           # modal + Actions\Users\DeleteUser

resources/views/
├── pages/admin/users/
│   ├── index.blade.php          # exists
│   ├── create.blade.php         # NEW
│   └── edit.blade.php           # NEW
├── livewire/admin/users/
│   ├── user-list.blade.php      # exists; minor changes
│   ├── create-user.blade.php    # NEW
│   ├── edit-user.blade.php      # NEW
│   ├── delete-user.blade.php    # NEW (modal)
│   └── partials/form.blade.php  # NEW (shared by create/edit)
└── components/admin/users/
    └── layout.blade.php         # NEW (resource layout, flat — no member/platform segment)

routes/admin/platform.php        # extend existing users group with create + edit
```

## DTOs

All `final readonly`, in `app/DTO/Users/`. Validation rules + messages stay in the Livewire components; DTOs only carry data.

```php
// CreateUserInput.php
final readonly class CreateUserInput
{
    public function __construct(
        public string $firstName,
        public string $lastName,
        public string $email,
        public string $password,
        public RoleEnum $role,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            firstName: $data['first_name'],
            lastName: $data['last_name'],
            email: $data['email'],
            password: $data['password'],
            role: RoleEnum::from($data['role']),
        );
    }
}
```

```php
// UpdateUserInput.php
final readonly class UpdateUserInput
{
    public function __construct(
        public string $firstName,
        public string $lastName,
        public string $email,
        public ?string $password,   // null = leave password unchanged
        public RoleEnum $role,
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
            role: RoleEnum::from($data['role']),
        );
    }
}
```

```php
// ListUsersFilters.php
final readonly class ListUsersFilters
{
    public function __construct(
        public string $search = '',
        public string $sort = 'created_at',   // 'created_at' | 'name' | 'email'
        public string $direction = 'desc',    // 'asc' | 'desc'
        public int $perPage = 15,
    ) {}
}
```

Notes:
- No `UserData` output DTO. Actions return the `User` model — Livewire only needs the id for the redirect, and the list query returns models per decision #6.
- `role` is parsed at the boundary (`RoleEnum::from(...)`) so the action receives a typed value, not a string.
- Empty-string passwords on update are normalized to `null` in `fromArray`.

## Actions

Three classes in `App\Actions\Users\`. All `final readonly`, single `__invoke`.

```php
final readonly class CreateUser
{
    public function __construct(private Hasher $hasher) {}

    public function __invoke(CreateUserInput $input): User
    {
        return DB::transaction(function () use ($input): User {
            $user = User::query()->create([
                'first_name' => $input->firstName,
                'last_name'  => $input->lastName,
                'email'      => $input->email,
                'password'   => $this->hasher->make($input->password),
            ]);

            $user->syncRoles([$input->role->value]);

            return $user;
        });
    }
}
```

```php
final readonly class UpdateUser
{
    public function __construct(private Hasher $hasher) {}

    public function __invoke(User $user, UpdateUserInput $input): User
    {
        return DB::transaction(function () use ($user, $input): User {
            $attributes = [
                'first_name' => $input->firstName,
                'last_name'  => $input->lastName,
                'email'      => $input->email,
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

```php
final readonly class DeleteUser
{
    public function __invoke(User $user): void
    {
        $user->delete();   // soft-delete (User uses SoftDeletes)
    }
}
```

Notes:
- Create/Update wrap in `DB::transaction` because role sync is a second statement; if it fails we don't want a half-saved user.
- `DeleteUser` is trivial but exists to keep the call pattern uniform and provide a place to add audit/event side effects without changing callers.
- Authorization is **not** in the action — Livewire components call `Gate::authorize()` before invoking it.

## Query + Scopes

```php
// in App\Models\User
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

```php
// app/Queries/Users/ListUsersQuery.php
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

Notes:
- Search logic moves from `UserList` into `scopeSearch`. Scopes are predicates (return a builder); queries orchestrate (sort, paginate).
- `scopeWithRole` is included as a forward-looking template for the obvious next filter; query objects compose it.

## Livewire Wiring

Pattern: `validate → build DTO → invoke action via method-injection → toast + redirect`. Actions are method-injected so components stay constructor-free; the fully-qualified `\App\Actions\Users\CreateUser` disambiguates from the Livewire component sharing the short name.

### `UserList` (refactored)

```php
final class UserList extends Component
{
    use WithPagination;

    #[Url] public string $search = '';
    public string $sort = 'created_at';
    public string $direction = 'desc';

    public function updatedSearch(): void { $this->resetPage(); }

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

    /** @return LengthAwarePaginator<int, User> */
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

### `CreateUser` (Livewire)

```php
final class CreateUser extends Component
{
    public string $first_name = '';
    public string $last_name = '';
    public string $email = '';
    public string $password = '';
    public string $role = RoleEnum::MEMBER->value;

    /** @return array<string, array<int, mixed>> */
    protected function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name'  => ['required', 'string', 'max:255'],
            'email'      => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password'   => ['required', 'string', 'min:8'],
            'role'       => ['required', Rule::enum(RoleEnum::class)],
        ];
    }

    public function store(\App\Actions\Users\CreateUser $action): void
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

### `EditUser` (Livewire)

Mirrors `CreateUser` with the following differences:
- `mount(User $user)` hydrates fields from the model.
- Password rule is `nullable|string|min:8`.
- Email `unique` rule ignores `$this->user->id`.
- `update(\App\Actions\Users\UpdateUser $action)` calls `$action($this->user, UpdateUserInput::fromArray($this->validate()))`.
- `Gate::authorize(Ability::UPDATE, $this->user)`.
- Redirects back to the edit page with a success toast.

### `DeleteUser` (Livewire)

Modal pattern from `admin-structure.md`:
- `mount(User $user, string $modalName)`.
- `delete(\App\Actions\Users\DeleteUser $action)` runs the action, closes the modal, toasts, redirects to `admin.platform.users.index`.
- `Gate::authorize(Ability::DELETE, $this->user)`.

All three write components share `livewire/admin/users/partials/form.blade.php` (with conditional password label/help text for create vs edit).

## Routes & Controller

```php
// routes/admin/platform.php
Route::prefix('users')
    ->name('users.')
    ->controller(UserController::class)
    ->group(function (): void {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::get('/{user}/edit', 'edit')->name('edit');
    });
```

No `store` / `update` / `destroy` HTTP routes — Livewire handles writes. No `show` route.

```php
// app/Http/Controllers/Admin/UserController.php
public function create(): View
{
    Gate::authorize(Ability::CREATE, User::class);

    return view('pages.admin.users.create');
}

public function edit(User $user): View
{
    Gate::authorize(Ability::UPDATE, $user);

    return view('pages.admin.users.edit', compact('user'));
}
```

Verify `Ability::CREATE` / `Ability::UPDATE` / `Ability::DELETE` exist in `App\Enums\Policies\Ability`; add the missing cases (and policy methods) as part of implementation.

## Resource Layout

`resources/views/components/admin/users/layout.blade.php` (flat path, no `member/`/`platform/` segment) — header buttons, optional badges/tabs slots — wraps `<x-admin.layouts.main>`. Used by all four user pages (index, create, edit, plus future show).

## Navigation

Add a Users link to the platform admin nav under `resources/views/components/admin/platform/layouts/navigation/`. Exact placement (header vs sidebar) is an implementation detail — match the existing convention for other platform-area links.

## Cleanup — In This PR

- Refactor `UserList` to use `ListUsersQuery` + `ListUsersFilters`.
- Add `CreateUser`, `EditUser`, `DeleteUser` Livewire components, page views, partials, and resource layout.
- Add `CreateUser`, `UpdateUser`, `DeleteUser` actions, `ListUsersQuery`, and three DTOs.
- Add `scopeSearch`, `scopeWithRole` to `User`.
- Add `Ability::CREATE/UPDATE/DELETE` cases and policy methods if missing.
- Add `create` and `edit` actions to `UserController` and routes for them.
- Add nav link.
- Update `.claude/rules/admin-structure.md` to allow flat namespacing for shared resources (so the directory layout we ship matches the documented convention).
- Tests (see Testing).

## Cleanup — Deferred to Follow-up PR

A separate PR keeps this one reviewable; the deletion is mechanical once the new pattern is in place.

- Delete `app/Services/Models/`, `app/Repositories/Models/`, `app/Contracts/Services/Models/`, `app/Contracts/Repositories/Models/`.
- Delete `app/Providers/ModelRepositoryServiceProvider.php` and remove its registration from `bootstrap/providers.php`.
- Delete `tests/Generators/Models/` entirely. Replace generator usage in tests with `User::factory()`. If any reusable setup logic surfaces during the migration, move it onto the factory as a state (`->admin()`, `->unverified()`, etc.) or onto `tests/TestCase.php` as a helper (e.g. `actingAsAdmin()`).
- Delete `tests/Unit/Services/Models/Users/UserInteractionServiceTest.php`, `tests/Unit/Repositories/Models/Users/UserRepositoryTest.php`, `tests/Unit/Repositories/Models/ModelRepositoryTestCase.php`.

## Testing

All PHPUnit, all extend `tests/TestCase.php` (which already provides `RefreshDatabase`).

**Feature tests (Livewire)**
- `UserList` — search filters list, sort toggles direction, `clearFilters` resets, pagination resets on filter change.
- `CreateUser` — happy path persists user with role + hashed password; validation failures (required, email format, unique email, password min); unauthorized actor blocked.
- `EditUser` — happy path updates fields and role; blank password leaves hash unchanged; unique-email rule ignores own id; role change calls `syncRoles`; unauthorized actor blocked.
- `DeleteUser` — modal close + soft-delete assertion; unauthorized actor blocked.

**Unit tests**
- `ListUsersQuery` — search across first/last/email, sort by `name`/`email`/`created_at`, pagination size.
- `CreateUser` action — creates user, hashes password, assigns role, transactional rollback on role-sync failure.
- `UpdateUser` action — updates fields, conditional password rehash, role re-sync only on change.
- `DeleteUser` action — soft-deletes the user.

**Authorization tests** — covered inside the Livewire feature tests via `actingAs(...)` with non-admin users.

## Out of Scope

- `spatie/laravel-data` — revisit after 3–4 resources adopt the hand-rolled pattern.
- Domain events (`UserCreated`, `UserDeleted`) — wire-in points exist inside the action transactions; add when there is a consumer.
- A `show` page / dashboard widget for users.
- API resources / JSON endpoints for users.
- Splitting `Admin/Users` into `Admin/Platform/Users` — the deliberate choice here is flat for shared resources, captured in the `admin-structure.md` revision.

## Risks & Mitigations

| Risk | Mitigation |
|---|---|
| Role sync edge cases (multiple roles, unknown role string) | Parse role at the boundary via `RoleEnum::from()` so an unknown value throws before the action runs. `syncRoles([$role->value])` enforces single-role; if multi-role is ever needed, change DTO + action together. |
| Email-unique race between validation and insert | Acceptable for an admin tool; DB unique constraint is the safety net. Catching `QueryException` and re-flagging the field is a follow-up if it becomes a real problem. |
| `appRole` accessor throws when user has no role | Pre-existing behavior. Existing users always have a role assigned at creation; the new `CreateUser` action preserves this invariant. |
| `Ability` enum may lack `CREATE/UPDATE/DELETE` cases | Implementation step verifies and adds them; policy methods added in lockstep. |
