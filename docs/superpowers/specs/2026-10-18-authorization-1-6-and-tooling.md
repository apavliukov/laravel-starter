# laravel-authorization 1.3.2 → 1.6.0 and the oohire tooling alignment — laravel-starter

Status: planned and applied 2026-10-18. Oohire and opiny are on 1.5.2 and take 1.6 after
this; the starter jumps straight from 1.3.2, so this is the reference for their 1.6
upgrade notes too (`CHANGELOG.md` 1.4.0–1.6.0 of the package names the exact spots).
Folds in the open items of the 2026-09-22 "align with oohire" note (its items 1–3; item 4
below is a repo setting, item 6 was done, 5 and 7 never applied here).

## What changed in the package since 1.3.2

| Version | Effect on this app |
|---|---|
| 1.3.3 | `HasMembers::owner()` typed as the auth user → drop the `User::query()->findOrFail($organization->owner_id)` workaround in `OrganizationFactory`. |
| 1.4.0 | Security: `BelongsToTenant` keeps `TenantScope` when Eloquent restores a model (Livewire props, `SerializesModels`). No tenant-owned model here, nothing to adapt; `FailClosedScopeTest` stays the reference. |
| 1.5.x | `choose()` / `enterable_scope` / `fallback_to_owned` / `TenantChoiceDropped` — never adopted here; `allowsNullTenant()`; relation-path fence by tenant only. Nothing to adapt. |
| 1.6.0 | **API cut.** Gone: `Tenancy\CurrentArea`, `authorization.areas`, `SetPermissionsTeam` / `resolveTeamsUsing`, `BypassStrategy` & co, `resolveTenantUsing` / `tenantColumn` / `systemAbilities`, `UserPermissionMemo`, the 1.5 tenant-entry knobs, and the `authorization:sync` / `authorization:repair-ownership` / `make:authorization-policy` commands. `AuthorizationSeeder` still syncs permissions and re-derives owner rows every deploy. |

Every other symbol the app imports (`TenantMembership`, `Tenancy`, `TenantResolver`,
`SetCurrentTenant`, `EnsurePlatformArea`, `EnsureTenantSelected`, `BelongsToTenant`,
`HasMembers`, `HasTenantMemberships`, `HasAppRole`, `HasTeamAwareRoles`, `HasPolicy`,
`AbstractPolicy`, `TenantScopedPolicy`, `Permissions`, `ScalarKey`, `AuthorizationSeeder`,
`OwnershipException`, `MissingTenantContextException`, `InteractsWithTenancy`, the
`Authorization` facade methods `useRoleEnum` / `authorizableModels` / `userHasGlobalRole`)
exists unchanged in 1.6.0 (`src/` tree of tag v1.6.0).

## Steps

### 1. Bump

`vendor/bin/sail composer require apavliukov/laravel-authorization:^1.6.0`, then
`vendor/bin/sail artisan boost:update --ansi` (regenerates
`.claude/skills/laravel-authorization-development` from the 1.6 package; strip the
injected `<laravel-boost-guidelines>` block from `CLAUDE.md`). `_ide_helper*.php` are the
user's, leave them.

### 2. `CurrentLayout` owns its route patterns

`app/Support/Areas/CurrentLayout.php` loses the `CurrentArea` dependency and the
`config('authorization.areas')` read. The UI area is app routing (ADOPTION §3 "Area ≠
tenant"); the starter has no view switcher and no remembered area, so the class keeps
exactly its two rules: inside an area (route name matches the area's pattern) the area's
layout, else `app_role` → `Role::layout()`. The patterns become a private constant on the
class — `platform => admin.platform.*`, `member => admin.member.*`. `CurrentLayoutTest`
is unchanged and must stay green. Oohire's `app/Support/Areas.php` (session-remembered
area, accessible areas per user) is the shape to grow into the day a switcher exists —
not before.

### 3. Config and factory

- `config/authorization.php`: delete the `areas` block and its comment; the file is the
  package's 1.6 shape plus the app's values.
- `OrganizationFactory::configure()`: `transferOwnership($organization, $organization->owner()->firstOrFail())`.

### 4. Docs that name removed API

- `CLAUDE.md`: tenancy bullet (no `areas` in config), "Stripping tenancy" step 1 (file
  stays for nothing but the tenancy block → "delete the file" is wrong, it still holds
  `model => null`; reword), the `SystemAbility` bullet (`systemAbilities()` no longer
  exists — say the gate is the package's and the enum only names it), the deviation
  paragraph (`CurrentArea` → `CurrentLayout`'s own patterns), skills paragraph unchanged.
- `.ai/rules/providers.md` (areas; `resolveTenantUsing()` / `resolveTeamsUsing()` no longer
  exist), `.ai/rules/admin.md` (`authorization.areas` → `CurrentLayout`),
  `.ai/rules/policies.md` (`authorization:sync` → the `AuthorizationSeeder` run on deploy).
  Boost's `record-rule` cannot edit a rule body; hand-edit and say so in the report.

### 5. Tooling from the 2026-09-22 note

1. **PhpStorm interpreter runs as root** → `docker-compose.phpstorm.yml` (opiny
   `b4fbd42` / oohire `ae2d7aa`: `user: "${WWWUSER:-1337}:${WWWGROUP:-1337}"`,
   `entrypoint: []`, `HOME: /tmp`), README paragraph (opiny README "PhpStorm interpreter"),
   `docker-compose.override.yml` git-ignored. The starter's `start-container` has no
   `chmod -R 777 /tmp` to remove.
2. **Lefthook execs the container as root** → the two `docker compose -f docker-compose.yml
   exec -T app composer run-script …` lines become `vendor/bin/sail composer run-script …`.
3. **PostCSS layer** → remove `@fullhuman/postcss-purgecss`, `@tailwindcss/postcss`,
   `cssnano`, `postcss`, `postcss-preset-env` from `package.json`, delete
   `postcss.config.js`, `yarn install`, `yarn build`; `@tailwindcss/vite` is what runs.
   Compare `public/build/assets/admin-*.css` size before/after (opiny: byte-identical
   outside production, where cssnano never ran either).
4. **Dependabot** — decided 2026-10-18: no version-update config in any project; security
   pull requests come from the GitHub repository setting, which needs no file (opiny
   `5f50e6e`). The starter has no `.github/` → nothing in the repo. **Oohire still carries
   the majors-only `.github/dependabot.yml`** (`7b8aabf3`) — delete it there.

### 6. Verify

`vendor/bin/sail composer test`, PHPStan on the touched PHP files, Pint on dirty files,
`check-translations` untouched (no UI strings change). Then delete
`TODO-align-with-oohire-2026-09-22.md` and the obsolete
`docs/superpowers/specs/2026-10-10-tenancy-1-2-rollout.md` (applied in `115ba6d`, superseded
by `87843cf`).

Commit as `chore(authorization): take package 1.6, CurrentLayout owns its area patterns; align tooling with oohire`,
this brief with it.
