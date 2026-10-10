---
paths:
  - app/Models/User.php
---

# Models

## Membership lives only in model_has_roles
There is no users.organization_id and no membership pivot: User::tenants() / Organization::members() (package traits) read model_has_roles.team_id. Spatie teams are on and hasRole()/getRoleNames() only see the active team, so identity reads go through the package (app_role, is_platform_admin, whereHasRole) — never hasRole(). Role writes go through TenantMembership only: assignGlobal for ADMIN (CreateUser/UpdateUser), AddOrganizationMember/RemoveOrganizationMember for memberships. UpdateUser never touches memberships — a user may belong to several organizations.

## app_role and is_platform_admin are not authorization
Both come from the package HasAppRole trait and exist for routing and UI branching only. Every access decision goes through the Gate — a policy or can() — never a role check. app_role is null for a user without any role (a freshly registered user); Role::layout() callers (sidebar, dashboard) fall back to Role::default().
