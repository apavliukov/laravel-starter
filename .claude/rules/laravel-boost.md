# Laravel Boost & Application Guidelines

## Package Versions

- php - 8.4
- laravel/framework (LARAVEL) - v13
- laravel/horizon (HORIZON) - v5
- laravel/prompts (PROMPTS) - v0
- livewire/flux (FLUXUI_FREE) - v2
- livewire/livewire (LIVEWIRE) - v4
- larastan/larastan (LARASTAN) - v3
- laravel/boost (BOOST) - v2
- laravel/mcp (MCP) - v0
- laravel/pail (PAIL) - v1
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- laravel/telescope (TELESCOPE) - v5
- phpunit/phpunit (PHPUNIT) - v13
- rector/rector (RECTOR) - v2
- fruitcake/laravel-debugbar - v4
- alpinejs (ALPINEJS) - v3
- eslint (ESLINT) - v10
- prettier (PRETTIER) - v3
- tailwindcss (TAILWINDCSS) - v4

## Boost Skills Activation

This project has domain-specific skills available in `.claude/skills/`. Activate the relevant skill whenever you work in that domain — don't wait until you're stuck.

- `laravel-best-practices` — writing/reviewing/refactoring backend Laravel PHP (controllers, models, migrations, form requests, policies, jobs, Eloquent queries, N+1 issues, caching, authorization).
- `configuring-horizon` — Horizon installation, `config/horizon.php`, dashboard authorization, queue supervisors, production troubleshooting.
- `fluxui-development` — Flux UI components in Livewire (`<flux:*>`), forms, modals, tables, validation patterns, theming.
- `tailwindcss-development` — Tailwind utility classes in Blade templates, responsive grids, dark mode, v4 changes.
- `debug-using-debugbar` — inspect Debugbar-captured data via Artisan CLI to diagnose slow requests, N+1, exceptions, failed requests.

## Conventions

- Follow all existing code conventions. When creating or editing a file, check sibling files for correct structure, approach, naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.
- Be concise in explanations — focus on what's important rather than explaining obvious details.

## Application Structure

- Stick to existing directory structure — don't create new base folders without approval.
- Do not change the application's dependencies without approval.
- Do not create verification scripts or tinker when tests cover that functionality.
- Only create documentation files if explicitly requested by the user.
- If frontend changes aren't reflected in UI, ask the user to run `vendor/bin/sail yarn run build`, `vendor/bin/sail yarn run dev`, or `vendor/bin/sail composer run dev`.

## Boost MCP Tools

Prefer Boost MCP tools over manual alternatives like shell commands or file reads.

- **search-docs**: Use before any other approach for Laravel-ecosystem documentation. Returns version-specific docs based on installed packages automatically. Pass a `packages` array to scope results.
  - Use multiple, broad, simple, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`.
  - Do not add package names to queries — package info is already shared.
  - Search syntax:
    1. Simple words with auto-stemming: `authentication` finds 'authenticate' and 'auth'.
    2. Multiple words (AND logic): `rate limit`.
    3. Quoted phrases (exact position): `"infinite scroll"`.
    4. Mixed: `middleware "rate limit"`.
    5. Multiple queries for OR logic: `["authentication", "middleware"]`.
- **list-artisan-commands**: Use to check available parameters before calling Artisan commands.
- **tinker**: Execute PHP in app context. Always wrap in single quotes to prevent shell expansion: `vendor/bin/sail artisan tinker --execute 'Your::code();'`. Use double quotes for PHP strings inside.
- **database-query**: Read-only queries against the database. Prefer this over raw SQL in tinker.
- **database-schema**: Inspect table structure before writing migrations or models.
- **browser-logs**: Read browser logs, errors, exceptions. Only recent logs are useful — ignore old entries.
- **get-absolute-url**: Use whenever sharing a project URL to ensure correct scheme, domain/IP, and port.

## Artisan

- Run Artisan commands directly (e.g., `vendor/bin/sail artisan route:list`). Discover with `vendor/bin/sail artisan list`, inspect parameters with `--help`.
- Inspect routes: `vendor/bin/sail artisan route:list`. Filter with `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration: `vendor/bin/sail artisan config:show app.name`. Or read config files directly.
- To check environment variables, read `.env` directly.

## Laravel Artisan & Models

- Use `vendor/bin/sail artisan make:` commands to create new files. For generic PHP classes, use `make:class`.
- Pass `--no-interaction` to all Artisan commands. Pass correct `--options` for desired behavior.
- When creating new models, also create useful factories and seeders. Ask the user if they need other things, using `list-artisan-commands` to check available `make:model` options.
- For APIs, default to Eloquent API Resources and API versioning unless existing routes don't, then follow existing convention.
- Use queued jobs for time-consuming operations with the `ShouldQueue` interface.
- Use Laravel's built-in auth and authorization features (gates, policies, Sanctum).

## Laravel 13 Structure

- Use `search-docs` for version-specific documentation.
- No middleware files in `app/Http/Middleware/`.
- `bootstrap/app.php` registers middleware, exceptions, and routing files.
- `bootstrap/providers.php` contains application-specific service providers.
- No `app/Console/Kernel.php` — use `bootstrap/app.php` or `routes/console.php` for console configuration.
- Commands auto-register — files in `app/Console/Commands/` are automatically available.
- When modifying a column in a migration, include all previously defined attributes or they will be dropped.
- Limit eagerly loaded records natively: `$query->latest()->limit(10)`.
- Casts should be set in a `casts()` method on a model rather than `$casts` property. Follow existing conventions.

## Pint Code Formatter

- After modifying PHP files, run `vendor/bin/sail bin pint --dirty --format agent` before finalizing changes.
- Do not run `vendor/bin/sail bin pint --test`; run `vendor/bin/sail bin pint --dirty --format agent` to fix formatting issues in-place.
- Never run Pint over the entire project — always scope to dirty/staged files.

## Vite Error

- If you receive "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest", run `vendor/bin/sail yarn run build` or ask the user to run `vendor/bin/sail yarn run dev` or `vendor/bin/sail composer run dev`.

## Refreshing Boost Guidelines

Boost auto-regeneration of `<laravel-boost-guidelines>` into CLAUDE.md is disabled in this project (`guidelines: true` in `boost.json` but the `boost:update` call is removed from `composer.json` post-update hooks).

To check whether boost has new convention hints to pull in after a `laravel/boost` version bump:

1. Run `vendor/bin/sail artisan boost:update --ansi` manually.
2. `git diff CLAUDE.md` to see the regenerated block.
3. Cherry-pick genuinely new/useful bits into this file.
4. `git checkout CLAUDE.md` to discard the block.
