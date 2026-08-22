---
paths:
  - app/Providers/AuthorizationServiceProvider.php
---

# Providers

## ACCESS_PLATFORM_ADMIN is a deny-all gate, not a system ability
It is registered with a direct Gate::define(..., static fn (): bool => false), deliberately outside Authorization::systemAbilities(). Only the super-admin bypass can satisfy it, so no role can ever be granted it. Register grantable abilities through the package instead; keep this one as it is.
