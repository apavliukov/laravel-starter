---
paths:
  - app/Models/User.php
  - app/Models/Organization.php
---

# Models

## Membership lives only in model_has_roles
There is no users.organization_id and no membership pivot: User::tenants() / Organization::members() (package traits) read model_has_roles.team_id. Spatie teams are on and hasRole()/getRoleNames() only see the active team, so identity reads go through the package (app_role, is_platform_admin, whereHasRole) — never hasRole(). Role writes go through TenantMembership only: assignGlobal for ADMIN (CreateUser/UpdateUser), AddOrganizationMember/RemoveOrganizationMember for memberships. UpdateUser never touches memberships — a user may belong to several organizations.

## app_role and is_platform_admin are not authorization
Both come from the package HasAppRole trait and exist for routing and UI branching only. Every access decision goes through the Gate — a policy or can() — never a role check. app_role is null for a user without any role (a freshly registered user); Role::layout() callers (sidebar, dashboard) fall back to Role::default().

## owner_id is the only ownership record and is never mass-assigned
organizations.owner_id (NOT NULL, FK users, restrictOnDelete) is the single source of truth for ownership; the OWNER role row is derived from it by the package (tenancy.owner_role). The column grants no access — the OWNER role's permissions do. Keep owner_id out of #[Fillable]: it changes only through TenantMembership::transferOwnership() (wrapped by TransferOrganizationOwnership; CreateOrganization sets it once via owner()->associate() and then calls transferOwnership to write the role row). Organizations are archived (SoftDeletes), never destroyed, members included — the resolver makes an archived organization invisible; cascades to its own records are a per-project decision, not the template's.
