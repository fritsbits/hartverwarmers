# Navigation Restructure: "Ontdek" Dropdown

**Date:** 2026-03-15
**Status:** Draft

## Problem

The profile sidebar has grown to 5 items (Persoonlijke info, Beveiliging, Favorieten, Downloads, Fiches), mixing account settings with content pages. Favorieten, Downloads, and Mijn fiches are not settings — they are content pages that belong in the main navigation.

## Design

### 1. Main Navigation

Rename the "Initiatieven" nav item to **"Ontdek"**. Convert it from a plain link to a dropdown with 3 items, using the same rich dropdown pattern as "Doelen" and "Tools & inspiratie" (section header, icon + title + description per row, arrow indicator, hover-open on desktop, tap-toggle on mobile).

**Dropdown items:**

| Item | Description | Route | URL |
|------|-------------|-------|-----|
| Initiatieven | Ontdek alle activiteiten en ideeën | `initiatives.index` | `/initiatieven` |
| Downloads & favorieten | Jouw gedownloade en opgeslagen fiches | `bookmarks.index` | `/favorieten` |
| Mijn fiches | Fiches die je hebt bijgedragen | `my-fiches.index` | `/mijn-fiches` |

All 3 items are visible to everyone (logged in and anonymous). Anonymous users see a conversion CTA page on `/favorieten` and `/mijn-fiches`.

### 2. New Routes

**Add:**
- `GET /favorieten` → route name `bookmarks.index`
- `GET /mijn-fiches` → route name `my-fiches.index`

**Remove from profile group:**
- `/profiel/favorieten`
- `/profiel/downloads`
- `/profiel/fiches`

**301 Redirects (preserve bookmarks):**
- `/profiel/favorieten` → `/favorieten`
- `/profiel/downloads` → `/favorieten`
- `/profiel/fiches` → `/mijn-fiches`

**Profile routes that remain:**
- `/profiel` (show + update)
- `/profiel/beveiliging`
- `/profiel/avatar` (update + delete)

### 3. Downloads & Favorieten Page (`/favorieten`)

**Page title:** "Downloads & favorieten"

**Logged-in view:** Two-column layout using the base `layout` component (no sidebar).
- **Left column (~60%):** Downloads — list of downloaded fiches, sorted by download date. Each row: fiche icon, title, download date.
- **Right column (~40%):** Favorieten — bookmarked fiches, sorted by bookmark date. Each row: heart icon, title, save date.
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
- `app/Http/Controllers/ProfileController.php` — remove `bookmarks()`, `downloads()`, `fiches()` methods
- New: controller(s) for `/favorieten` and `/mijn-fiches`
- New: view for `/favorieten` (two-column layout)
- New: view for `/mijn-fiches` (moved from profile)
- New: anonymous CTA views (or inline conditionals in the views above)
- Existing tests referencing old routes need updating

## Out of Scope

- No changes to the Initiatieven page itself
- No changes to the bookmark/download/fiche functionality — only the location in the UI and URLs
- No new features on the moved pages
