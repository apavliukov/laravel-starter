---
paths:
  - 'routes/admin/**'
---

# Admin

## Area gates are the package tenancy middleware
Neither group adds a URL prefix: routes/admin/platform.php serves /admin/users, not /admin/platform/users. The platform group runs under AlexPavliukov\Authorization\Tenancy\EnsurePlatformArea (deny-all accessPlatformAdmin gate + request-wide tenant bypass); the member group under EnsureTenantSelected (403 without an organization). Do not write app-side gate middleware — attach one of these two to every new group, and never leave a group ungated.
