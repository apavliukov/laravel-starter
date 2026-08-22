---
paths:
  - app/Models/User.php
---

# Models

## is_admin and is_member are not authorization
Both accessors exist for routing and UI branching only. Every access decision goes through the Gate — a policy or can() — never a role check.
