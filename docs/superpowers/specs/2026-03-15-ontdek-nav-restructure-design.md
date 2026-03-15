# Navigation Restructure: "Ontdek" Dropdown

**Date:** 2026-03-15
**Status:** Draft

## Problem

The profile sidebar has 4 items (Persoonlijke info, Beveiliging, Favorieten, Fiches) and was about to grow to 5 with the planned Downloads page. It mixes account settings with content pages. Favorieten and Fiches are not settings — they are content pages that belong in the main navigation, along with the planned Downloads feature.

## Supersedes

This spec supersedes `docs/superpowers/plans/2026-03-15-profile-downloads-page.md`. That plan placed downloads under the profile sidebar; this spec moves them to a standalone page at `/favorieten` combined with bookmarks. The downloads plan should be marked as canceled.

## Design

### 1. Main Navigation

Rename the "Initiatieven" nav item to **"Ontdek"**. Convert it from a plain link to a dropdown with 3 items, using the same rich dropdown pattern as "Doelen" and "Tools & inspiratie" (section header, icon + title + description per row, arrow indicator, hover-open on desktop, tap-toggle on mobile).

**Dropdown items:**

| Item | Description | Route | URL |
|------|-------------|-------|-----|
| Initiatieven | Ontdek alle activiteiten en ideeën | `initiatives.index` | `/initiatieven` |
| Downloads & favorieten | Gedownloade en opgeslagen fiches | `bookmarks.index` | `/favorieten` |
| Mijn fiches | Fiches die je hebt bijgedragen | `my-fiches.index` | `/mijn-fiches` |

All 3 items are visible to everyone (logged in and anonymous). Anonymous users see a conversion CTA page on `/favorieten` and `/mijn-fiches`.

### 2. New Routes

**Add:**
- `GET /favorieten` → route name `bookmarks.index`
- `GET /mijn-fiches` → route name `my-fiches.index`

**Remove from profile group:**
- `/profiel/favorieten`
- `/profiel/fiches`

Note: `/profiel/downloads` was never implemented (only planned). No redirect needed for it.

**301 Redirects (preserve bookmarks):**
- `/profiel/favorieten` → `/favorieten`
- `/profiel/fiches` → `/mijn-fiches`

**Profile routes that remain:**
- `/profiel` (show + update)
- `/profiel/beveiliging`
- `/profiel/avatar` (update + delete)

### 3. Downloads & Favorieten Page (`/favorieten`)

**Page title:** "Downloads & favorieten"

**Logged-in view:** Two-column layout using the base `layout` component (no sidebar).
- **Left column (~60%):** Downloads — queried from `UserInteraction` model (`type=download`), eager loading `interactable` (the `Fiche`) with its `initiative` and `user`. Filter out records where the fiche has been deleted. Sorted by `created_at` descending. Each row: fiche icon, title, download date.
- **Right column (~40%):** Favorieten — queried from the `$user->bookmarks()` relationship (polymorphic likes). Eager load the fiche with its `initiative` and `user`. Filter out records where the fiche has been deleted. Sorted by bookmark date. Each row: heart icon, title, save date.
- Section headers with item counts.
- On mobile: columns stack vertically, downloads first.

**Anonymous view:** Conversion CTA page. Warm, centered layout with:
- Heart + download icons
- Heading: "Bewaar je favoriete fiches"
- Body: "Sla inspirerende fiches op als favoriet en download ze om later te gebruiken. Zo heb je altijd ideeën bij de hand."
- Primary button: "Maak een gratis account" → register page
- Secondary link: "Al een account? Log in" → login page

### 4. Mijn Fiches Page (`/mijn-fiches`)

Moves from `/profiel/fiches` to `/mijn-fiches`. Same content and functionality as before — list of contributed fiches with comment badge for new comments. Uses the base `layout` component (no sidebar).

**Anonymous view:** Conversion CTA:
- Heading: "Deel jouw ervaring met collega's"
- Body: "Schrijf een fiche en help andere animatoren met praktische ideeën."
- Primary button: "Maak een gratis account" → register page
- Secondary link: "Al een account? Log in" → login page

Remove the `$newFicheCommentsCount` query from `sidebar-layout.blade.php` (line 3) since the Fiches item that uses it is being removed. This avoids a wasted DB query on every profile page load.

### 5. Profile Sidebar

Shrinks to 2 items:

```
Profiel
  Persoonlijke info
  Beveiliging
```

Admin section unchanged.

Both desktop (vertical navlist) and mobile (horizontal scroll tabs) versions must be updated.

### 6. Avatar Dropdown Menu

Remove "Favorieten" and "Fiches" entries from the user dropdown in the top-right nav. Keep:
- Profiel
- Beveiliging
- (Admin section if admin)
- Uitloggen

### 7. Mobile Navigation

"Ontdek" becomes an expandable section in the mobile menu (same pattern as "Doelen" and "Tools & inspiratie"), with the same 3 sub-items and descriptions.

## Files to Modify

- `resources/views/components/nav.blade.php` — rename Initiatieven to Ontdek dropdown (desktop + mobile)
- `resources/views/components/sidebar-layout.blade.php` — remove Favorieten, Downloads, Fiches from sidebar
- `routes/web.php` — add new routes, remove old profile routes, add redirects
- `app/Http/Controllers/ProfileController.php` — move `bookmarks()` and `fiches()` methods to new controllers (no `downloads()` method exists on ProfileController)
- New: `DownloadsAndBookmarksController` for `/favorieten` — receives the moved `bookmarks()` logic from ProfileController plus new downloads query
- New: `MyFichesController` for `/mijn-fiches` — receives the moved `fiches()` logic from ProfileController (including eager loads, comment counting, stats, `fiches_comments_seen_at` update)
- New: view for `/favorieten` (two-column layout)
- New: view for `/mijn-fiches` (moved from profile)
- New: anonymous CTA views (or inline conditionals in the views above)
- `tests/Feature/ProfileDownloadsTest.php` — this test references `profile.downloads` which was never implemented. Repurpose assertions into the new `bookmarks.index` tests or delete and rewrite
- `tests/Feature/ProfileFichesTest.php` (if exists) — update route references from `profile.fiches` to `my-fiches.index`
- Other tests referencing old profile routes need updating

## Out of Scope

- No changes to the Initiatieven page itself
- No changes to the bookmark/download/fiche functionality — only the location in the UI and URLs
- No new features on the moved pages
