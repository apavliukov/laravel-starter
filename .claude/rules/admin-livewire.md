# Admin Livewire Component Rules

Patterns for Livewire components in admin areas. All write operations use Actions; all list queries use Query classes. See `admin-business-logic.md` for those patterns.

---

## List Component

```php
<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Member\{Resources};

use App\Dto\{Resources}\List{Resources}Filters;
use App\Queries\{Resources}\List{Resources}Query;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

final class {Resource}List extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url(except: 'created_at')]
    public string $sort = 'created_at';

    #[Url(except: 'desc')]
    public string $direction = 'desc';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $column): void
    {
        if ($this->sort === $column) {
            $this->direction = $this->direction === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sort = $column;
            $this->direction = 'desc';
        }

        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->sort = 'created_at';
        $this->direction = 'desc';
        $this->resetPage();
    }

    #[Computed]
    public function {resources}(): LengthAwarePaginator
    {
        return new List{Resources}Query()->handle(new List{Resources}Filters(
            search: $this->search,
            sort: $this->sort,
            direction: $this->direction,
        ));
    }

    public function render(): View
    {
        return view('livewire.admin.member.{resources}.{resource}-list');
    }
}
```

---

## Create Component

```php
<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Member\{Resources};

use App\Actions\{Resources}\Create{Resource} as Create{Resource}Action;
use App\Enums\Policies\Abilities\Ability;
use App\Livewire\Admin\Member\{Resources}\Forms\{Resource}Form;
use App\Models\{Resource};
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

final class Create{Resource} extends Component
{
    public {Resource}Form $form;

    public function store(Create{Resource}Action $action): void
    {
        Gate::authorize(Ability::CREATE, {Resource}::class);

        $this->form->validate();

        $resource = $action($this->form->toCreateInput());

        session()?->flash('toast', [
            'message' => __('{Resource} created successfully'),
            'variant' => 'success',
        ]);

        $this->redirect(route('admin.member.{resources}.edit', $resource), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.admin.member.{resources}.create-{resource}');
    }
}
```

---

## Edit Component

```php
<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Member\{Resources};

use App\Actions\{Resources}\Update{Resource} as Update{Resource}Action;
use App\Enums\Policies\Abilities\Ability;
use App\Livewire\Admin\Member\{Resources}\Forms\{Resource}Form;
use App\Models\{Resource};
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

final class Edit{Resource} extends Component
{
    public {Resource} ${resource};
    public {Resource}Form $form;

    public function mount({Resource} ${resource}): void
    {
        $this->{resource} = ${resource};
        $this->form->set{Resource}(${resource});
    }

    public function update(Update{Resource}Action $action): void
    {
        Gate::authorize(Ability::UPDATE, $this->{resource});

        $this->form->validate();

        $action($this->{resource}, $this->form->toUpdateInput());

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

---

## Delete Component (Modal)

```php
<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Member\{Resources};

use App\Enums\Policies\Abilities\Ability;
use App\Models\{Resource};
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
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
        Gate::authorize(Ability::DELETE, $this->{resource});

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

### List view

```blade
{{-- livewire/admin/member/{resources}/{resource}-list.blade.php --}}
<div>
    {{-- search / filter controls --}}
    <flux:input wire:model.live="search" placeholder="{{ __('Search...') }}" />

    {{-- table rows --}}
    @foreach ($this->{resources} as ${resource})
        <div wire:key="{{ ${resource}->id }}">
            {{ ${resource}->name }}
        </div>
    @endforeach

    {{ $this->{resources}->links() }}
</div>
```

### Create view

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

### Edit view

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

Form fields bind to `form.*` because validation and state live in the Form Object.

```blade
{{-- livewire/admin/member/{resources}/partials/form.blade.php --}}
<div class="space-y-6">
    <flux:field>
        <flux:label>{{ __('Name') }}</flux:label>
        <flux:input wire:model="form.name" placeholder="{{ __('Enter name...') }}" />
        <flux:error name="form.name" />
    </flux:field>

    <flux:field>
        <flux:label>{{ __('Description') }}</flux:label>
        <flux:textarea wire:model="form.description" rows="4"
            placeholder="{{ __('Enter description...') }}" />
        <flux:error name="form.description" />
    </flux:field>
</div>
```

### Delete modal view

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
