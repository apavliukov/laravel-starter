# Admin Blade Rules

Patterns for resource layouts, page views, and static View Components in admin areas.

---

## Resource Layout

The resource layout wraps every page of a resource — it renders the heading, action buttons, status badges, and tab navigation. One layout component per resource.

```
resources/views/components/admin/member/{resources}/layout.blade.php
```

```blade
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

## View Components (static widgets)

Reserve View Components for non-interactive bits — dashboard stats cards, sidebars, static summaries. Anything needing pagination, search, row actions, or wire-model binding should be Livewire instead.

```
app/View/Components/Admin/Member/{Resources}/{Resource}Dashboard.php
resources/views/components/admin/member/{resources}/dashboard.blade.php
```

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
