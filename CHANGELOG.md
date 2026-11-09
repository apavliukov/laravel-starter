# Changelog

All notable changes to this project will be documented in this file. See [commit-and-tag-version](https://github.com/absolute-version/commit-and-tag-version) for commit guidelines.

## Unreleased

Everything before the first tagged release, in the order it landed:

- **Foundation** — Laravel 13 on PHP 8.4 in Sail, PostgreSQL 17, Redis, Horizon, Telescope,
  Sentry, Mailbook; Pint, Rector, PHPStan, lefthook and commitlint on every commit.
- **Admin shell** — Livewire 4 + Flux 2 + Tailwind 4, split into the Platform
  (`/platform/*`) and Member (`/admin/*`) areas.
- **Business logic** — the service and repository layer replaced by Actions, Queries and
  readonly DTOs; `Users` and `Organizations` are the reference resources.
- **Authorization** — ability enums, an abstract policy and permission sync, later extracted
  into the `apavliukov/laravel-authorization` package (now ^1.6).
- **Tenancy** — `Organization` is the tenant and the Spatie permissions team. Membership is
  a role row in `model_has_roles`, ownership is `organizations.owner_id` with a derived
  `OWNER` role; the tenant scope fails closed.
