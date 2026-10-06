# Changelog

All notable changes to this project are documented here.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to
[Semantic Versioning](https://semver.org/).

## [Unreleased]

## [1.1.0] - 2026-10-06

### Added

- **`php forge ai:skills`** — publishes Claude Code Skills (`.claude/skills/{name}/SKILL.md`) encoding Marrow
  best practices as step-by-step procedures: `marrow-module`, `marrow-crud`, `marrow-auth`,
  `marrow-service-integration`, `marrow-ui-component`. Same publish convention as `marrow/warden`/`marrow/anvil`'s
  install commands — plain Markdown, safe to edit afterward, `--only=` to publish a subset, `--force` to
  overwrite, asks before overwriting on a real terminal otherwise. Each skill checks what's actually installed
  (`marrow/warden`, `marrow/ui`, `marrow/form-builder`) rather than assuming every optional package is present.

### Changed

- **Requires `marrow/framework` ^3.0** (was ^2.2).

### Fixed

- **`AGENTS.md`'s "App" section mislabeled the application's own version as "Marrow version"**, the exact same
  bug independently found and fixed in `php forge about` (marrow/framework 2.5.0) — now shows "App version"
  (`config('app.version')`) and "Framework" (`framework_version()`, the real installed `marrow/framework`
  version) as two correctly separated lines.

### Documentation

- Added five new entries to the generated "Marrow conventions & gotchas" section: `RedirectResponse`/
  `JsonResponse` now being genuine `Response` subtypes (3.0), the cross-namespace `{% extends %}` gotcha for a
  module's own layout, the Tailwind v4 `@source` requirement for an installed UI package's templates,
  `Support\ServiceIntegration`/`Http\Webhook\WebhookSignature` for external-service integration, and
  `fakerphp/faker`/`psy/psysh` being `require-dev` since 2.4.1.

## [1.0.1] - 2026-10-05

### Changed

- **`LICENSE` copyright holder corrected to "Aure Dulvresse"** — matches every other Marrow repository
  (previously read "marrow").
- **`composer.json` `authors`** — credits the maintainer, matching every other Marrow repository.

## [1.0.0]

Initial stable release — see the main README for the full feature set at this point; not individually
detailed here.
