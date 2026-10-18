---
paths:
  - app/Enums/Policies/Role.php
  - 'app/Enums/Policies/*.php'
---

# Policies

## Declare every role grant in the Role enum
permissions() is the only source of role grants. ADMIN passes everything through the super-admin bypass (isSuperAdmin()), so MEMBER's empty array is an active deny-all, not a placeholder waiting to be filled. A permission granted anywhere else — a seeder, a migration, straight into the database — is wiped by the next AuthorizationSeeder run (every deploy via ConsistencySeeder).

## Role priority is byPriority(), OWNER is the organization's full set
Role::byPriority() is the explicit ranking behind $user->app_role (landing page, role badge): [ADMIN, OWNER, MEMBER], highest first. Declaration order means nothing; the package's sync throws if the list misses or repeats a case, so add every new case there. OWNER is an ordinary enumerated role (view/update the organization + OrganizationAbility::TRANSFER_OWNERSHIP), by convention a superset of every lower organization role; it is exclusive to the user in organizations.owner_id and is never handed out through memberships — the package derives it from the column. ADMIN stays the global super-admin bypass, MEMBER stays an active deny-all. Custom organization abilities live in OrganizationAbility and are declared via Organization::getCustomAbilities(); the policy method is named after the enum value.
