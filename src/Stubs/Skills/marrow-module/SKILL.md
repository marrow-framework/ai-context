---
name: marrow-module
description: Scaffold a new HMVC module in this Marrow app the conventional way — directory structure, #[Module] attribute, route registration, and enabling it. Use when asked to create a new module, feature area, or "bounded context" (e.g. "add a Blog module", "create a Billing module").
---

# Marrow module

A Marrow app is organized into HMVC modules under `modules/{Name}/` — there
is no global `routes/web.php` or app-wide controller directory that routes
to anything. Every route lives in some module's own `routes.php`.

## Steps

1. **Generate it**: `php forge make:module {Name}` — writes
   `modules/{Name}/{Name}Module.php`, `routes.php`, and the `Controllers/`,
   `Models/`, `Views/`, `Database/Migrations/` directories.

2. **Check the generated `#[Module(...)]` attribute** on `{Name}Module`:
   ```php
   #[Module(
       name: '{name}',
       imports: [],      // other module classes this one depends on
       providers: [],    // services this module binds into the container
       exports: [],      // which providers other modules may resolve
       commands: [],     // console commands this module registers
       listeners: [],    // [EventClass::class => [ListenerClass::class, ...]]
   )]
   ```
   Only list a service in `providers` if you actually bind something for it
   in `register()` — an empty `providers: []` with nothing bound is fine
   and common. A provider is only reachable from *other* modules if it's
   also in `exports`.

3. **Enable it** — unless it's shipped inside a Composer package with
   `"extra": {"marrow": {"modules": [...]}}}` (auto-discovered, no action
   needed), add it to `config/modules.php`:
   ```php
   'enabled' => [
       // ...
       \Modules\{Name}\{Name}Module::class,
   ],
   ```

4. **Add routes** in `modules/{Name}/routes.php` — `$router` is already in
   scope (provided by `BaseModule::loadRoutes()`), no `use` import needed
   for it:
   ```php
   use Modules\{Name}\Controllers\{Name}Controller;

   $router->get('/{route}', [{Name}Controller::class, 'index'])->name('{name}.index');
   ```
   Prefer the `#[Route(...)]` attribute directly on a controller method
   instead, if the app already uses that style elsewhere — register it from
   `routes.php` either way via `$router->controller({Name}Controller::class)`.

5. **A controller generated without `--module=` lands in `app/Controllers/`
   and nothing will ever route to it.** Always pass `--module={Name}` to
   `make:controller`/`make:model`/`make:migration`/etc. when the file
   belongs to this module, not the app's own top-level directories.

## Verify

- `php forge route:list` (or `--json`) shows the new routes.
- `php forge module:graph` (if available) shows the module's
  imports/exports resolved correctly, with no cycle.
- If this module is meant to be reusable across apps, ship it as its own
  Composer package instead of a local `modules/` directory — a module's
  `Views`/routes/migrations resolve relative to wherever its own class
  file physically lives (`BaseModule::path()`), so this works identically
  either way with no code changes.
