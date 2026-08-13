# Laravel Starter — Project Manifest

The template repository new Laravel projects are seeded from; carries the reference implementations of the canon patterns.

**Architecture patterns live in the global `laravel-app-patterns` skill — invoke it before
building or refactoring any resource, Livewire component, Action, Query, or layout.** This
file only binds those patterns to this project.

## Topology: T2 (role-split admin)

| Area | Namespace | View path | Route prefix | URL | Middleware |
|------|-----------|-----------|--------------|-----|-----------|
| **Platform** | `App\...\Admin\Platform\` | `admin/platform/` | `admin.platform.*` | `/admin/platform/*` | `EnsureUserIsPlatformAdmin` |
| **Member** | `App\...\Admin\Member\` | `admin/member/` | `admin.member.*` | `/admin/member/*` | `EnsureUserIsMember` |

Platform is the cross-app super-admin area; Member is the authenticated user's portal.
Routes live in `routes/admin/{platform,member}.php`. The base layout `x-layouts.admin`
auto-routes to the area layout by the authenticated user's role.

Flat (shared) resources (no area segment in the namespace) are used for resources owned by
one area without a natural audience split — reference: `Users`
(`app/Livewire/Admin/Users/`, route under `admin.platform.*`).

## Tenancy: none

## Reference resource

**Users** — mirror `app/Livewire/Admin/Users/` and `resources/views/livewire/admin/users/`.
Read it before mirroring a pattern: it carries conventions that evolved after the docs.

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

- Roles: `ADMIN` (super — bypasses everything), `MEMBER` (non-super). Presentation helpers
  (`layout()`, `badgeColor()`) via `App\Support\Roles\HasRolePresentation`.
- **`MEMBER` returns `[]` from `permissions()` — an active deny-all.** The first grant made
  outside the enum disappears on the next sync; declare `MEMBER` grants in the enum only.
- `SystemAbility::ACCESS_PLATFORM_ADMIN` — defined directly as a deny-all
  `Gate::define(...)` in `AuthorizationServiceProvider` (not via `systemAbilities()`).
- Authorizable models: `User`. Teams: off. Tenancy declarations: none.
- `$user->is_admin` / `is_member` are routing/UX helpers — never authorization.
- Seeding: `ConsistencySeeder` (deploy) and `DatabaseSeeder` both call the package's
  `AuthorizationSeeder`. Policy test base: `tests/Unit/Policies/BasePolicyTestCase.php`
  (resolves policies from the container).

## Skills

Relevant global Boost skills: `laravel-best-practices`, `configuring-horizon`,
`fluxui-development`, `tailwindcss-development`, `debug-using-debugbar`,
`laravel-permission-development`.

Package-shipped into git-ignored `.claude/skills/` by `boost:update`:
`laravel-authorization-development`, `laravel-finalize` (translation checker:
`vendor/bin/sail php vendor/bin/check-translations`; Stop hook in
`.claude/settings.json` runs the packaged backstop).

## Deviations from canon

- (none)
