# Package Extraction Handoff — `apavliukov/laravel-authorization`

**Audience:** a fresh Claude Code session starting work in the NEW (empty) package
repository. You will NOT have the conversation that produced this — this brief is
self-contained. Read it fully before doing anything.

**Goal:** extract the authorization layer that already lives, decoupled and
tested, in the reference app (`laravel-starter`, under `app/Authorization/`) into
a reusable internal Composer package.

- **Composer name:** `apavliukov/laravel-authorization`
- **PHP namespace:** `AlexPavliukov\Authorization\` → `src/`
- **Distribution:** a normal Composer package, consumed via **private VCS**
  (git repo, `dev-main` before 1.0, tags after). It must work anywhere through
  Composer (Docker/Sail and bare prod alike). A local **path-repository symlink**
  is an OPTIONAL personal dev aid only — see §8; never commit it.

---

## 0. First actions in the empty package folder

You start in an EMPTY directory (the new package repo). Do this before anything
else:

**0a. Get the reference checkout path.** Ask the human for the absolute path to
their `laravel-starter` checkout (call it `$STARTER`). You need it to copy source
material. If they can't give a path and haven't copied files in, STOP and ask.

**0b. Initialise the repo + skeleton.**
```bash
git init
mkdir -p src tests reference
printf 'reference/\nvendor/\ncomposer.lock\n.phpunit.result.cache\n.phpunit.cache\n' > .gitignore
```
(`reference/` is gitignored — it's read-only source material, never shipped.)

**0c. Copy the reference material into `reference/`** (so you can read it without
leaving the repo; it will NOT be part of the package):
```bash
cp -R "$STARTER/app/Authorization"                              reference/Authorization
cp    "$STARTER/app/Enums/Policies/Role.php"                    reference/Role.php
cp    "$STARTER/app/Support/Roles/HasRolePresentation.php"      reference/HasRolePresentation.php
cp    "$STARTER/app/Traits/Enums/HasValues.php"                 reference/HasValues.php
cp    "$STARTER/app/Providers/AuthorizationServiceProvider.php" reference/AppAuthorizationServiceProvider.php
cp -R "$STARTER/tests/Unit/Authorization"                      reference/tests-Unit-Authorization
cp -R "$STARTER/tests/Feature/Authorization"                   reference/tests-Feature-Authorization
cp    "$STARTER/tests/Unit/Enums/Policies/RoleTest.php"         reference/RoleTest.php
cp    "$STARTER/tests/Unit/Policies/BasePolicyTest.php"         reference/BasePolicyTest.php
cp    "$STARTER/docs/superpowers/specs/2026-06-27-authorization-decoupling-design.md" reference/design-spec.md
cp    "$STARTER/docs/superpowers/specs/2026-06-28-package-extraction-handoff.md"       reference/handoff.md
cp    "$STARTER/meg-authorization.md" "$STARTER/oohire-authorization.md"               reference/
```

**0d. Read first, in this order:** `reference/design-spec.md` (esp. **§7** and
**§11** — authoritative), then this handoff (`reference/handoff.md`), then the
code under `reference/Authorization/`. The `meg`/`oohire` docs explain why the
bypass is pluggable.

> `reference/Authorization/` is your **source material** — you move it into `src/`
> and rename namespaces (§1). It is NOT the package; the package is what you build
> in `src/`.

---

## 1. Namespace map (apply during the move)

| Reference app (`App\…`) | Package (`AlexPavliukov\Authorization\…`) |
|---|---|
| `App\Authorization\AbstractPolicy` | `…\AbstractPolicy` |
| `App\Authorization\AuthorizationManager` | `…\AuthorizationManager` |
| `App\Authorization\Authorization` (facade) | `…\Authorization` |
| `App\Authorization\Contracts\AuthorizationRole` | `…\Contracts\AuthorizationRole` |
| `App\Authorization\Contracts\TeamResolver` | `…\Contracts\TeamResolver` |
| `App\Authorization\Concerns\HasPolicy` | `…\Concerns\HasPolicy` |
| `App\Authorization\Enums\Ability` | `…\Enums\Ability` |
| `App\Authorization\Enums\SystemAbility` | `…\Enums\SystemAbility` |
| `App\Authorization\PermissionRegistry` | `…\PermissionRegistry` |
| `App\Authorization\Support\BypassStrategy` | **split — see §3.1** |
| `App\Authorization\Teams\DefaultTeamResolver` | `…\Teams\DefaultTeamResolver` |
| `App\Authorization\Teams\SetPermissionsTeam` | `…\Teams\SetPermissionsTeam` |
| `App\Authorization\Database\PermissionSync` | `…\Database\PermissionSync` |
| `App\Authorization\Console\MakePolicyCommand` | `…\Console\MakePolicyCommand` |

**Stays in the consumer app (do NOT move into the package):** `App\Enums\Policies\Role`
(the role enum — app-specific; it *implements* the package's `AuthorizationRole`),
`App\Support\Roles\HasRolePresentation`, concrete policies (`App\Policies\*`),
`App\Models\User`, custom ability enums (e.g. `UserAbility`), the seeders, and the
published `App\Providers\AuthorizationServiceProvider` (the package only ships a
stub of it — see §3.4).

---

## 2. Target structure

```
src/
├── AbstractPolicy.php
├── Authorization.php                 (facade → AuthorizationManager)
├── AuthorizationManager.php
├── AuthorizationServiceProvider.php   (CORE provider — bindings, gate, publishes)
├── Concerns/
│   └── HasPolicy.php
├── Console/
│   ├── MakePolicyCommand.php
│   └── stubs/policy.stub
├── Contracts/
│   ├── AuthorizationRole.php
│   ├── BypassStrategy.php             (NEW — §3.1)
│   └── TeamResolver.php
├── Database/
│   ├── PermissionSync.php
│   └── AuthorizationSeeder.php        (NEW — §3.3)
├── Enums/
│   ├── Ability.php
│   └── SystemAbility.php
├── Support/
│   ├── BypassGate.php                 (NEW — registers Gate::before)
│   ├── HasValues.php                  (relocated — §3.2)
│   ├── RoleBypass.php                 (NEW — §3.1)
│   └── NoBypass.php                   (NEW — §3.1)
└── stubs/
    └── AuthorizationServiceProvider.stub   (published into consumer app — §3.4)
tests/                                  (Testbench — §4)
composer.json
phpunit.xml
pint.json (or use default)
phpstan.neon
```

---

## 3. Transformations to apply (from spec §11)

### 3.1 Pluggable bypass (replaces the static `Support\BypassStrategy`)

The reference app ships a single static `Support\BypassStrategy::register($manager)`.
In the package this becomes a contract + strategies + a registrar.

- **`Contracts\BypassStrategy`**
  ```php
  interface BypassStrategy
  {
      public function shouldBypass(Authenticatable $user, string $ability): bool;
  }
  ```
- **`Support\BypassGate`** — wraps the chosen strategy into the single `Gate::before`:
  ```php
  Gate::before(static fn (Authenticatable $user, string $ability): ?bool =>
      $strategy->shouldBypass($user, $ability) ? true : null);
  ```
  (Return `true → grant`, `false → null/fall-through`. Never deny-all.)
- **`Support\RoleBypass` (default)** — port the reference logic; add an optional
  `array<int, BackedEnum> $protected` of abilities always routed to policies
  (matched by `enum->value` vs the incoming `$ability` string — by-ability, not
  by ability+model). Call `$user->hasAnyRole(...)` directly (the package requires
  the user to use Spatie `HasRoles`; no `method_exists` guard). Signature:
  `__construct(AuthorizationManager $manager, array $protected = [])`.
- **`Support\NoBypass`** — `shouldBypass()` returns `false` always (no god-mode;
  every check goes through Spatie/policies).
- Wire the default in the core provider: bind `BypassStrategy` → `RoleBypass`, then
  `BypassGate::register(app(BypassStrategy::class))`. Let the consumer override via
  `Authorization::bypassUsing(...)` (manager stores the chosen strategy; or rebind
  the container — pick one and document it).

Guiding principle to honor and document (spec §11.2): **a super-admin has the
right to do everything; real "can't"s are business invariants enforced in the
Action/domain layer, not authorization.** `protected`/`NoBypass` exist only for
genuine authorization-level carve-outs (separation-of-duties, break-glass).

### 3.2 Relocate `HasValues`

The reference app's enums no longer depend on it, but if any package enum needs
`values()`, ship `AlexPavliukov\Authorization\Support\HasValues` (copy of the
trivial trait) instead of depending on the app's `App\Traits\Enums\HasValues`.
The package must have **zero `App\` references**.

### 3.3 `AbstractPolicy` → `Authenticatable`

Replace `App\Models\User` with `Illuminate\Contracts\Auth\Authenticatable` in
`AbstractPolicy` (method signatures and the `userCan()` helper). This is the one
documented app-coupling in the reference app; it must be removed here.

### 3.4 Seeder + publishable provider

- Ship **`Database\AuthorizationSeeder`** — a generic seeder whose `run()` calls
  `PermissionSync::permissions()` then `::roles()`. Consumers call it from their
  own `ConsistencySeeder`/`DatabaseSeeder` via `$this->call([AuthorizationSeeder::class])`.
  (The reference app's `PermissionSeeder`/`RoleSeeder`/`RoleAndPermissionSeeder`
  are NOT moved — they disappear from the consumer in favor of this.)
- The **core `AuthorizationServiceProvider`** (in `src/`):
  - `register()`: singleton `AuthorizationManager`; bind `TeamResolver` →
    `DefaultTeamResolver`; bind `BypassStrategy` → `RoleBypass`.
  - `boot()`: `BypassGate::register(...)`; register `SetPermissionsTeam` on the
    `web` group ONLY when `config('permission.teams') === true`; register
    `MakePolicyCommand` under `runningInConsole()`; `publishes()` the provider stub.
  - Does NOT call `Authorization::useRoleEnum()` / `authorizableModels()` — those
    are app declarations and live in the published stub.
- Ship **`stubs/AuthorizationServiceProvider.stub`** → published to
  `app/Providers/AuthorizationServiceProvider.php`. It contains the app
  declarations (`useRoleEnum(Role::class)`, `authorizableModels([...])`,
  `Gate::define(SystemAbility::ACCESS_PLATFORM_ADMIN, …)`, optional
  `bypassUsing(...)`). Use `App\` namespaces in the stub (Laravel convention).

### 3.5 Generator stub namespaces

`Console\stubs/policy.stub` keeps generating into `App\Policies` + `App\Models`
(Laravel convention — fine for virtually all consumers). Making it configurable is
out of scope unless a consumer needs it.

---

## 4. Package testing — Orchestra Testbench

The package has no host app, so tests run on **orchestra/testbench**.

- Add a `Tests\TestCase` extending `Orchestra\Testbench\TestCase` that: loads the
  package `AuthorizationServiceProvider` + Spatie's `PermissionServiceProvider`
  (`getPackageProviders()`); runs Spatie's migrations; defines a minimal test
  `User` model (uses Spatie `HasRoles`) and a test `Role` enum implementing
  `AuthorizationRole`; configures the manager in `setUp` (or via a tiny test
  provider).
- Port the reference tests, adapting namespaces: `AuthorizationManagerTest`,
  `PermissionRegistryTest`, `BypassStrategyTest` (now covering `RoleBypass`
  including a `protected` case AND `NoBypass`), `PermissionSyncTest` (incl. the
  `roles()` sync branch with a granting test role), the teams tests, and
  `MakePolicyCommandTest`.
- Use `#[Group]` + `#[CoversClass]`. Read test verdicts the normal way here
  (no `laravel/pao` unless you add it).

---

## 5. Quality gates

- **PHPStan level 9** (`phpstan.neon`, analyse `src`), larastan extension. The
  package code must be 0 errors (the reference `app/Authorization/` already is).
- **Pint** for style (project conventions: `declare(strict_types=1)`, `final`,
  `readonly` where holding only injected state, typed properties, explicit return
  types, short `?Type`, blank line before control structures, full variable names,
  type-hinted closures, docblock generics, imported classnames in docblocks — not
  FQCN).
- `composer.json` `require`: `php ^8.4`, `illuminate/contracts` (or
  `illuminate/support`+`illuminate/auth`+`illuminate/database` as needed) for the
  target Laravel, `spatie/laravel-permission`. `require-dev`:
  `orchestra/testbench`, `phpunit/phpunit`, `larastan/larastan`, `laravel/pint`.
- Add package discovery in `composer.json`:
  ```json
  "extra": { "laravel": { "providers": ["AlexPavliukov\\Authorization\\AuthorizationServiceProvider"] } }
  ```

---

## 6. Build order (checklist)

1. Scaffold `composer.json` (name, PSR-4 `AlexPavliukov\Authorization\` → `src/`,
   requires, dev requires, extra.laravel.providers), `phpunit.xml`, `phpstan.neon`.
2. Move `app/Authorization/*` → `src/`, rename namespaces per §1.
3. Apply §3.1 (bypass split), §3.2 (HasValues), §3.3 (Authenticatable),
   §3.4 (AuthorizationSeeder + core provider + provider stub), §3.5 (verify stub).
4. Confirm **zero `App\` references** in `src/` (`grep -rn "App\\\\" src/` → empty).
5. Testbench setup + port tests (§4). Get the suite green.
6. PHPStan level 9 clean on `src/`; Pint clean.
7. Tag/branch: commit, push, `dev-main` available for consumers.

**Acceptance:** package suite green on Testbench; phpstan level 9 = 0; no `App\`
refs in `src/`; a Laravel app can `composer require apavliukov/laravel-authorization`,
publish the provider stub, declare its role enum + models, and authorize.

---

## 7. Consumer adoption (back in `laravel-starter` — separate task/session)

After the package is green, adopt it in the reference app (this is the "delete
local code, wire the package" step):

1. `composer require apavliukov/laravel-authorization:dev-main` (VCS — see §8).
2. Delete `app/Authorization/*` from the app.
3. Rewrite references `App\Authorization\` → `AlexPavliukov\Authorization\` across
   the app (policies, `User`, providers, seeders, tests, blades `@can`).
4. `php artisan vendor:publish` the `AuthorizationServiceProvider` stub; fill in
   `useRoleEnum(Role::class)` + `authorizableModels([...])` (+ `bypassUsing` if not
   the default `RoleBypass`). Keep it registered in `bootstrap/providers.php`.
5. Replace the app seeders with `AuthorizationSeeder`: `ConsistencySeeder` /
   `DatabaseSeeder` call `$this->call([AuthorizationSeeder::class])`; delete
   `PermissionSeeder`/`RoleSeeder`/`RoleAndPermissionSeeder`.
6. Run the app suite → green (the 3 pre-existing user-form failures aside).

---

## 8. How the starter consumes this package — Composer/VCS everywhere

**One mechanism for all environments: a normal Composer dependency from private
VCS.** The starter's committed `composer.json` requires it the same way locally,
in CI, and in prod. No symlink, no path-repository, no per-environment override.

```jsonc
// starter composer.json (committed — identical local / CI / prod)
{
  "repositories": [
    { "type": "vcs", "url": "git@github.com:apavliukov/laravel-authorization.git" }
  ],
  "require": { "apavliukov/laravel-authorization": "dev-main" }
}
```

**Dev loop:** develop and test the package **in its own repo** (the Testbench
suite is your fast inner loop — the starter is not needed for package work). When
you want the starter to pick up changes: commit+push the package, then in the
starter run `vendor/bin/sail composer update apavliukov/laravel-authorization`.

**Versioning:** `dev-main` while iterating (pulls latest `main`); cut a tag and
move the starter to `^0.1` once the API stabilises, for predictability.

**Prod/CI:** identical `composer install` from VCS — the only requirement is read
access to the private repo (a deploy key or a Composer auth token in
`auth.json` / `COMPOSER_AUTH`). That is standard Composer auth, not specific to
this package.

> Deliberately NOT used: local path-repository / symlink, `composer-merge-plugin`,
> `composer.local.json`, `docker-compose.override.yml`. With a real package test
> suite the symlink buys little and adds Docker/Composer-override complexity.
> Keep local == prod.

---

## 9. Reference — what each piece does (orient yourself)

Read the design spec (§5) for full detail. In short: `AuthorizationManager` holds
the app's role enum + authorizable models; `AbstractPolicy` turns policy methods
into permission-string checks via `PermissionRegistry::nameFromAbility()`
(`"{ability} {table}"`); `BypassGate` + a `BypassStrategy` short-circuits
`Gate::before` for super-admins (pluggable); `PermissionSync` idempotently seeds
permissions (from the manager's models) and roles (from `Role::permissions()`);
the teams path (`TeamResolver` + `SetPermissionsTeam`) is active only when Spatie
teams are enabled; `make:authorization-policy` scaffolds resource policies.
