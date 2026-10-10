---
paths:
  - app/Models/User.php
---

# Models

## is_admin and is_member are not authorization
Both accessors exist for routing and UI branching only. Every access decision goes through the Gate — a policy or can() — never a role check.

## Read roles team-agnostically
Spatie teams are on and hasRole()/getRoleNames() only see the active team, so a MEMBER (assigned inside the organization team) is invisible to an ADMIN request and vice versa. appRole, is_admin, is_member and the withRole scope go through the package (Authorization::primaryRole / userHasGlobalRole / userHasRole, whereHasRole) — never hasRole() for identity. Role writes go through App\Support\Users\SyncsUserRole only.
