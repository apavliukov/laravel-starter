# Laravel Starter — Project Manifest

The template repository new Laravel projects are seeded from; carries the reference implementations of the canon patterns.

**Architecture patterns live in the global `laravel-app-patterns` skill — invoke it before
building or refactoring any resource, Livewire component, Action, Query, or layout.** This
file only binds those patterns to this project.

Path-scoped rules live in committed `.ai/rules` — read `.ai/rules/index.md` and every rule
file whose globs cover what you are about to edit. Record new ones with `record-rule`,
never by hand.

## Topology: T2 (role-split admin)

| Area | Namespace | View path | Route prefix | URL | Middleware |
|------|-----------|-----------|--------------|-----|-----------|
| **Platform** | `App\...\Admin\Platform\` | `admin/platform/` | `admin.platform.*` | `/admin/*` | `EnsurePlatformArea` (package) |
| **Member** | `App\...\Admin\Member\` | `admin/member/` | `admin.member.*` | `/admin/*` | `EnsureTenantSelected` (package) |

Platform is the cross-app super-admin area; Member is the authenticated user's portal.
Routes live in `routes/admin/{platform,member}.php`, both wired from `routes/admin.php`
under `['auth', 'verified']` and the `/admin` prefix.

**The area split is route names only — neither group adds a URL segment.** `platform.php`
serves `/admin/users`, not `/admin/platform/users`. The platform group runs under the
package's `EnsurePlatformArea` (deny-all `accessPlatformAdmin` gate + request-wide tenant
bypass); the member group under `EnsureTenantSelected` (403 without an organization).
`routes/admin/member.php` is still empty. See `.ai/rules/admin.md`.

No area-specific Livewire namespace or view subtree exists yet: all current resources are
the canon's flat (shared) form — `App\Livewire\Admin\{Resources}\` with the route in the
owning area's group (`Users` and `Organizations`, both under `admin.platform.*`). The `{Area}` segment
appears the first time a resource genuinely differs by audience.

Where the areas *are* materialized today: `components/admin/{platform,member}/navigation/`
(menus) and `components/admin/dashboard/{platform,member}.blade.php` (dashboard bodies).

## Tenancy: global-scope traits, `FromUser` mode (reference implementation)

The canon tenancy layer from `apavliukov/laravel-authorization` ^1.1 — mechanics in the
`laravel-authorization-development` skill, design history in the package's
`docs/TENANCY-DESIGN.md`. App-side binding:

- Tenant = `App\Models\Organization`; it is also the Spatie permissions team
  (`config/permission.php` teams on). Configured in `config/authorization.php`: resolver
  `user` (one organization per user, `users.organization_id`, nullable — admins may have
  none), column `organization_id`, `strict` outside production, areas
  `admin.platform.*` / `admin.member.*`.
- `SetCurrentTenant` is appended to `web` in `bootstrap/app.php`. **No tenant-owned model
  exists yet** — the first domain model a project adds gets `BelongsToTenant`, and from
  then on every sessionless path (jobs, seeders, commands) must run inside
  `Tenancy::withoutTenant()` / `forTenant()` or it fails closed.
- `admin.dashboard` and `admin.settings.*` sit outside both areas: no bypass, tenant from
  the user. Fine while they touch no tenant-owned model; move them into an area the day
  they do.

### Stripping tenancy (projects without tenants)

Retrofitting tenancy is expensive, removing it is not — so the template ships with it.
For a single-tenant project, do this once, right after seeding:

1. `config/authorization.php`: `tenancy.model => null` — switches the whole package layer
   off (scope, gate, middleware registration). Keep the file for `areas`.
2. `config/permission.php`: `teams => false`, `models.team => null`.
3. `bootstrap/app.php`: drop `SetCurrentTenant` from `web`.
4. `routes/admin.php`: platform group → your own deny-all-gate middleware
   (`Gate::define(SystemAbility::ACCESS_PLATFORM_ADMIN, fn () => false)` back in
   `AuthorizationServiceProvider`, see `.ai/rules/providers.md`); member group → no
   middleware.
5. Delete `Organization` end to end: model, policy, factory, both `2026_08_22_*`
   migrations, `app/{Actions,Dto,Queries}/Organizations/`,
   `app/Livewire/Admin/Organizations/`, `OrganizationController`, the three view trees
   (`pages/admin/organizations`, `livewire/admin/organizations`,
   `components/admin/organizations`), the platform menu item, the `organizations.*` routes,
   `tests/**/Organizations*`, `tests/Feature/Tenancy/`.
6. `Users`: remove `organization_id` from `User`, both DTOs, `UserForm` (+ rule and
   select), `ListUsersQuery::with('organization')`, the list column; drop
   `Organization::class` from `authorizableModels()`.
7. `SyncsUserRole` and `UserFactory::withRole()`: collapse to a plain `syncRoles([$role])`
   / `assignRole($role)` — with teams off every role is global. `User::appRole` and the
   `is_*` accessors can stay on the package helpers (they work without teams).
8. `DemoUserSeeder`: drop the organization; `UserFactory::member()` loses its argument.

Everything else (package, `AbstractPolicy`, role enum, seeders, tests) is tenancy-agnostic.

## Reference resource

**Users** — mirror `app/Livewire/Admin/Users/` and `resources/views/livewire/admin/users/`.
Read it before mirroring a pattern: it carries conventions that evolved after the docs.
**Organizations** is the same shape reduced to one field — the smaller copy to start from.

## Local dev processes

`sail up -d` starts the web server only — supervisord in the container is stock Sail
(`[program:php]` alone). Horizon, the scheduler, Pail and Vite run under
`vendor/bin/sail artisan dev`, registered in `AppServiceProvider::registerDevCommands()`
(`server` and the default `queue` process are excluded there). A queued job that never
runs after a bare `sail up -d` is this, not a bug — do not re-add `[program:horizon]` or
`[program:scheduler]` to `docker/8.4/supervisord.conf`.

Vite runs **inside** the container on `VITE_PORT` with `strictPort` — do not also start
`yarn dev` on the host.

## Test suite

- Full suite: `vendor/bin/sail composer test` (`artisan test --compact`)
- Parallel: `vendor/bin/sail composer test:parallel` (6 processes; keeps the
  `testing_test_1..6` databases around between runs — they are not junk)
- Base `TestCase` extras: `Http::preventStrayRequests()` in `setUp()`; no seeding helpers

## Authorization (app specifics)

Package mechanics live in the `laravel-authorization-development` skill shipped by
`apavliukov/laravel-authorization`. App-side facts:

- Roles: `ADMIN` (super — a **global** assignment, `team_id NULL`, bypasses everything),
  `MEMBER` (non-super, assigned **inside the organization's team**). One role per user;
  `App\Support\Users\SyncsUserRole` is the only writer (clears every team, then assigns).
  `User::appRole` / `is_admin` / `is_member` read team-agnostically through the package —
  never `hasRole()`, which only sees the active team. Presentation helpers (`layout()`,
  `badgeColor()`) via `App\Support\Roles\HasRolePresentation`.
- **`MEMBER` returns `[]` from `permissions()` — an active deny-all.** The first grant made
  outside the enum disappears on the next sync; declare `MEMBER` grants in the enum only.
- `SystemAbility::ACCESS_PLATFORM_ADMIN` (`accessPlatformAdmin`) — the deny-all gate is
  registered by the package once tenancy is configured; the app enum only names it (Log
  Viewer auth). Not via `systemAbilities()`.
- Authorizable models: `Organization`, `User`. Teams: **on** (team = `Organization`).
  Tenancy declarations: all in `config/authorization.php`, none in the provider.
- `$user->is_admin` / `is_member` are routing/UX helpers — never authorization.
- Seeding: `ConsistencySeeder` (deploy) and `DatabaseSeeder` both call the package's
  `AuthorizationSeeder`. `DemoUserSeeder` creates users through the `CreateUser` Action so
  roles land in the right team. `UserFactory::member($organization = null)` creates an
  organization when none is given; `admin()` stays global. Policy test base: `tests/Unit/Policies/BasePolicyTestCase.php`
  (resolves policies from the container).

## Skills

All Boost skills live in git-ignored `.claude/skills/`, installed by `boost:update` and
version-matched to this project (Laravel 13, Livewire 4, Flux 2, Tailwind 4,
laravel-permission 8). Never hand-edit them; overrides go in `.ai/skills/{name}/SKILL.md`.

`laravel-finalize` ships the translation checker
(`vendor/bin/sail php vendor/bin/check-translations`); the Stop hook in
`.claude/settings.json` runs the packaged backstop.

Hand-written cross-project skills stay global: `laravel-app-patterns` above all.

## Deviations from canon

- **No auto-routing area layout.** Canon T2 has `x-layouts.admin` pick the area layout
  from the authenticated user's role. Here `x-layouts.admin` is a single flat shell and
  the role branch sits one level down: `Role::layout()` feeds `x-dynamic-component` in
  `admin.ui.navigation.sidebar` (which menu) and `pages/admin/dashboard/index` (which
  dashboard body). Keep this shape until an area needs genuinely different chrome.
- **No URL prefix per area** — see Topology above. Deliberate: the areas differ by
  audience, not by address.
