# Livewire 4 & Flux UI

## Livewire Core

- Use `search-docs` for exact version-specific Livewire documentation.
- Create components: `vendor/bin/sail artisan make:livewire [Posts\CreatePost] --class`
- State should live on the server, with the UI reflecting it.
- All Livewire requests hit the Laravel backend. Always validate form data and run authorization checks in Livewire actions.

## Project Convention: Class-Based Components

This codebase uses **class-based components** (the v3-style format):

- Component class: `app/Livewire/{Area}/{Resource}/{Name}.php`
- View file: `resources/views/livewire/{area}/{resource}/{name}.blade.php`

Do **not** introduce single-file (SFC, `⚡`-prefixed) or multi-file (MFC) components unless explicitly approved — it would fragment the codebase. When creating new components, always pass `--class` to `make:livewire`.

## Livewire Conventions (v3 + v4 shared)

- `wire:model` is deferred by default. Use `wire:model.live` for real-time updates.
- Components use the `App\Livewire` namespace (not `App\Http\Livewire`).
- Use `$this->dispatch()` to dispatch events (not `emit` or `dispatchBrowserEvent`).
- Use the `components.layouts.app` view as the typical layout path (not `layouts.app`).

## Livewire 4 Changes That Affect Existing Code

- **`wire:model` now ignores child events by default.** Use `wire:model.deep` to restore the v3 behavior when binding to Alpine-modified values or deeply nested inputs.
- **`wire:scroll` renamed to `wire:navigate:scroll`.** Update any existing usages.
- **Component tags must be properly closed.** Self-closing (`<livewire:foo />`) or matched closing (`<livewire:foo>...</livewire:foo>`) — unclosed tags now error.
- **`wire:transition` uses the browser's View Transitions API.** Transition modifiers from v3 have been removed; if you relied on them, rework with CSS view transitions.
- **JS hooks renamed:** `commit`/`request` hooks → `interceptMessage()`/`interceptRequest()`. `$wire.$js('name', fn)` → `$wire.$js.name = fn`.
- **Full-page routing:** use `Route::livewire('/path', ComponentClass::class)` for full-page components. Config keys renamed: `layout` → `component_layout`, `lazy_placeholder` → `component_placeholder`.

## Livewire 4 Features Available (opt-in)

Not currently used in this codebase, but available if the need arises:

- **Islands** (`@island(name: 'stats')`) — isolated update regions for partial re-renders.
- **Async actions** (`wire:click.async` or `#[Async]` attribute) — non-blocking parallel execution.
- **Deferred loading** (`defer` attribute) — load components after page render.
- **Bundled lazy loading** (`lazy.bundle`) — load multiple components together.
- **New directives**: `wire:sort` (drag-and-drop), `wire:intersect` (viewport detection), `wire:ref` (element references), `.renderless`, `.preserve-scroll`.
- **Alpine is bundled in Livewire 4** — do not import Alpine separately.

Discuss with the user before adopting any of these — they change the architecture of how data loads.

## Best Practices

- Components require a single root element.
- Use `wire:loading` and `wire:dirty` for loading states.
- Add `wire:key` in loops:
  ```blade
  @foreach ($items as $item)
      <div wire:key="item-{{ $item->id }}">
          {{ $item->name }}
      </div>
  @endforeach
  ```
- Prefer lifecycle hooks for initialization and reactive side effects:
  ```php
  public function mount(User $user) { $this->user = $user; }
  public function updatedSearch() { $this->resetPage(); }
  ```

## Directives

- Available: `wire:show`, `wire:transition`, `wire:cloak`, `wire:offline`, `wire:target`. Use documentation for usage examples.

## Blade Template Rules — No Heavy Logic in Views

**Strictly forbidden in any blade template** (pages, Livewire views, x-components, partials):

- **Eloquent queries**: `Model::query()`, `Model::where(...)`, `Model::all()`, `Model::find(...)`, `Model::first(...)`, `->get()`, `->paginate()`, `->pluck()`, `->count()` triggering a DB call
- **Service container lookups**: `app(SomeClass::class)`, `resolve(...)` — resolve these in the backend and pass data as props/computed properties
- **Business logic**: non-trivial calculations, conditionals wrapping queries, data transformation beyond simple presentation

**Where data must come from:**

- **Livewire views** → `#[Computed]` properties on the component (or ordinary public properties)
- **Static/read-only widgets** → class-based Blade view component (`App\View\Components\...`) with logic in the constructor / `render()`
- **Shared lookups** (e.g. dropdown options, filter lists used across components) → put into a trait/concern with a `#[Computed]` property

**What's allowed in `@php` blocks:**

- Variable aliasing for readability: `@php $language = $this->localesByKey[$locale] ?? null; @endphp`
- Presentation-only operations on already-loaded data: array access, null coalescing, string formatting
- Laravel Collection operations on in-memory collections (`->where()`, `->filter()`, `->map()`, `->first()`) when operating on a plain array/collection, **not** an Eloquent query builder

**Why**: views re-render on every Livewire round trip. Inline queries cause silent N+1, bypass eager-loading, and hide data dependencies from tests. Moving them to the backend makes query counts auditable and data sources obvious.

## Controller Rules — Thin Controllers, No View Data Queries

**View-rendering controllers (show/index/edit) must be thin.** Their job is routing + authorization, not data loading.

**Forbidden in view-rendering controllers:**

- **Eloquent queries** to build data for the view (`Model::query()->...->get()`, `->with(...)`, `->whereHas(...)`, etc.)
- **Data transformations**, counting, aggregating for display
- **Fetching related data** that a view component can fetch itself

**Allowed in view-rendering controllers:**

- Route-model binding (Laravel resolves the model, not you)
- Authorization (`$this->authorize(...)`)
- Trivial eager-loading for a single route-bound model: `$model->loadCount('rel')` or `$model->load('rel')` — a one-liner is fine, more than that belongs in a component
- Returning the view with the route-bound model

**Where display data must come from:**

- **Complex reads** (multi-step queries, eager-loads, cross-scope work) → **Livewire component** if interactive, otherwise **class-based Blade view component** (`App\View\Components\...`)
- **Scoped widgets** (stats cards, badge counters, dashboards, sidebars) → class-based view components that pull their own data in the constructor
- **Filtered/paginated lists** → Livewire component

**Action controllers** (POST/PATCH/DELETE, redirect-returning) are exempt — they process commands and may query what they need. Prefer extracting into services when logic grows.

**Example — bad:**
```php
public function show(Post $post): View
{
    $comments = $post->comments()->with([...])->latest()->get();
    $stats = /* aggregate queries */;

    return view('...', compact('post', 'comments', 'stats'));
}
```

**Example — good:**
```php
public function show(Post $post): View
{
    $post->loadCount('comments'); // one-liner OK for the page layout badge

    return view('...', ['post' => $post]);
}
```
Then the view uses `<x-admin.member.posts.comments-list :$post />` and `<x-admin.member.posts.stats :$post />` — each class-based component loads its own data.

## Alpine.js

- Alpine is included with Livewire - do not manually include Alpine.js.
- Included plugins: persist, intersect, collapse, and focus.
- **No inline Alpine.js**: Never write Alpine logic inline in blade templates. Always extract Alpine components into dedicated JS files under `resources/js/` and register them via `initAlpineComponents()` in the appropriate module (e.g., `resources/js/web/modules/web.js`). Blade templates should only reference components by name: `x-data="componentName(args)"`. Pass server-side values as arguments to the component function.

## JS Lifecycle Hooks

Livewire 4 uses `interceptRequest` (HTTP-level) and `interceptMessage` (component-level). The deprecated v3 `Livewire.hook('request' | 'commit' | 'message.failed', ...)` API still works but should not be used in new code.

```js
document.addEventListener('livewire:init', function () {
    Livewire.interceptRequest(({ onError, onFailure }) => {
        onError(({ response }) => {
            if (response.status === 419) {
                alert('Your session expired');
            }
        });

        onFailure(({ error }) => {
            console.error(error);
        });
    });
});
```

## Flux UI Free

- This project uses the free edition of Flux UI, a component library for Livewire built with Tailwind CSS.
- Use Flux components when available, fallback to standard Blade components.
- Use `search-docs` for Flux documentation.
- Usage: `<flux:button variant="primary"/>`
- Available components: avatar, badge, brand, breadcrumbs, button, callout, checkbox, dropdown, field, heading, icon, input, modal, navbar, otp-input, profile, radio, select, separator, skeleton, switch, text, textarea, tooltip

## Testing Livewire

```php
Livewire::test(Counter::class)
    ->assertSet('count', 0)
    ->call('increment')
    ->assertSet('count', 1)
    ->assertSee(1)
    ->assertStatus(200);
```

Testing a component exists on a page:
```php
$this->get('/posts/create')
    ->assertSeeLivewire(CreatePost::class);
```