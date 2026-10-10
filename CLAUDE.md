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
| **Platform** | `App\...\Admin\Platform\` | `admin/platform/` | `admin.platform.*` | `/platform/*` | `EnsurePlatformArea` (package) |
| **Member** | `App\...\Admin\Member\` | `admin/member/` | `admin.member.*` | `/admin/*` | `EnsureTenantSelected` (package) |

Platform is the cross-app super-admin area; Member is the authenticated user's portal.
Routes live in `routes/admin/{platform,member}.php`, both wired from `routes/admin.php`
under `['auth', 'verified']`. Route names carry `admin.{area}.`, URLs do not: the platform
area is `/platform/*`, the tenant (member) area `/admin/*`, and the area-less pages sit at
`/dashboard` and `/settings/*` — the same layout as oohire (`/platform`, `/admin`,
`/candidate`). The platform group runs under the package's `EnsurePlatformArea` (deny-all
`accessPlatformAdmin` gate + request-wide tenant bypass); the member group under
`EnsureTenantSelected` (403 without an organization). `routes/admin/member.php` is still
empty. See `.ai/rules/admin.md`.

No area-specific Livewire namespace or view subtree exists yet: all current resources are
the canon's flat (shared) form — `App\Livewire\Admin\{Resources}\` with the route in the
owning area's group (`Users` and `Organizations`, both under `admin.platform.*`). The `{Area}` segment
appears the first time a resource genuinely differs by audience.

Where the areas *are* materialized today: `components/admin/{platform,member}/navigation/`
(menus) and `components/admin/dashboard/{platform,member}.blade.php` (dashboard bodies).

## Tenancy: package layer, membership in `model_has_roles` (reference implementation)

The canon tenancy layer from `apavliukov/laravel-authorization` ^1.2 — mechanics in the
`laravel-authorization-development` skill, design history in the package's
`docs/TENANCY-DESIGN.md` (§7 = this shape). App-side binding:

- Tenant = `App\Models\Organization`; it is also the Spatie permissions team
  (`config/permission.php` teams on). Configured in `config/authorization.php`: model,
  `foreign_key` `organization_id`, `session_key`, `strict` outside production, areas
  `admin.platform.*` / `admin.member.*`.
- **Membership = a role row in `model_has_roles` with `team_id`, nothing else.** There is
  no `users.organization_id` and no pivot. `Organization::members()` and
  `User::tenants()` are the package relations over that table; writes go through
  `TenantMembership` (wrapped by `AddOrganizationMember` / `RemoveOrganizationMember`).
  A user may belong to several organizations; the package's single resolver activates
  the session choice (membership-validated), else the user's sole organization, else
  none (→ 403 on `admin.member.*`; a switcher behind `missing_tenant_redirect` is the
  app's job the day the member area exists). A platform admin may also hold memberships.
- **UI split:** the user form edits identity plus the global ADMIN role
  (`is_platform_admin`); memberships and their roles are managed on the organization
  (`OrganizationMemberList` on the edit page). `UpdateUser` never touches memberships.
- `SetCurrentTenant` is appended to `web` in `bootstrap/app.php`. **No tenant-owned model
  exists yet** — `tests/Feature/Tenancy/FailClosedScopeTest` is the reference for the
  fail-closed scope on an in-test model; the first domain model a project adds gets
  `BelongsToTenant`, and from then on every sessionless path (jobs, seeders, commands)
  must run inside `Tenancy::withoutTenant()` / `forTenant()` or it fails closed.
- `admin.dashboard` and `admin.settings.*` sit outside both areas: no bypass, tenant from
  the resolver. Fine while they touch no tenant-owned model; move them into an area the
  day they do.
- An organization with members cannot be deleted (`DeleteOrganization` throws
  `DomainException`; the component surfaces it as an error toast).

### Stripping tenancy (projects without tenants)

Retrofitting tenancy is expensive, removing it is not — so the template ships with it.
For a single-tenant project, do this once, right after seeding:

1. `config/authorization.php`: `tenancy.model => null` — switches the whole package layer
   off (scope, resolver, middleware registration). Keep the file for `areas`. The
   `accessPlatformAdmin` deny-all gate stays registered by the package regardless.
2. `config/permission.php`: `teams => false`, `models.team => null`.
3. `bootstrap/app.php`: drop `SetCurrentTenant` from `web`.
4. `routes/admin.php`: member group → no middleware (platform keeps `EnsurePlatformArea`,
   which is gate-only without tenancy).
5. Delete `Organization` end to end: model, policy, factory, the `2026_08_22_204247`
   migration, `app/{Actions,Dto,Queries}/Organizations/`,
   `app/Livewire/Admin/Organizations/`, `OrganizationController`, the three view trees
   (`pages/admin/organizations`, `livewire/admin/organizations`,
   `components/admin/organizations`), the platform menu item, the `organizations.*` routes,
   `tests/**/Organization*`, `tests/Feature/Tenancy/`.
6. `User`: drop `HasTenantMemberships`; `UserFactory::member()` loses its argument and
   assigns `MEMBER` globally; drop `Organization::class` from `authorizableModels()`.
7. `DemoUserSeeder`: drop the organization and the `AddOrganizationMember` call.

Everything else (package, `AbstractPolicy`, role enum, `HasAppRole`, seeders, tests) is
tenancy-agnostic.

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
  `MEMBER` (non-super, assigned **inside an organization's team**, one per organization).
  Writers: `TenantMembership::assignGlobal` (in `CreateUser`/`UpdateUser`) for ADMIN,
  `AddOrganizationMember` / `RemoveOrganizationMember` for memberships. Identity via the
  package `HasAppRole` trait: `$user->app_role` (highest role held anywhere, **`null` for
  a user with no role** — `Role::layout()` callers fall back to `Role::default()`) and
  `$user->is_platform_admin`. Never `hasRole()`, which only sees the active team.
  Presentation helpers (`layout()`, `badgeColor()`) via `App\Support\Roles\HasRolePresentation`.
- **`MEMBER` returns `[]` from `permissions()` — an active deny-all.** The first grant made
  outside the enum disappears on the next sync; declare `MEMBER` grants in the enum only.
- `SystemAbility::ACCESS_PLATFORM_ADMIN` (`accessPlatformAdmin`) — the deny-all gate is
  registered by the package unconditionally; the app enum only names it (Log Viewer
  auth). Not via `systemAbilities()`.
- Authorizable models: `Organization`, `User`. Teams: **on** (team = `Organization`).
  Tenancy declarations: all in `config/authorization.php`, none in the provider.
- `$user->is_platform_admin` / `app_role` are routing/UX helpers — never authorization.
- Seeding: `ConsistencySeeder` (deploy) and `DatabaseSeeder` both call the package's
  `AuthorizationSeeder`. `DemoUserSeeder` creates users through `CreateUser` and
  memberships through `AddOrganizationMember`. `UserFactory::member($organization = null)`
  creates an organization when none is given; `admin()` stays global. Policy test base: `tests/Unit/Policies/BasePolicyTestCase.php`
  (resolves policies from the container).

## Skills

All Boost skills live in `.claude/skills/`, **tracked in git** (as in oohire `58d75a6e`:
same reasoning as any generated file, and a fresh clone works without `boost:update`),
installed by `boost:update` and version-matched to this project (Laravel 13, Livewire 4, Flux 2, Tailwind 4,
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
