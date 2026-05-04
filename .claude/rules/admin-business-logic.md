# Admin Business Logic Rules

Write operations go through **Actions**, read operations go through **Queries**. Neither services nor repositories are used — do not introduce them.

---

## Actions

Actions are `final readonly` classes with a single `__invoke()` method. They accept a typed DTO, wrap side-effects in `DB::transaction()` when needed, and return the resulting model.

```
app/Actions/{Resources}/
├── Create{Resource}.php
├── Update{Resource}.php
└── Delete{Resource}.php
```

```php
<?php

declare(strict_types=1);

namespace App\Actions\{Resources};

use App\Dto\{Resources}\Create{Resource}Input;
use App\Models\{Resource};
use Illuminate\Support\Facades\DB;

final readonly class Create{Resource}
{
    public function __invoke(Create{Resource}Input $input): {Resource}
    {
        return DB::transaction(function () use ($input): {Resource} {
            return {Resource}::query()->create([
                'name' => $input->name,
            ]);
        });
    }
}
```

In Livewire, inject actions via **method injection** — not constructor injection:

```php
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
```

---

## Queries

Queries are `final readonly` classes with a `handle(FiltersDTO)` method returning data. The data source can be Eloquent, an external HTTP API, or anything else — the Query's job is always the same: fetch and return typed data, no side effects.

```
app/Queries/{Resources}/
└── List{Resources}Query.php
```

### Eloquent query

```php
<?php

declare(strict_types=1);

namespace App\Queries\{Resources};

use App\Dto\{Resources}\List{Resources}Filters;
use App\Models\{Resource};
use Illuminate\Pagination\LengthAwarePaginator;

final readonly class List{Resources}Query
{
    /** @return LengthAwarePaginator<int, {Resource}> */
    public function handle(List{Resources}Filters $filters): LengthAwarePaginator
    {
        return {Resource}::query()
            ->when($filters->search, fn ($q) => $q->where('name', 'ilike', "%{$filters->search}%"))
            ->orderBy($filters->sort, $filters->direction)
            ->paginate($filters->perPage);
    }
}
```

### HTTP API query

When data comes from an external API, the query injects an HTTP client and delegates transport to it. The query itself only decides what to ask for and what to return — it never builds raw HTTP requests.

```php
<?php

declare(strict_types=1);

namespace App\Queries\{Resources};

use App\Dto\{Resources}\List{Resources}Filters;
use App\Http\Clients\{Service}Client;
use App\Data\{Resources}\{Resource}Data;

final readonly class List{Resources}Query
{
    public function __construct(private {Service}Client $client) {}

    /** @return array<int, {Resource}Data> */
    public function handle(List{Resources}Filters $filters): array
    {
        return $this->client->list{Resources}(
            limit: $filters->perPage,
            cursor: $filters->cursor,
        );
    }
}
```

The HTTP client lives in `app/Http/Clients/` and is responsible for transport only: base URL, authentication, request building, response parsing, and throwing on HTTP errors. It is bound in a service provider with a pre-configured `Http::withToken(...)->baseUrl(...)` instance.

```php
// app/Http/Clients/{Service}Client.php
final readonly class {Service}Client
{
    public function __construct(private PendingRequest $http) {}

    /** @return array<int, {Resource}Data> */
    public function list{Resources}(int $limit, ?string $cursor = null): array
    {
        $response = $this->http->get('/{resources}', array_filter([
            'limit' => $limit,
            'starting_after' => $cursor,
        ]))->throw();

        return array_map(
            fn (array $item) => {Resource}Data::fromArray($item),
            $response->json('data'),
        );
    }
}
```

### Using queries in Livewire components

`#[Computed]` properties do not support method injection. The right approach depends on whether the query has constructor dependencies:

**No constructor dependencies (pure Eloquent)** — instantiate directly:

```php
#[Computed]
public function {resources}(): LengthAwarePaginator
{
    return new List{Resources}Query()->handle(new List{Resources}Filters(
        search: $this->search,
        sort: $this->sort,
        direction: $this->direction,
    ));
}
```

**Has constructor dependencies (e.g. HTTP client)** — inject via `boot()`, which does support method injection and runs on every request before rendering. Store in a `private` property so Livewire does not try to serialize it:

```php
private List{Resources}Query $query;

public function boot(List{Resources}Query $query): void
{
    $this->query = $query;
}

#[Computed]
public function {resources}(): array
{
    return $this->query->handle(new List{Resources}Filters(
        search: $this->search,
    ));
}
```

---

## DTOs

All DTOs are `final readonly` classes with constructor-promoted properties.

- **Input DTOs** (`Create{Resource}Input`, `Update{Resource}Input`) — expose a `fromArray()` static factory for cases where data comes from a raw array rather than a form object.
- **Filter DTOs** (`List{Resources}Filters`) — carry defaults for every field so callers only pass what differs.

```
app/Dto/{Resources}/
├── Create{Resource}Input.php
├── Update{Resource}Input.php
└── List{Resources}Filters.php
```

```php
// Create{Resource}Input.php
final readonly class Create{Resource}Input
{
    public function __construct(
        public string $name,
        public string $description,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            description: $data['description'],
        );
    }
}

// Update{Resource}Input.php — same shape, nullable fields where appropriate
final readonly class Update{Resource}Input
{
    public function __construct(
        public string $name,
        public ?string $description,
    ) {}
}

// List{Resources}Filters.php
final readonly class List{Resources}Filters
{
    public function __construct(
        public string $search = '',
        public string $sort = 'created_at',
        public string $direction = 'desc',
        public int $perPage = 15,
    ) {}
}
```

---

## Livewire Form Objects

For create/edit forms, extract validation and DTO conversion into a `Livewire\Form` subclass. Place it alongside the components that use it.

```
app/Livewire/Admin/Member/{Resources}/Forms/
└── {Resource}Form.php
```

```php
<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Member\{Resources}\Forms;

use App\Dto\{Resources}\Create{Resource}Input;
use App\Dto\{Resources}\Update{Resource}Input;
use App\Models\{Resource};
use Livewire\Form;

final class {Resource}Form extends Form
{
    public string $name = '';
    public string $description = '';

    public function set{Resource}({Resource} ${resource}): void
    {
        $this->name = ${resource}->name ?? '';
        $this->description = ${resource}->description ?? '';
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function toCreateInput(): Create{Resource}Input
    {
        return new Create{Resource}Input(
            name: $this->name,
            description: $this->description,
        );
    }

    public function toUpdateInput(): Update{Resource}Input
    {
        return new Update{Resource}Input(
            name: $this->name,
            description: $this->description,
        );
    }
}
```

Use the form object in the component:

```php
public {Resource}Form $form;

public function mount({Resource} ${resource}): void
{
    $this->{resource} = ${resource};
    $this->form->set{Resource}(${resource});
}
```
