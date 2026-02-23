# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Hartverwarmers is a Dutch-language Laravel 12 platform for sharing elderly care initiatives. Activity coordinators in care homes share practical elaborations of initiatives, organized around the DIAMANT model — a 7-goal pedagogical framework (Doen, Inclusief, Autonomie, Mensgericht, Anderen, Normalisatie, Talent).

## Commands

```bash
# Development (runs server, queue, logs, vite concurrently)
composer run dev

# Build frontend assets
npm run build

# Run all tests
php artisan test --compact

# Run a specific test file
php artisan test --compact tests/Feature/GoalTest.php

# Run a specific test method
php artisan test --compact --filter=testName

# Format PHP code (run after any PHP changes)
vendor/bin/pint --dirty --format agent

# Fresh database with seeds
php artisan migrate:fresh --seed
```

## Architecture

### Domain Model

The core domain is **Initiatives** (activity ideas) with **Elaborations** (detailed practical write-ups by contributors). Each elaboration can have **Files**, **Comments**, and **Likes/Bookmarks** (both polymorphic). A multi-type **Tag** system (morph-to-many) classifies both initiatives and elaborations by interest, theme, season, target group, dimension, guidance, and goal.

**Users** belong to **Organisations** (care homes). User roles: `admin`, `curator`, `contributor`.

### DIAMANT Model

The 7 goals are config-driven in `config/diamant.php` (not database-stored). `DiamantService` provides access. Goals link to initiatives via tags with type `goal` and slug pattern `doel-{facetSlug}`.

### Content System

Static/semi-static content (tools, workshops, video lessons, roadmap) is stored as JSON files on the `content` disk (`resources/content/`) and loaded via `JsonContent` service. This includes tools, workshops, and lesson series.

### Key Services

- `App\Services\DiamantService` — DIAMANT facet lookups from config
- `App\Services\JsonContent` — JSON content loader from the `content` storage disk

### View Composers

- `FooterComposer` — injects stats (elaboration count, contributor count, organisation count) into `components.layout`, cached for 1 hour

### Routes

All routes use Dutch URL slugs: `/initiatieven`, `/doelen`, `/bijdragers`, `/themas`, `/uitwerkingen`, `/profiel`. Route names are English: `initiatives.index`, `goals.show`, `contributors.index`, etc.

### CSS & Tailwind

Custom design tokens are defined as CSS custom properties in `resources/css/app.css` (colors, typography, spacing). The `@theme` block in the CSS customizes Flux UI accent colors. **Do not add spacing keys like `xl`, `2xl`, `3xl`, `4xl` to `tailwind.config.js` `extend.spacing`** — they conflict with Tailwind v4's `max-w-*` resolution.

Component classes (`.cta-link`, `.btn-pill`, `.section-label`, `.content-card`, `.diamant-badge`, etc.) are defined in `@layer components` in `app.css`.
