---
name: fluxui-development
description: "Use this skill for Flux UI development in Livewire applications only. Trigger when working with <flux:*> components, building or customizing Livewire component UIs, creating forms, modals, tables, or other interactive elements. Covers: flux: components (buttons, inputs, modals, forms, tables, date-pickers, kanban, badges, tooltips, etc.), component composition, Tailwind CSS styling, Heroicons/Lucide icon integration, validation patterns, responsive design, and theming. Do not use for non-Livewire frameworks or non-component styling."
license: MIT
metadata:
  author: laravel
---

# Flux UI Development

## Documentation

Use `search-docs` for detailed Flux UI patterns and documentation.

## Basic Usage

This project uses the free edition of Flux UI, which includes all free components and variants but not Pro components.

Flux UI is a component library for Livewire built with Tailwind CSS. It provides components that are easy to use and customize.

Use Flux UI components when available. Fall back to standard Blade components when no Flux component exists for your needs.

<!-- Basic Button -->
```blade
<flux:button variant="primary">Click me</flux:button>
```

## Available Components (Free Edition)

Available: avatar, badge, brand, breadcrumbs, button, callout, checkbox, dropdown, field, heading, icon, input, modal, navbar, otp-input, profile, radio, select, separator, skeleton, switch, text, textarea, tooltip

## Icons

Flux includes [Heroicons](https://heroicons.com/) as its default icon set. Search for exact icon names on the Heroicons site - do not guess or invent icon names.

<!-- Icon Button -->
```blade
<flux:button icon="arrow-down-tray">Export</flux:button>
```

For icons not available in Heroicons, use [Lucide](https://lucide.dev/). Import the icons you need with the Artisan command:

```bash
vendor/bin/sail artisan flux:icon crown grip-vertical github
```

## Common Patterns

### Form Fields

Always use the explicit `<flux:field>` + `<flux:label>` + `<flux:error>` pattern. Never use the shorthand `:label="..."` prop on `<flux:input>` — it does not expose an error slot and is less flexible.

```blade
<flux:field>
    <flux:label>{{ __('Email') }}</flux:label>
    <flux:input type="email" wire:model="email" />
    <flux:error class="mt-0!" name="email" />
</flux:field>
```

The `class="mt-0!"` on `<flux:error>` removes the default top margin so the error sits flush against the input. Always include it.

### Modals

<!-- Modal -->
```blade
<flux:modal wire:model="showModal">
    <flux:heading>Title</flux:heading>
    <p>Content</p>
</flux:modal>
```

## Verification

1. Check component renders correctly
2. Test interactive states
3. Verify mobile responsiveness

## Button Conventions

All `<flux:button>` instances in admin and app views must have `size="sm"`. Auth views (`livewire/auth/`) are exempt — their full-width buttons intentionally omit size.

`variant="ghost"` is reserved for icon-only dropdown triggers (e.g. the ellipsis `⋯` button). Use `variant="filled"` for all other neutral/secondary actions.

```blade
{{-- correct (admin/app) --}}
<flux:button variant="primary" size="sm">Save</flux:button>
<flux:button variant="filled" size="sm">Cancel</flux:button>
<flux:button variant="danger" size="sm">Delete</flux:button>
<flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" square />  {{-- dropdown trigger only --}}

{{-- correct (auth views only) --}}
<flux:button variant="primary" class="w-full">Log in</flux:button>

{{-- wrong --}}
<flux:button variant="primary">Save</flux:button>            {{-- missing size (non-auth) --}}
<flux:button variant="ghost" size="sm">Cancel</flux:button>  {{-- ghost only for icon dropdown triggers --}}
```

## Common Pitfalls

- Trying to use Pro-only components in the free edition
- Not checking if a Flux component exists before creating custom implementations
- Forgetting to use the `search-docs` tool for component-specific documentation
- Not following existing project patterns for Flux usage
- Using `:label="..."` shorthand on `<flux:input>` — always use the explicit `<flux:field>` wrapper instead
- Omitting `class="mt-0!"` on `<flux:error>` — this causes unwanted spacing between input and error message
- Omitting `size="sm"` on `<flux:button>` in admin/app views — auth views are the only exception
- Using `variant="ghost"` for regular buttons — ghost is only for icon-only dropdown triggers