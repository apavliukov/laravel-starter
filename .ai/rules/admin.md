---
paths:
  - 'routes/admin/**'
---

# Admin

## Area URLs: /platform for platform, /admin for members; names keep admin.{area}.
Route names carry admin.{area}., URLs follow oohire: routes/admin/platform.php is served at /platform/* (EnsurePlatformArea: deny-all accessPlatformAdmin gate + request-wide tenant bypass), routes/admin/member.php at /admin/* (EnsureTenantSelected: 403 without an organization); dashboard and settings are area-less at /dashboard and /settings/*. Do not write app-side gate middleware — attach one of the two package middlewares to every new group, never leave a group ungated. App\Support\Areas\CurrentLayout matches on route names, so never rename the admin.platform./admin.member. prefixes.
