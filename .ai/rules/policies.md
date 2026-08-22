---
paths:
  - app/Enums/Policies/Role.php
---

# Policies

## Declare every role grant in the Role enum
permissions() is the only source of role grants. ADMIN passes everything through the super-admin bypass (isSuperAdmin()), so MEMBER's empty array is an active deny-all, not a placeholder waiting to be filled. A permission granted anywhere else — a seeder, a migration, straight into the database — is wiped by the next authorization:sync.
