<div align="center">

<img src="https://raw.githubusercontent.com/marrow-framework/.github/main/marrow-logo-mark.svg" alt="Marrow" width="120">

# Marrow AI Context

Generates `AGENTS.md` (a live, accurate map of a [Marrow](https://github.com/marrow-framework/core) app) and publishes Claude Code Skills encoding Marrow best practices — for AI coding agents and human contributors.

[![CI](https://img.shields.io/github/actions/workflow/status/marrow-framework/ai-context/ci.yml?branch=main&style=flat-square&label=CI)](https://github.com/marrow-framework/ai-context/actions/workflows/ci.yml)
[![Packagist Version](https://img.shields.io/packagist/v/marrow/ai-context?style=flat-square&label=packagist)](https://packagist.org/packages/marrow/ai-context)
[![Packagist Downloads](https://img.shields.io/packagist/dt/marrow/ai-context?style=flat-square&color=blue)](https://packagist.org/packages/marrow/ai-context)
[![PHP 8.2+](https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=flat-square&logo=php&logoColor=white)](https://php.net)
[![License MIT](https://img.shields.io/badge/license-MIT-22c55e?style=flat-square)](LICENSE)

</div>

---

Generates `AGENTS.md` — a live, accurate map of an
[Marrow](https://github.com/marrow-framework/core) app (modules,
routes, config) plus Marrow-specific conventions and gotchas — for AI
coding agents (Claude Code, Cursor, Copilot, ...) and human contributors to
read before exploring the codebase.

```bash
composer require --dev marrow/ai-context
php forge ai:context
php forge ai:skills
```

Both commands are available immediately after `composer require` — no
manual registration step, no config file. The package declares its own
module via `extra.marrow.modules` in its own `composer.json`, picked up
automatically by Marrow's package auto-discovery at boot.

## Why

An agent (or a new contributor) exploring an unfamiliar codebase burns a
lot of turns re-discovering things a maintainer already knows: which
routes exist, which modules are actually active (not just the ones listed
in `config/modules.php` — package auto-discovery adds more), where the
framework's own documentation actually lives once it's a `vendor/`
dependency rather than a monorepo checkout, and a handful of
Marrow-specific behaviors that aren't obvious from reading any single
file in isolation. `AGENTS.md` front-loads all of that.

## What it generates

```bash
php forge ai:context                        # writes ./AGENTS.md
php forge ai:context --path=docs/AGENTS.md  # custom location
```

Re-run it after adding/removing a route or module — it always overwrites
the whole file, so the live sections never drift from reality. The static
conventions section doesn't change between runs; only the introspected
data does.

`AGENTS.md` has five sections:

1. **App** — name, app version (`config('app.version')`), the *framework's*
   own installed version (`framework_version()`, resolved from Composer —
   correctly separate from the app's own version), environment, PHP
   version.
2. **Enabled modules** — every module actually registered right now,
   including ones no `config/modules.php` entry mentions (auto-discovered
   from an installed package), with their imports/exports/commands.
3. **Routes** — method, URI, name, controller action, and middleware for
   every registered route (same data as `php forge route:list --json`).
4. **Config files present** — every file under `config/`.
5. **Framework documentation** — this package locates the installed
   `marrow/framework` package's own `docs/` directory (by
   reflecting on a framework class's file location — works whether it's a
   real Composer install or a symlinked path repository) and reads every
   `.md` file in it, extracting each one's title and first paragraph into
   an index with clickable paths. This is read from whatever documentation
   actually ships with the installed framework version, not a bundled copy
   that could drift out of sync with it.
6. **Marrow conventions & gotchas** — a hand-maintained list of real,
   previously-undiscovered behaviors (routing being module-only, middleware
   colon-parameters always arriving as strings, `Auditable` needing manual
   wiring, and others) that otherwise cost real debugging time to find —
   its doc cross-references also resolve to the actual installed path.

If the framework's `docs/` directory can't be located (an install that
excludes docs from the package, for instance), section 5 is skipped and a
warning is printed — the rest of the file is still written normally.

## `ai:skills` — Claude Code Skills for Marrow best practices

`AGENTS.md` is prose an agent reads once, up front. A
[Skill](https://docs.claude.com/en/docs/claude-code/skills) is a
step-by-step procedure Claude Code loads *only when relevant* — a
sharper tool for "how do I actually build this the Marrow way" than
another paragraph of context.

```bash
php forge ai:skills                              # publish all five
php forge ai:skills --only=marrow-crud,marrow-auth  # a subset
php forge ai:skills --force                        # overwrite already-published skills
```

Writes to `.claude/skills/{name}/SKILL.md` — plain Markdown, safe to edit
afterward (re-running never touches an already-published skill unless you
pass `--force`; on a real terminal it asks first instead of silently
skipping). Five ship today:

| Skill | Covers |
|---|---|
| `marrow-module` | Scaffolding a new HMVC module — directory structure, `#[Module(...)]`, registration |
| `marrow-crud` | A full CRUD resource — migration, model attributes, declarative form, controller, routes |
| `marrow-auth` | Login/route-protection/RBAC — `marrow/warden` if installed, the raw `Auth\*` primitives otherwise |
| `marrow-service-integration` | Integrating a payment provider/external API via `ServiceIntegration`/`WebhookSignature` instead of a hand-rolled client |
| `marrow-ui-component` | Using, customizing, or creating a view component — `marrow/ui`'s three call styles and the Tailwind `@source` gotcha, or the framework's own `Component` primitives either way |

Each one is self-aware of what's actually installed (checks for
`marrow/warden`/`marrow/ui`/`marrow/form-builder` before assuming they're
there) rather than assuming every optional package is present.

## Requirements

PHP 8.2+, `marrow/framework`.

## License

MIT — see [LICENSE](LICENSE).

---

<div align="center">

Made with ❤️ by [Aure Dulvresse](https://github.com/AureDulvresse)

</div>
