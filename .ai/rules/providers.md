---
paths:
  - app/Providers/AuthorizationServiceProvider.php
---

# Providers

## No tenancy or gate wiring in the provider
The provider only declares the role enum and authorizableModels(). Tenancy lives in config/authorization.php (model, foreign_key, session_key, areas) and the deny-all SystemAbility::ACCESS_PLATFORM_ADMIN gate is registered by the package unconditionally, with or without tenancy — do not re-add Gate::define(), resolveTenantUsing() or resolveTeamsUsing() here.
