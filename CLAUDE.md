# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Hartverwarmers is a Dutch-language Laravel 12 platform for sharing elderly care initiatives. Activity coordinators in care homes share practical elaborations of initiatives, organized around the DIAMANT model — a 7-goal pedagogical framework (Doen, Inclusief, Autonomie, Mensgericht, Anderen, Normalisatie, Talent).

## Database Safety

**NEVER run `migrate:fresh`, `migrate:reset`, `db:wipe`, or any other destructive database command.** The local database contains hand-curated content (initiatives, fiches, users, comments, kudos) that is NOT fully reproducible from seeders. There is no backup. Losing this data means hours of manual re-entry.

- To test migrations: run `php artisan migrate` (forward only) — never `migrate:fresh`
- To verify a migration works from scratch: use the **test suite** (which uses an isolated test database with `RefreshDatabase`)
- If you need to rollback a migration you just created: `php artisan migrate:rollback --step=1`

## Commands

```bash
# Development (runs server, queue, logs, vite concurrently)
composer run dev

# Build frontend assets
npm run build

# Run all tests (parallel required — sequential OOMs at ~500 tests)
php artisan test --parallel --compact

# Run a specific test file
php artisan test --compact tests/Feature/GoalTest.php

# Run a specific test method
php artisan test --compact --filter=testName

# Format PHP code (run after any PHP changes)
vendor/bin/pint --dirty --format agent

# Fresh database with seeds
php artisan migrate:fresh --seed
```

## Deployment & Migrations

**Before committing and pushing**, always check for untracked migrations: `git status database/migrations/`. Migrations must be committed together with the code that depends on them (models, controllers, scopes referencing new columns). Never push code that references a column without its migration — this causes production crashes.

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

All routes use Dutch URL slugs: `/initiatieven`, `/doelen`, `/bijdragers`, `/themas`, `/fiches`, `/profiel`. Route names are English: `initiatives.index`, `goals.show`, `contributors.index`, etc.

### CSS & Tailwind

Custom design tokens are defined as CSS custom properties in `resources/css/app.css` (colors, typography, spacing). The `@theme` block in the CSS customizes Flux UI accent colors. **Do not add spacing keys like `xl`, `2xl`, `3xl`, `4xl` to `tailwind.config.js` `extend.spacing`** — they conflict with Tailwind v4's `max-w-*` resolution.

**Typography:** Headings use **Aleo** (slab-serif, weight 700) and body text uses **Fira Sans** (sans-serif, weights 300–700). Both are loaded from **Bunny Fonts** (privacy-friendly, no Google Fonts). Tailwind utilities: `font-heading` for Aleo, `font-body` for Fira Sans. Card titles (`<flux:heading>`) need explicit `class="font-heading font-bold"` since they don't render as `<h1>`–`<h3>` elements.

Component classes (`.cta-link`, `.btn-pill`, `.section-label`, `.content-card`, `.diamant-badge`, etc.) are defined in `@layer components` in `app.css`.

**Design System:**
- **Living pattern library** (admin-only): view at `resources/views/admin/design-system.blade.php` — all visual components rendered with real CSS/Blade. Read this file to see every component name, markup pattern, and usage note.
- **Written reference**: [`docs/DESIGN_SYSTEM.md`](docs/DESIGN_SYSTEM.md) — concise rules for when to use which component.

## Design Context

### Users

**Public site**: Activity coordinators ("animatoren" / "begeleidsters") in Flemish residential care homes. Mostly women aged 35–55, practically oriented, not tech-native. They visit the platform during work breaks or preparation time, looking for concrete ideas they can implement tomorrow. The job to be done: **find, adapt, and share practical activity ideas** that genuinely improve residents' quality of life.

**Admin section**: Used exclusively by the platform administrator (single person, technically proficient). Admin pages track platform health, content quality scores, and AI suggestion adoption. They are internal tools, not user-facing.

### Brand Personality

**Warm, Practical, Encouraging.** The platform speaks like a supportive colleague — never academic, never condescending. It celebrates the small, real moments of care work. The tone is direct and hopeful: "you can do this too."

### Emotional Goals

The primary emotion to evoke is **belonging and pride** — "I'm part of something meaningful." Coordinators should feel they're contributing to a community of peers who share the same mission. Secondary: quiet confidence that their work matters.

### Aesthetic Direction

- **Visual tone**: Warm, tactile, handcrafted — like a well-loved scrapbook or bulletin board in a care home common room. Paper textures (polaroid frames, ruled-paper quotes, stacked fiche cards), generous white space, cream and orange warmth.
- **Reference**: Airbnb-adjacent — clean layout, beautiful photography, content-first, but warmer and less polished. The interface should feel personal, not corporate.
- **Anti-references**: Clinical healthcare portals, cold SaaS dashboards, overly minimal tech aesthetics. Nothing that feels institutional or sterile — the platform should be the opposite of the environment these users are trying to humanize.
- **Theme**: Light mode only. The warm cream/orange palette doesn't translate well to dark mode and the audience has no expectation of it.
- **Admin pages**: Same warm palette and typography, but slightly more data-dense and compact. More utilitarian — tighter spacing, smaller text, more information per screen. Still uses the same color tokens and components; just less generous whitespace than public pages.

### Design Tokens (Quick Reference)

```
Primary orange:    #E8764B   (--color-primary)
Primary hover:     #D4683F   (--color-primary-hover)
Secondary teal:    #4CB7C5   (--color-secondary)
Accent yellow:     #F4C44E   (--color-yellow)
Accent purple:     #B57BB3   (--color-accent-purple)

Text primary:      #231E1A   (--color-text-primary)
Text secondary:    #756C65   (--color-text-secondary)
Text tertiary:     #C0B5AE   (--color-text-tertiary) — meta icons, timestamps

BG white:          #FFFFFF   (--color-bg-white)
BG cream:          #FEF8F4   (--color-bg-cream) — page backgrounds
BG subtle:         #F5F0EC   (--color-bg-subtle) — alternating sections
BG accent light:   #FDF3EE   (--color-bg-accent-light) — active states

Border light:      #EBE4DE   (--color-border-light)
Border hover:      #DDD5CD   (--color-border-hover)

Heading font:      Aleo (slab-serif, weight 700) — font-heading
Body font:         Fira Sans (sans-serif, 300–700) — font-body
Both from Bunny Fonts (not Google Fonts)
```

### Component Patterns

- **Section labels**: `.section-label` — uppercase, tracking-widest, orange, 18px. Use above h2 headings.
- **CTA links**: `.cta-link` — orange text + arrow that animates on hover. Prefer over filled buttons for secondary actions.
- **Primary buttons**: `.btn-pill` — filled orange, rounded-full. Use sparingly — one primary CTA per section.
- **Cards**: `<flux:card>` — white background, soft shadow. Never nest cards inside cards.
- **Meta icons**: `w-4 h-4`, color `--color-text-tertiary`, `gap-1.5` icon-to-number, `gap-4` between pairs.
- **flux:heading in cards**: Always add `class="font-heading font-bold"` — flux cards don't inherit heading styles automatically.
- **Score colors**: green-700 (≥70), amber-600 (40–69), red-600 (<40). Plain colored text, no pill backgrounds.

### Design Principles

1. **Warmth over polish** — Every surface should feel warm and human. Prefer cream backgrounds, soft shadows, rounded corners, and organic touches (paper textures, handwritten accents) over pixel-perfect minimalism.
2. **Content is the hero** — Initiatives, elaborations, and the DIAMANT goals are the stars. UI chrome should recede. Large text, generous spacing, real photography over icons where possible.
3. **Accessible by default** — WCAG AA compliance. Good contrast on warm backgrounds, generous click targets, readable font sizes (base 17px for forms). Many users are 40–60 and not digitally fluent.
4. **Encourage, don't overwhelm** — Progressive disclosure. Show the essential path clearly. Use section labels + outcome-driven headings to guide rather than dump. Celebrate contributions (confetti, kudos hearts) to reinforce participation.
5. **Flemish Dutch, direct, imperative** — All copy is written in Belgian Dutch (Flemish), not Holland Dutch. Use the Dutch imperative form ("Registreer", "Ontdek", "Deel"). Headings are outcome-driven, not descriptive. The voice is a peer, not an authority. Avoid Hollandisms: no "hartstikke", "gewoon" as an adverb, "lekker" for non-food contexts, "dat klopt", or "super" as an intensifier. Use Flemish care vocabulary: "woonzorgcentrum", "bewoners", "begeleidster", "animatoren".
6. **Data honesty in admin** — Admin visualizations show what's real, not what looks impressive. De-emphasize percentages when N is small. Count as primary stat, rate as secondary. "Te weinig data" over misleading 100%s.

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.4
- laravel/ai (AI) - v0
- laravel/framework (LARAVEL) - v13
- laravel/pennant (PENNANT) - v1
- laravel/prompts (PROMPTS) - v0
- livewire/flux (FLUXUI_FREE) - v2
- livewire/flux-pro (FLUXUI_PRO) - v2
- livewire/livewire (LIVEWIRE) - v4
- laravel/boost (BOOST) - v2
- laravel/breeze (BREEZE) - v2
- laravel/mcp (MCP) - v0
- laravel/pail (PAIL) - v1
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- phpunit/phpunit (PHPUNIT) - v12
- alpinejs (ALPINEJS) - v3
- tailwindcss (TAILWINDCSS) - v4

## Skills Activation

This project has domain-specific skills available. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

- `pennant-development` — Use when working with Laravel Pennant the official Laravel feature flag package. Trigger whenever the query mentions Pennant by name or involves feature flags or feature toggles in a Laravel project. Tasks include defining feature flags checking whether features are active creating class based features in `app/Features` using Blade `@feature` directives scoping flags to users or teams building custom Pennant storage drivers protecting routes with feature flags testing feature flags with Pest or PHPUnit and implementing A B testing or gradual rollouts with feature flags. Do not trigger for generic Laravel configuration authorization policies authentication or non Pennant feature management systems.
- `fluxui-development` — Use this skill for Flux UI development in Livewire applications only. Trigger when working with <flux:*> components, building or customizing Livewire component UIs, creating forms, modals, tables, or other interactive elements. Covers: flux: components (buttons, inputs, modals, forms, tables, date-pickers, kanban, badges, tooltips, etc.), component composition, Tailwind CSS styling, Heroicons/Lucide icon integration, validation patterns, responsive design, and theming. Do not use for non-Livewire frameworks or non-component styling.
- `livewire-development` — Use for any task or question involving Livewire. Activate if user mentions Livewire, wire: directives, or Livewire-specific concepts like wire:model, wire:click, wire:sort, or islands, invoke this skill. Covers building new components, debugging reactivity issues, real-time form validation, drag-and-drop, loading states, migrating from Livewire 3 to 4, converting component formats (SFC/MFC/class-based), and performance optimization. Do not use for non-Livewire reactive UI (React, Vue, Alpine-only, Inertia.js) or standard Laravel forms without Livewire.
- `tailwindcss-development` — Always invoke when the user's message includes 'tailwind' in any form. Also invoke for: building responsive grid layouts (multi-column card grids, product grids), flex/grid page structures (dashboards with sidebars, fixed topbars, mobile-toggle navs), styling UI components (cards, tables, navbars, pricing sections, forms, inputs, badges), adding dark mode variants, fixing spacing or typography, and Tailwind v3/v4 work. The core use case: writing or fixing Tailwind utility classes in HTML templates (Blade, JSX, Vue). Skip for backend PHP logic, database queries, API routes, JavaScript with no HTML/CSS component, CSS file audits, build tool configuration, and vanilla CSS.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

- Laravel Boost is an MCP server that comes with powerful tools designed specifically for this application. Use them.

## Artisan Commands

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`, `php artisan tinker --execute "..."`).
- Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.

## URLs

- Whenever you share a project URL with the user, you should use the `get-absolute-url` tool to ensure you're using the correct scheme, domain/IP, and port.

## Debugging

- Use the `database-query` tool when you only need to read from the database.
- Use the `database-schema` tool to inspect table structure before writing migrations or models.
- To execute PHP code for debugging, run `php artisan tinker --execute "your code here"` directly.
- To read configuration values, read the config files directly or run `php artisan config:show [key]`.
- To inspect routes, run `php artisan route:list` directly.
- To check environment variables, read the `.env` file directly.

## Reading Browser Logs With the `browser-logs` Tool

- You can read browser logs, errors, and exceptions using the `browser-logs` tool from Boost.
- Only recent browser logs will be useful - ignore old logs.

## Searching Documentation (Critically Important)

- Boost comes with a powerful `search-docs` tool you should use before trying other approaches when working with Laravel or Laravel ecosystem packages. This tool automatically passes a list of installed packages and their versions to the remote Boost API, so it returns only version-specific documentation for the user's circumstance. You should pass an array of packages to filter on if you know you need docs for particular packages.
- Search the documentation before making code changes to ensure we are taking the correct approach.
- Use multiple, broad, simple, topic-based queries at once. For example: `['rate limiting', 'routing rate limiting', 'routing']`. The most relevant results will be returned first.
- Do not add package names to queries; package information is already shared. For example, use `test resource table`, not `filament 4 test resource table`.

### Available Search Syntax

1. Simple Word Searches with auto-stemming - query=authentication - finds 'authenticate' and 'auth'.
2. Multiple Words (AND Logic) - query=rate limit - finds knowledge containing both "rate" AND "limit".
3. Quoted Phrases (Exact Position) - query="infinite scroll" - words must be adjacent and in that order.
4. Mixed Queries - query=middleware "rate limit" - "middleware" AND exact phrase "rate limit".
5. Multiple Queries - queries=["authentication", "middleware"] - ANY of these terms.

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.

## Constructors

- Use PHP 8 constructor property promotion in `__construct()`.
    - `public function __construct(public GitHub $github) { }`
- Do not allow empty `__construct()` methods with zero parameters unless the constructor is private.

## Type Declarations

- Always use explicit return type declarations for methods and functions.
- Use appropriate PHP type hints for method parameters.

<!-- Explicit Return Types and Method Params -->
```php
protected function isAccessible(User $user, ?string $path = null): bool
{
    ...
}
```

## Enums

- Typically, keys in an Enum should be TitleCase. For example: `FavoritePerson`, `BestLake`, `Monthly`.

## Comments

- Prefer PHPDoc blocks over inline comments. Never use comments within the code itself unless the logic is exceptionally complex.

## PHPDoc Blocks

- Add useful array shape type definitions when appropriate.

=== herd rules ===

# Laravel Herd

- The application is served by Laravel Herd and will be available at: `https?://[kebab-case-project-dir].test`. Use the `get-absolute-url` tool to generate valid URLs for the user.
- You must not run any commands to make the site available via HTTP(S). It is always available through Laravel Herd.

=== tests rules ===

# Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

## Database

- Always use proper Eloquent relationship methods with return type hints. Prefer relationship methods over raw queries or manual joins.
- Use Eloquent models and relationships before suggesting raw database queries.
- Avoid `DB::`; prefer `Model::query()`. Generate code that leverages Laravel's ORM capabilities rather than bypassing them.
- Generate code that prevents N+1 query problems by using eager loading.
- Use Laravel's query builder for very complex database operations.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

### APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## Controllers & Validation

- Always create Form Request classes for validation rather than inline validation in controllers. Include both validation rules and custom error messages.
- Check sibling Form Requests to see if the application uses array or string based validation rules.

## Authentication & Authorization

- Use Laravel's built-in authentication and authorization features (gates, policies, Sanctum, etc.).

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Queues

- Use queued jobs for time-consuming operations with the `ShouldQueue` interface.

## Configuration

- Use environment variables only in configuration files - never use the `env()` function directly outside of config files. Always use `config('app.name')`, not `env('APP_NAME')`.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== livewire/core rules ===

# Livewire

- Livewire allow to build dynamic, reactive interfaces in PHP without writing JavaScript.
- You can use Alpine.js for client-side interactions instead of JavaScript frameworks.
- Keep state server-side so the UI reflects it. Validate and authorize in actions as you would in HTTP requests.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== phpunit/core rules ===

# PHPUnit

- This application uses PHPUnit for testing. All tests must be written as PHPUnit classes. Use `php artisan make:test --phpunit {name}` to create a new test.
- If you see a test using "Pest", convert it to PHPUnit.
- Every time a test has been updated, run that singular test.
- When the tests relating to your feature are passing, ask the user if they would like to also run the entire test suite to make sure everything is still passing.
- Tests should cover all happy paths, failure paths, and edge cases.
- You must not remove any tests or test files from the tests directory without approval. These are not temporary or helper files; these are core to the application.

## Running Tests

- Run the minimal number of tests, using an appropriate filter, before finalizing.
- To run all tests: `php artisan test --compact`.
- To run all tests in a file: `php artisan test --compact tests/Feature/ExampleTest.php`.
- To filter on a particular test name: `php artisan test --compact --filter=testName` (recommended after making a change to a related file).

</laravel-boost-guidelines>
