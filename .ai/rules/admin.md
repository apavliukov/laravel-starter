---
paths:
  - 'routes/admin/**'
---

# Admin

## The platform/member split is route names only
Neither group adds a URL prefix: routes/admin/platform.php serves /admin/users, not /admin/platform/users. Only the platform group is gated, by EnsurePlatformAdminAccessMiddleware. routes/admin/member.php carries no middleware of its own, so anything added there is reachable by every authenticated, verified user — attach the gate explicitly.
