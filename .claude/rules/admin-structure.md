# Admin Structure Rules

Reference patterns for building pages and resources in role-aware admin areas.

> **Template, not gospel.** The patterns below are conventions for organizing role-scoped admin/portal areas. Adapt names, middleware, and URL prefixes to fit your app. Add a third area (e.g. `Manager`, `Vendor`) by mirroring the same shape — the layout machinery is designed to scale.

Detailed patterns are split across companion rule files:
- `admin-business-logic.md` — Actions, Queries, DTOs, Livewire Form Objects
- `admin-livewire.md` — Livewire component classes and their Blade views
- `admin-blade.md` — Resource layout, page views, View Components, modal naming

---

## Two Role Areas

The admin layer is split by audience. Each area has its own namespace, view path, route prefix, and layout — but they share the same page-composition patterns.

| Area | Namespace | View path | Route prefix | URL prefix | Middleware |
|------|-----------|-----------|--------------|------------|-----------|
| **Platform** | `App\...\Admin\Platform\` | `admin/platform/` | `admin.platform.*` | `/admin/platform/*` | `EnsureUserIsPlatformAdmin` |
| **Member** | `App\...\Admin\Member\` | `admin/member/` | `admin.member.*` | `/admin/member/*` | `EnsureUserIsMember` |

**Platform** is the cross-app super-admin area — managing global resources, users, settings. **Member** is the authenticated user's portal — what a logged-in member sees by default.

URL prefixes are independent of the namespace; rename `/admin/member/*` → `/dashboard/*` or `/portal/*` if that fits your app better. Keep the namespace/view-path symmetry so the directory layout stays predictable.

The examples below use the Member area; swap `member` → `platform` to build in the Platform area.

### Flat (shared) resources

Some resources are not bound to a single audience — for example, a `Users`
admin screen managed from the platform area but conceptually a shared
catalog. For these, the resource files live at the top level of each
subtree (no `Member/` or `Platform/` segment), while the route stays inside
the relevant area group:

| Element              | Path                                                   |
|----------------------|--------------------------------------------------------|
| Controller           | `app/Http/Controllers/Admin/{Resource}Controller.php`  |
| Livewire             | `app/Livewire/Admin/{Resources}/`                      |
| Resource layout      | `resources/views/components/admin/{resources}/`        |
| Pages                | `resources/views/pages/admin/{resources}/`             |
| Livewire views       | `resources/views/livewire/admin/{resources}/`          |
| Route                | `routes/admin/platform.php` (resolves under `admin.platform.{resources}.*`) or `routes/admin/member.php` |

The reference resource for this layout is `Users` — see
`app/Livewire/Admin/Users/` and `resources/views/livewire/admin/users/`.

Choose the flat layout when the resource is owned by one area but its
**namespace** has no natural area suffix; use the role-scoped layout
(`Admin/Member/{Resources}` or `Admin/Platform/{Resources}`) when the
resource genuinely differs by audience.

---

## Layout System

The base layout `x-layouts.admin` auto-routes to the correct area layout based on the authenticated user's role — pages don't pick the layout themselves. Source: `resources/views/components/layouts/admin.blade.php`.

```
<x-layouts.admin>
    ↓ picks one of:
    ├── <x-layouts.admin.platform>   (platform admin view)
    └── <x-layouts.admin.member>     (member portal view)
        ↓ both wrap:
        <x-admin.layouts.main>           (shared: back link, heading, badges, buttons, tabs)
            ↓ wrapped by each resource's:
            <x-admin.{area}.{resources}.layout>   (resource-specific header/tabs)
                ↓ renders:
                Livewire components + page content
```

Pages almost always use the area-specific resource layout (e.g. `<x-admin.member.posts.layout>`), which itself uses `<x-admin.layouts.main>` and ultimately `<x-layouts.admin>`.

---

## Directory Structure

```
app/
├── Actions/{Resources}/
│   ├── Create{Resource}.php
│   ├── Update{Resource}.php
│   └── Delete{Resource}.php
├── Queries/{Resources}/
│   └── List{Resources}Query.php
├── Dto/{Resources}/
│   ├── Create{Resource}Input.php
│   ├── Update{Resource}Input.php
│   └── List{Resources}Filters.php
├── Http/Controllers/Admin/
│   ├── Platform/{Resource}Controller.php
│   └── Member/{Resource}Controller.php
├── Livewire/Admin/
│   ├── Platform/{Resources}/...
│   └── Member/{Resources}/
│       ├── Forms/{Resource}Form.php
│       ├── {Resource}List.php
│       ├── Create{Resource}.php
│       ├── Edit{Resource}.php
│       └── Delete{Resource}.php
└── View/Components/Admin/
    ├── Platform/{Resources}/...
    └── Member/{Resources}/
        └── {Resource}Dashboard.php  # Static widget (no interactivity)

resources/views/
├── components/
│   ├── layouts/
│   │   ├── admin.blade.php              # Auto-routes to area layout
│   │   └── admin/
│   │       ├── platform.blade.php
│   │       └── member.blade.php
│   └── admin/
│       ├── platform/{resources}/...
│       ├── member/{resources}/
│       │   ├── layout.blade.php         # Resource layout (header + tabs)
│       │   ├── dashboard.blade.php      # Optional static widget
│       │   └── partials/
│       └── layouts/
│           ├── main.blade.php           # Shared main layout
│           └── partials/
├── pages/admin/
│   ├── platform/{resources}/...
│   └── member/{resources}/
│       ├── index.blade.php
│       ├── create.blade.php
│       ├── edit.blade.php
│       ├── show.blade.php
│       └── tabs/
└── livewire/admin/
    ├── platform/{resources}/...
    └── member/{resources}/
        ├── {resource}-list.blade.php
        ├── create-{resource}.blade.php
        ├── edit-{resource}.blade.php
        ├── delete-{resource}.blade.php
        └── partials/form.blade.php
```

**List is Livewire, dashboard is a View Component.** List components need interactivity (search, filter, pagination, row actions); static widgets that just display data stay as View Components.

---

## Controller

Controllers are thin — routing and authorization only. No data loading beyond what route-model binding provides.

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Member;

use App\Http\Controllers\Controller;
use App\Models\{Resource};
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class {Resource}Controller extends Controller
{
    public function index(): View
    {
        return view('pages.admin.member.{resources}.index');
    }

    public function show({Resource} ${resource}): RedirectResponse|View
    {
        // Either redirect to edit:
        return redirect()->route('admin.member.{resources}.edit', ${resource});

        // Or show a dashboard:
        return view('pages.admin.member.{resources}.show', ['{resource}' => ${resource}]);
    }

    public function create(): View
    {
        return view('pages.admin.member.{resources}.create');
    }

    public function edit({Resource} ${resource}): View
    {
        return view('pages.admin.member.{resources}.edit', ['{resource}' => ${resource}]);
    }

    public function {tab}({Resource} ${resource}): View
    {
        return view('pages.admin.member.{resources}.tabs.{tab}', ['{resource}' => ${resource}]);
    }
}
```

## Routes (`routes/admin/member.php`)

```php
Route::prefix('{resources}')
    ->name('{resources}.')
    ->controller({Resource}Controller::class)
    ->group(function (): void {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');

        Route::prefix('{resource}')->group(function (): void {
            Route::get('/', 'show')->name('show');
            Route::get('/edit', 'edit')->name('edit');
            Route::get('/{tab}', '{tab}')->name('{tab}');
        });
    });
```

Resolved route names: `admin.member.{resources}.index`, `admin.member.{resources}.edit`, etc.

---

## Checklist for a New Resource

Swap `member` → `platform` for the Platform area.

### Backend
- [ ] Controller: `app/Http/Controllers/Admin/Member/{Resource}Controller.php`
- [ ] Routes: resource block in `routes/admin/member.php`
- [ ] Action Create: `app/Actions/{Resources}/Create{Resource}.php`
- [ ] Action Update: `app/Actions/{Resources}/Update{Resource}.php`
- [ ] Action Delete: `app/Actions/{Resources}/Delete{Resource}.php`
- [ ] Query: `app/Queries/{Resources}/List{Resources}Query.php`
- [ ] DTO Create input: `app/Dto/{Resources}/Create{Resource}Input.php`
- [ ] DTO Update input: `app/Dto/{Resources}/Update{Resource}Input.php`
- [ ] DTO Filters: `app/Dto/{Resources}/List{Resources}Filters.php`
- [ ] Livewire Form Object: `app/Livewire/Admin/Member/{Resources}/Forms/{Resource}Form.php`
- [ ] Livewire List: `app/Livewire/Admin/Member/{Resources}/{Resource}List.php`
- [ ] Livewire Create: `app/Livewire/Admin/Member/{Resources}/Create{Resource}.php`
- [ ] Livewire Edit: `app/Livewire/Admin/Member/{Resources}/Edit{Resource}.php`
- [ ] Livewire Delete: `app/Livewire/Admin/Member/{Resources}/Delete{Resource}.php`
- [ ] (optional) View Component dashboard: `app/View/Components/Admin/Member/{Resources}/{Resource}Dashboard.php`

### Views
- [ ] Resource layout: `resources/views/components/admin/member/{resources}/layout.blade.php`
- [ ] Index page: `resources/views/pages/admin/member/{resources}/index.blade.php`
- [ ] Create page: `resources/views/pages/admin/member/{resources}/create.blade.php`
- [ ] Edit page: `resources/views/pages/admin/member/{resources}/edit.blade.php`
- [ ] Show page (optional): `resources/views/pages/admin/member/{resources}/show.blade.php`
- [ ] Tab pages (optional): `resources/views/pages/admin/member/{resources}/tabs/{tab}.blade.php`
- [ ] List view: `resources/views/livewire/admin/member/{resources}/{resource}-list.blade.php`
- [ ] Create view: `resources/views/livewire/admin/member/{resources}/create-{resource}.blade.php`
- [ ] Edit view: `resources/views/livewire/admin/member/{resources}/edit-{resource}.blade.php`
- [ ] Delete view: `resources/views/livewire/admin/member/{resources}/delete-{resource}.blade.php`
- [ ] Form partial: `resources/views/livewire/admin/member/{resources}/partials/form.blade.php`

### Navigation
- [ ] Platform: menu item in `resources/views/components/admin/platform/layouts/navigation/` (header and/or sidebar)
- [ ] Member: `resources/views/components/admin/member/layouts/navigation/`
