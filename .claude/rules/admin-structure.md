# Admin Structure Rules

Reference patterns for building pages and resources in role-aware admin areas.

> **Template, not gospel.** The patterns below are conventions for organizing role-scoped admin/portal areas. Adapt names, middleware, and URL prefixes to fit your app. Add a third area (e.g. `Manager`, `Vendor`) by mirroring the same shape — the layout machinery is designed to scale.

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
├── Http/Controllers/Admin/
│   ├── Platform/{Resource}Controller.php
│   └── Member/{Resource}Controller.php
├── Livewire/Admin/
│   ├── Platform/{Resources}/...
│   └── Member/{Resources}/
│       ├── {Resource}List.php      # Livewire list component (with filters/pagination)
│       ├── Create{Resource}.php
│       ├── Edit{Resource}.php
│       └── Delete{Resource}.php    # Modal
└── View/Components/Admin/
    ├── Platform/{Resources}/...
    └── Member/{Resources}/
        └── {Resource}Dashboard.php # Static widget (stats card, no interactivity)

resources/views/
├── components/
│   ├── layouts/
│   │   ├── admin.blade.php              # Auto-routes to area layout
│   │   └── admin/
│   │       ├── platform.blade.php
│   │       └── member.blade.php
│   └── admin/
│       ├── platform/{resources}/...
│       ├── member/{resources}/         # Member components
│       │   ├── layout.blade.php         # Resource layout (header + tabs)
│       │   ├── dashboard.blade.php      # Optional dashboard widget
│       │   └── partials/                # Shared bits
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

**List is Livewire, dashboard is a View Component.** This is the convention — list components typically need interactivity (search, filter, pagination, row actions) so they're Livewire; static widgets that just read data can stay as View Components.

---

## Patterns

### Controller (Member area)

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
        // Either redirect to edit tab:
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

### Routes (`routes/admin/member.php`)

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

## Resource Layout

The resource layout wraps every page of a resource — it renders the heading, action buttons, status badges, and tab navigation.

```blade
{{-- resources/views/components/admin/member/{resources}/layout.blade.php --}}
@props([
    'mainHeading' => isset(${resource}) ? ${resource}->name : null,
    'hasTabs' => false,
    'tabHeading' => null,
    'tabSubheading' => null,
    '{resource}' => null,
])

<x-admin.layouts.main :$mainHeading :$tabHeading :$tabSubheading>
    <x-slot:headerBadges>
        @isset(${resource})
            {{-- e.g. status badge, counters --}}
        @endisset
    </x-slot:headerBadges>

    <x-slot:headerButtons>
        @isset(${resource})
            <flux:button variant="primary" size="sm" icon="pencil"
                :href="route('admin.member.{resources}.edit', ${resource})" wire:navigate>
                {{ __('Edit') }}
            </flux:button>
        @else
            <flux:button variant="primary" size="sm" icon="plus"
                :href="route('admin.member.{resources}.create')" wire:navigate>
                {{ __('Add {Resource}') }}
            </flux:button>
        @endisset
    </x-slot:headerButtons>

    @if($hasTabs)
        <x-slot:tabs>
            <flux:navlist>
                <flux:navlist.item :href="route('admin.member.{resources}.edit', ${resource})"
                    icon="pencil" wire:navigate>
                    {{ __('Edit') }}
                </flux:navlist.item>
                <flux:navlist.item :href="route('admin.member.{resources}.{tab}', ${resource})"
                    icon="..." wire:navigate>
                    {{ __('Tab Name') }}
                </flux:navlist.item>
            </flux:navlist>
        </x-slot:tabs>
    @endif

    {{ $slot }}
</x-admin.layouts.main>
```

### Shared Main Layout

`<x-admin.layouts.main>` lives at `resources/views/components/admin/layouts/main.blade.php` — shared across all areas. It provides back link, heading, badges/buttons row, and optional tabs. It wraps `<x-layouts.admin>` which auto-picks the area layout.

---

## Page Views

### Index

```blade
{{-- pages/admin/member/{resources}/index.blade.php --}}
<x-admin.member.{resources}.layout :main-heading="__('Resources')">
    <livewire:admin.member.{resources}.{resource}-list />
</x-admin.member.{resources}.layout>
```

### Create

```blade
{{-- pages/admin/member/{resources}/create.blade.php --}}
<x-admin.member.{resources}.layout :main-heading="__('Create {Resource}')">
    <livewire:admin.member.{resources}.create-{resource} />
</x-admin.member.{resources}.layout>
```

### Edit

```blade
{{-- pages/admin/member/{resources}/edit.blade.php --}}
<x-admin.member.{resources}.layout
    :main-heading="${resource}->name"
    :${resource}
    :has-tabs="true"
    :tab-heading="__('Edit')"
>
    <livewire:admin.member.{resources}.edit-{resource} :${resource} />
</x-admin.member.{resources}.layout>
```

### Show / Dashboard

```blade
{{-- pages/admin/member/{resources}/show.blade.php --}}
<x-admin.member.{resources}.layout :main-heading="${resource}->name" :${resource}>
    <x-admin.member.{resources}.dashboard :${resource} />
</x-admin.member.{resources}.layout>
```

### Tab

```blade
{{-- pages/admin/member/{resources}/tabs/{tab}.blade.php --}}
<x-admin.member.{resources}.layout
    :main-heading="${resource}->name"
    :${resource}
    :has-tabs="true"
    :tab-heading="__('Tab Name')"
>
    <livewire:admin.member.{resources}.{tab} :${resource} />
</x-admin.member.{resources}.layout>
```

---

## Livewire Components

### List

```php
<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Member\{Resources};

use App\Models\{Resource};
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

final class {Resource}List extends Component
{
    use WithPagination;

    public string $search = '';

    #[Computed]
    public function {resources}(): LengthAwarePaginator
    {
        return {Resource}::query()
            ->when($this->search, fn ($q) => $q->where('name', 'ilike', "%{$this->search}%"))
            ->orderBy('name')
            ->paginate();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        return view('livewire.admin.member.{resources}.{resource}-list');
    }
}
```

### Create

```php
<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Member\{Resources};

use App\Models\{Resource};
use Illuminate\Contracts\View\View;
use Livewire\Component;

final class Create{Resource} extends Component
{
    public string $name = '';
    public string $description = '';

    /** @return array<string, array<int, mixed>> */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function store(): void
    {
        $this->validate();

        ${resource} = {Resource}::query()->create([
            'name' => $this->name,
            'description' => $this->description,
        ]);

        session()?->flash('toast', [
            'message' => __('{Resource} created successfully'),
            'variant' => 'success',
        ]);

        $this->redirect(route('admin.member.{resources}.edit', ${resource}), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.admin.member.{resources}.create-{resource}');
    }
}
```

### Edit

```php
<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Member\{Resources};

use App\Models\{Resource};
use Illuminate\Contracts\View\View;
use Livewire\Component;

final class Edit{Resource} extends Component
{
    public {Resource} ${resource};

    public string $name = '';
    public string $description = '';

    public function mount({Resource} ${resource}): void
    {
        $this->{resource} = ${resource};
        $this->name = ${resource}->name ?? '';
        $this->description = ${resource}->description ?? '';
    }

    /** @return array<string, array<int, mixed>> */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function update(): void
    {
        $this->validate();

        $this->{resource}->update([
            'name' => $this->name,
            'description' => $this->description,
        ]);

        session()?->flash('toast', [
            'message' => __('{Resource} updated successfully'),
            'variant' => 'success',
        ]);

        $this->redirect(route('admin.member.{resources}.edit', $this->{resource}), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.admin.member.{resources}.edit-{resource}');
    }
}
```

### Delete (Modal)

```php
<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Member\{Resources};

use App\Models\{Resource};
use Illuminate\Contracts\View\View;
use Livewire\Component;

final class Delete{Resource} extends Component
{
    public {Resource} ${resource};
    public string $modalName;

    public function mount({Resource} ${resource}, string $modalName): void
    {
        $this->{resource} = ${resource};
        $this->modalName = $modalName;
    }

    public function delete(): void
    {
        $this->{resource}->delete();

        self::modal($this->modalName)->close();

        session()?->flash('toast', [
            'message' => __('{Resource} deleted successfully'),
            'variant' => 'success',
        ]);

        $this->redirect(route('admin.member.{resources}.index'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.admin.member.{resources}.delete-{resource}');
    }
}
```

---

## Livewire Views

### Create

```blade
{{-- livewire/admin/member/{resources}/create-{resource}.blade.php --}}
<div class="w-full max-w-2xl">
    <form wire:submit="store" class="space-y-6">
        @include('livewire.admin.member.{resources}.partials.form')

        <div class="border-t border-neutral-200 pt-6 dark:border-neutral-700">
            <flux:button type="submit" variant="primary">
                {{ __('Create {Resource}') }}
            </flux:button>
        </div>
    </form>
</div>
```

### Edit

```blade
{{-- livewire/admin/member/{resources}/edit-{resource}.blade.php --}}
<div class="w-full max-w-2xl">
    <form wire:submit="update" class="space-y-6">
        @include('livewire.admin.member.{resources}.partials.form')

        <div class="border-t border-neutral-200 pt-6 dark:border-neutral-700">
            <flux:button type="submit" variant="primary">
                {{ __('Update {Resource}') }}
            </flux:button>
        </div>
    </form>
</div>
```

### Shared form partial

```blade
{{-- livewire/admin/member/{resources}/partials/form.blade.php --}}
<div class="space-y-6">
    <flux:field>
        <flux:label>{{ __('Name') }}</flux:label>
        <flux:input wire:model="name" placeholder="{{ __('Enter name...') }}" />
        <flux:error name="name" />
    </flux:field>

    <flux:field>
        <flux:label>{{ __('Description') }}</flux:label>
        <flux:textarea wire:model="description" rows="4"
            placeholder="{{ __('Enter description...') }}" />
        <flux:error name="description" />
    </flux:field>
</div>
```

### Delete (modal)

```blade
{{-- livewire/admin/member/{resources}/delete-{resource}.blade.php --}}
<flux:modal :name="$modalName" class="w-full max-w-md">
    <div class="space-y-6">
        <flux:heading size="lg">{{ __('Delete {Resource}') }}</flux:heading>

        <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-900/20">
            <div class="flex gap-3">
                <flux:icon.exclamation-triangle class="h-5 w-5 text-amber-500" />
                <div>
                    <flux:text class="font-medium text-amber-800 dark:text-amber-200">
                        {{ __('This action cannot be undone.') }}
                    </flux:text>
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <flux:modal.close>
                <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
            </flux:modal.close>
            <flux:button wire:click="delete" variant="danger">{{ __('Delete') }}</flux:button>
        </div>
    </div>
</flux:modal>
```

---

## View Components (static widgets)

Reserve View Components for non-interactive bits — dashboard stats cards, sidebars, static summaries. Anything needing pagination, search, row actions, or wire-model binding should be Livewire.

```php
<?php

declare(strict_types=1);

namespace App\View\Components\Admin\Member\{Resources};

use App\Models\{Resource};
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

final class {Resource}Dashboard extends Component
{
    public function __construct(public {Resource} ${resource}) {}

    public function render(): View
    {
        return view('components.admin.member.{resources}.dashboard');
    }
}
```

---

## Modal Names Convention

| Action | Modal Name | Usage |
|--------|------------|-------|
| Delete | `delete-{resource}-{$id}` | Dynamic, one per row in list |

---

## Checklist for a New Resource

Swap `member` → `platform` for the Platform area. Adding a third area follows the same shape.

### Backend
- [ ] Controller: `app/Http/Controllers/Admin/Member/{Resource}Controller.php`
- [ ] Routes: resource block in `routes/admin/member.php`
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
