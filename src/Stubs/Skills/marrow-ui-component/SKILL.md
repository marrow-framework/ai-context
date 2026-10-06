---
name: marrow-ui-component
description: Use, customize, or create a view component in this Marrow app — covers marrow/ui's component library (if installed) and the framework's own Component/ComponentRegistry primitives either way. Use when asked to build a UI element, use a button/card/form component, restyle a component, or create a new reusable component.
---

# Marrow view components

## Three equivalent ways to call a component

```twig
{{ component('button', {variant: 'primary', slot: 'Save'}) }}

{% component 'alert' with {type: 'success'} %}{{ message }}{% endcomponent %}

<mui-button variant="primary">Save</mui-button>
```
All three compile to the same thing — `<mui-x>` (only if `marrow/ui` is
installed) is rewritten to one of the other two forms before Twig's lexer
ever sees it. Pick whichever reads best for the component at hand: the
HTML-like form usually reads better for anything with a body (`<mui-card>`,
`<mui-form>`); the function-call form is simplest for a component with no
slot content (`<mui-checkbox name="..." />`, self-closing).

`<mui-x>` attribute rules: `attr="value"` → plain string;
`attr="{{ expr }}"` → unwrapped expression (keeps real type — bool/array/
number, not a stringified copy); `:attr="expr"` → Vue/Alpine-style bind,
already an expression, no `{{ }}` needed; a bare `attr` with no value →
`true`. `kebab-case` attribute names become `camelCase` props.

## Every component accepts `class` and `attrs`

(marrow/ui >= 1.1) — universal, not per-component:
- `class` — merged **additively** with the component's own base Tailwind
  classes, never replacing them.
- `attrs` — arbitrary HTML attribute passthrough (`style`, `data-*`,
  `aria-*`, or anything with no dedicated prop): `true` → bare boolean
  attribute, `false`/`null` → omitted, anything else → `name="value"`.
```twig
<mui-input name="code" class="tracking-widest" :attrs="{inputmode: 'numeric', autocomplete: 'one-time-code'}" />
```

## Restyling a component globally

Don't edit the component's own file inside `vendor/marrow/ui/` — override
the registration instead, from your own module's `boot()`:
```php
$this->container->make(\Marrow\Template\ComponentRegistry::class)
    ->registerAs('button', \Modules\Theme\Components\MyButtonComponent::class);
```
Every `{{ component('button', ...) }}`/`<mui-button>` call in the app now
resolves to your class instead.

## Creating a brand-new component (with or without marrow/ui)

```bash
php forge make:component Spinner --module={Module}
```
Writes a `Marrow\Template\Component` subclass and its Twig template.
Register it (if not auto-discovered already) via `ComponentRegistry::
register()` from the owning module's `boot()`. A component's public
properties are its props; `slot` is the conventional name for pre-rendered
body content (not auto-escaped — build it with `{% set %}...{% endset %}`,
never feed it raw user input directly).

## `marrow/ui` + Tailwind — the one gotcha that looks like "the design is
just plain"

If a `marrow/ui` component renders with **no styling at all** (default
browser checkbox, no button background, no border on an input) after
`composer require marrow/ui && php forge ui:install`, it's almost always
this: Tailwind v4's automatic content scan skips anything `.gitignore`
excludes, which includes `/vendor/` in every Marrow app — so a utility
class that appears *only* inside `marrow/ui`'s own templates is never
generated. `ui:install` adds the fix
(`@source "../../vendor/marrow/ui/src/Views";` in `resources/css/app.css`)
automatically; if it looks unstyled anyway, check that line is actually
present and re-run `ui:install` if not (safe — already-wired sections are
skipped).

## Verify

- The component renders with its full intended styling in an actual
  browser, not just "no PHP error" — a missing Tailwind `@source` produces
  zero errors anywhere, only a plain, unstyled element.
- A `class`/`attrs` override actually appears in the rendered HTML
  (inspect the output), not just assumed from reading the component's
  source.
