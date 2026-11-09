# Laravel Starter

The template repository new Laravel projects are seeded from. It ships a working admin
shell with authentication, role-based authorization and multi-tenancy, plus the reference
implementations of the architecture patterns (Actions / Queries / DTOs, Livewire CRUD with
Form Objects) that projects copy from.

## Stack

- PHP 8.4, Laravel 13, Livewire 4, Flux 2, Tailwind 4, Alpine, Vite 8
- PostgreSQL 17, Redis (cache, sessions, queues), Horizon, Mailpit
- Authorization: `apavliukov/laravel-authorization` on top of `spatie/laravel-permission`
  (teams on, team = `Organization`)
- Tooling: Pint, Rector, PHPStan (Larastan), PHPUnit + Paratest, ESLint, Prettier,
  lefthook, commitlint, Sentry, Telescope, Debugbar, Log Viewer, Mailbook, Laravel Boost

## Requirements

- Docker with Compose — PHP, Composer and the database run in Laravel Sail
- Node and Yarn 4 (pinned in `package.json`, via Corepack) on the host, for installing
  packages and git hooks

## Installation

```bash
cp .env.example .env

# vendor/ does not exist yet, so the first install runs in a throwaway container
docker run --rm -u "$(id -u):$(id -g)" -v "$(pwd):/var/www/html" -w /var/www/html \
    laravelsail/php84-composer:latest composer install --ignore-platform-reqs

vendor/bin/sail up -d
vendor/bin/sail artisan key:generate
vendor/bin/sail artisan migrate --seed
vendor/bin/sail artisan boost:install   # generates the AI guidelines, skills and MCP config

yarn install   # also installs the lefthook git hooks
```

`migrate --seed` syncs roles and permissions only. For local accounts, run the demo seeder
(idempotent):

```bash
vendor/bin/sail artisan db:seed --class="Database\\Seeders\\Demo\\DemoSeeder"
```

| Email | Role | Password |
|-------|------|----------|
| `admin@demo.local` | platform admin | `password` |
| `owner@demo.local` | owner of the demo organization | `password` |
| `member@demo.local` | member of the demo organization | `password` |

## Development

```bash
vendor/bin/sail up -d          # web server, PostgreSQL, Redis, Mailpit
vendor/bin/sail artisan dev    # Horizon, scheduler, Pail and Vite in one TUI
```

`sail up -d` alone starts the web server only: queued jobs and scheduled tasks do not run
until `artisan dev` is up. The process list lives in
`AppServiceProvider::registerDevCommands()`. Vite runs inside the container on
`VITE_PORT` — do not start `yarn dev` on the host as well.

### PhpStorm interpreter

Use the Docker Compose interpreter with the configuration files `./docker-compose.yml`
**and** `./docker-compose.phpstorm.yml`, in that order, and the *Always start a new
container* lifecycle. The second file runs tools as your host uid, so the IDE stops
leaving root-owned files in `storage/`, `vendor/` and the container's `/tmp`.

## Commands

| Command | What it does |
|---------|--------------|
| `vendor/bin/sail composer test` | Test suite (`-- --filter=Foo` to scope it) |
| `vendor/bin/sail composer test:parallel` | Test suite across 6 processes |
| `vendor/bin/sail composer pint -- --dirty` | Code style on changed files |
| `vendor/bin/sail composer rector` | Rector (`-- --dry-run` to preview) |
| `vendor/bin/sail composer analyse` | PHPStan |
| `vendor/bin/sail composer ide-helper` | Regenerate IDE helper files |
| `vendor/bin/sail php vendor/bin/check-translations` | Verify UI strings are translated |
| `yarn build` | Production asset build |
| `yarn release:patch` / `release:minor` / `release:major` | Bump the version, tag, update the changelog |

Tests run against the `testing` database, created by the PostgreSQL container on first
start. The parallel run keeps `testing_test_1..6` around between runs.

Commits follow [Conventional Commits](https://www.conventionalcommits.org) (commitlint in
the `commit-msg` hook); the `pre-commit` hook runs ESLint, Prettier, Rector and Pint on
staged files.

## Structure

| Area | URL | Route names | Access |
|------|-----|-------------|--------|
| Platform | `/platform/*` | `admin.platform.*` | platform admins |
| Member | `/admin/*` | `admin.member.*` | users with an organization (no pages yet) |
| Shared | `/dashboard`, `/settings/*` | `admin.dashboard`, `admin.settings.*` | any verified user |

- **Roles:** `ADMIN` (global, bypasses everything), `OWNER` and `MEMBER` (inside an
  organization).
- **Tenancy:** the tenant is `Organization`. Membership is a role row in
  `model_has_roles`, ownership is `organizations.owner_id`. A project without tenants
  strips the layer once, right after seeding — the steps are in `CLAUDE.md`.
- **Reference resources:** `Users` (`app/Livewire/Admin/Users/`) is the full pattern,
  `Organizations` the same shape reduced to one field. Mirror them for new resources.

Internal tools: `/horizon`, `/telescope`, `/log-viewer` and `/mailbook`.

## Architecture

No service or repository layer. A Livewire component authorizes and validates, hands a
DTO to an Action (writes) or a Query (reads), and renders the result. One request, read
top to bottom:

| Layer | Reference class | Role |
|-------|-----------------|------|
| Component | [`Livewire\Admin\Users\EditUser`](app/Livewire/Admin/Users/EditUser.php) | `Gate::authorize`, validate the form, call the Action — no business logic |
| Form Object | [`Livewire\Admin\Users\Forms\UserForm`](app/Livewire/Admin/Users/Forms/UserForm.php) | Validation rules, shared by create and edit; converts itself into a DTO |
| DTO | [`Dto\Users\UpdateUserInput`](app/Dto/Users/UpdateUserInput.php) | `final readonly`, typed input — the Action never sees a request or an array |
| Action | [`Actions\Users\UpdateUser`](app/Actions/Users/UpdateUser.php) | One invokable class per write, in a transaction, dependencies injected |
| Query | [`Queries\Users\ListUsersQuery`](app/Queries/Users/ListUsersQuery.php) | One class per read: filters DTO in, paginator out, eager loads declared here |

Actions and Queries know nothing about Livewire, so they are unit-tested on their own
(`tests/Unit/{Actions,Queries,Dto}/Users/`) and the components through Livewire
(`tests/Feature/Admin/Users/`). The whole resource lives in
[`app/Livewire/Admin/Users/`](app/Livewire/Admin/Users/) and
[`resources/views/livewire/admin/users/`](resources/views/livewire/admin/users/).

## Further reading

- `CHANGELOG.md` — release history
- `CLAUDE.md` — project manifest: topology, tenancy, authorization, deviations from canon
- `.ai/rules/index.md` — path-scoped conventions, read before editing the files they cover
- `docs/superpowers/specs/` — design documents behind the larger changes
