# Hartverwarmers

A Dutch-language platform for sharing elderly care initiatives, built for activity coordinators in Flemish residential care homes (woonzorgcentra). Coordinators publish practical "fiches" — concrete, reusable activity write-ups — organised around the **DIAMANT model**, a 7-goal pedagogical framework for meaningful daily life in care:

**D**oen · **I**nclusief · **A**utonomie · **M**ensgericht · **A**nderen · **N**ormalisatie · **T**alent

The site is in production at [hartverwarmers.be](https://hartverwarmers.be).

## Stack

- **Laravel 13** (PHP 8.4) — application framework
- **Livewire 4** + **Flux UI 2 (Pro)** — server-driven reactive UI
- **Tailwind CSS 4** — styling
- **SQLite** — primary database
- **PHPUnit 12** — testing
- **Laravel Pennant** — feature flags
- **Sentry** — error tracking

## Local setup

```bash
# Install PHP and JS dependencies
composer install
npm install

# Configure environment
cp .env.example .env
php artisan key:generate

# Database
php artisan migrate --seed

# Run dev server (server, queue, logs, vite — concurrent)
composer run dev
```

The app is served at the URL set by `APP_URL` in your `.env` (Laravel Herd users: `https://hartverwarmers.test`).

> **Flux Pro license required.** Installing dependencies needs valid Flux Pro credentials in `auth.json` or via `composer config http-basic.composer.fluxui.dev`. See [fluxui.dev](https://fluxui.dev/pricing).

## Tests

```bash
# All tests (parallel — sequential OOMs at ~500 tests)
php artisan test --parallel --compact

# Single file
php artisan test --compact tests/Feature/GoalTest.php

# Filter
php artisan test --compact --filter=testName
```

## Code style

```bash
vendor/bin/pint --dirty --format agent
```

## Architecture overview

- **Initiatives** — high-level activity ideas
- **Fiches** (elaborations) — detailed practical write-ups attached to an initiative, with files, comments, likes, and bookmarks
- **Tags** — polymorphic, multi-typed (theme, season, target group, dimension, guidance, goal)
- **Users** belong to **Organisations** (care homes), with roles `admin`, `curator`, `contributor`
- The 7 DIAMANT goals are config-driven (`config/diamant.php`), not stored in the database

Static content (tools, workshops, lesson series, roadmap) lives as JSON on the `content` disk under `resources/content/` and is loaded via `App\Services\JsonContent`.

See [`CLAUDE.md`](CLAUDE.md) for in-depth project conventions, design tokens, and contributor guidance.

## License

[MIT](LICENSE) — © 2026 Frederik Vincx.

The Hartverwarmers name, logo, and content (initiatives, fiches, photography) are not covered by the MIT license and remain the property of their respective authors.
