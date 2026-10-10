---
paths:
  - app/Providers/AuthorizationServiceProvider.php
---

# Providers

## No tenancy or gate wiring in the provider
The provider only declares the role enum and authorizableModels(). Tenancy lives in config/authorization.php (model, resolver, column, areas) and the deny-all SystemAbility::ACCESS_PLATFORM_ADMIN gate is registered by the package once tenancy.model is set — do not re-add Gate::define() or resolveTenantUsing()/resolveTeamsUsing() here. If tenancy is stripped (CLAUDE.md → Stripping tenancy), the Gate::define(..., fn () => false) comes back here, still outside systemAbilities().
