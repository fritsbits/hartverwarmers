# Initiative & Fiche Filtering, Sorting & Search

**Date:** 2026-03-14
**Status:** Draft
**Context:** User testing revealed that activity directors lose their place when browsing fiches on an initiative page. After clicking through to a fiche and pressing back, the page resets to its initial state. The sorting on the initiatives index ("Ontdek") is also opaque — users don't understand the logic behind the order.

## Problem

1. **Lost scroll/filter state on back-navigation:** The initiative detail page uses Alpine.js `x-show` to toggle fiches beyond the first 6. When the user clicks a fiche (full page navigation to `/initiatieven/{slug}/{fiche-slug}`), then presses back, Alpine state resets — the list collapses and the user loses track of where they were.
2. **No way to search or sort fiches within an initiative:** With 70+ fiches (e.g. Quiz), users can only scroll a flat list. No search, no sorting, no way to discover what's popular or find something specific.
3. **Opaque "Ontdek" sort on initiatives index:** The interleaved rich/growing/needs-love logic is invisible to users. The label doesn't communicate what it does.

## Solution

### Approach: Alpine.js + URL Query Params

Client-side filtering/sorting with Alpine.js (consistent with existing codebase pattern), but with sort/search state persisted in URL query parameters (`?sort=popular&q=kerst`). This ensures:
- Browser back button restores the exact filtered/sorted state
- Shareable/bookmarkable filter URLs
- No server round-trips for filtering (all fiches loaded upfront)
- Consistent pattern across both pages

## Changes

### 1. Initiative Detail Page (`/initiatieven/{slug}`)

#### Show all fiches (no toggle)
- Remove the "show 6 + expand" pattern (`x-data="{ showAll: ... }"`, the `+ N meer` button)
- Render all fiches in the list, always visible
- Remove the diamond fiche pinning — it sorts with everything else
- All fiches use the **compact list-item format** (icon, title, author, kudos count) — the special `<x-fiche-card>` rendering for the diamond fiche is removed

#### Add search input
- Text input filtering fiches by **title + description**
- Debounced (200ms), same pattern as initiatives index
- Synced to URL param: `?q=kerst`
- Fiche description text must be passed to Alpine data for client-side matching (stripped of HTML, truncated to ~200 chars)

#### Add sort pills with icons and tooltips
Sort options (in this order):
1. **Nieuwste** (default) — sorted by `created_at` descending. Icon: `flux:icon.clock`. Tooltip: "Nieuwste fiches eerst"
2. **Populair** — sorted by `kudos_count` descending (denormalized column on `fiches` table, always available — no `withCount` needed). Icon: `flux:icon.heart`. Tooltip: "Fiches met de meeste kudos"
3. **Willekeurig** — shuffled order, re-shuffled on each page load. Icon: `flux:icon.arrows-right-left`. Tooltip: "Willekeurige volgorde — verras jezelf!"
4. **A–Z** — alphabetical by title. Icon: `flux:icon.bars-3-bottom-left`. Tooltip: "Alfabetische volgorde"

Default is "Nieuwste" because on an initiative page, users want to see what's new — what colleagues recently contributed. On the index page, A–Z is the default because users are browsing a known catalogue of initiative names.

Synced to URL param: `?sort=newest` (default, omitted from URL), `?sort=popular`, `?sort=random`, `?sort=az`

#### URL param sync behavior
- On page load, read `sort` and `q` from URL query params and apply them
- On sort/search change, update URL params via `history.replaceState()` (no page reload)
- Default sort (`newest`) is omitted from URL to keep URLs clean
- Empty search is omitted from URL

#### Controller changes
- Pass fiche `description` (stripped of HTML, truncated to ~200 chars) and `created_at` timestamp to Alpine data
- Generate a shuffled array of fiche IDs: `$initiative->fiches->pluck('id')->shuffle()->values()->all()` — passed to Alpine as `randomOrder` so "Willekeurig" is deterministic within a page load but different across loads

### 2. Initiatives Index Page (`/initiatieven`)

#### Replace sort options
Old: Ontdek | Meest bijdragen | A–Z
New (in this order):
1. **A–Z** (default) — alphabetical by title. Icon: `flux:icon.bars-3-bottom-left`. Tooltip: "Alle initiatieven op alfabetische volgorde"
2. **Veel fiches** — initiatives with 10+ fiches, sorted by fiche count descending. Icon: `flux:icon.square-3-stack-3d`. Tooltip: "Initiatieven met de meeste uitwerkingen"
3. **Hulp nodig** — initiatives with <3 fiches, sorted by fiche count ascending. Icon: `flux:icon.hand-raised`. Tooltip: "Initiatieven die nog uitwerkingen zoeken"
4. **Willekeurig** — shuffled order (all initiatives visible). Icon: `flux:icon.arrows-right-left`. Tooltip: "Willekeurige volgorde — verras jezelf!"

Note: "Veel fiches" and "Hulp nodig" are **filters** as well as sorts — they hide initiatives that don't match the threshold. "A–Z" and "Willekeurig" show all initiatives. This intentionally replaces the old "Meest bijdragen" unfiltered popularity sort — the user specifically wanted these two curated views instead of a generic popularity ranking.

#### Add icons and tooltips to sort pills
All sort pills get a Flux icon and a native `title` attribute tooltip. Same pattern on both pages.

#### URL param sync
- Sync `sort`, `q`, and `goals` (DIAMANT filter) to URL params
- `?sort=az` (default, omitted), `?sort=rich`, `?sort=needs-love`, `?sort=random`
- `?q=muziek` for search
- `?goals=doel-doen,doel-talent` for DIAMANT filters (comma-separated, parsed via `split(',')` in Alpine)
- Use `history.replaceState()` on change

#### Remove `discoverOrder` logic
The `discoverOrder` interleaving logic in `InitiativeController@index` can be removed. The `$rich`, `$growing`, `$needsLove` server-side categorization is replaced by client-side threshold filtering (`fichesCount >= 10` for "Veel fiches", `fichesCount < 3` for "Hulp nodig").

Keep the `$needsLoveInitiatives` for the "Jouw ervaring telt" CTA card — that's independent of sorting.

Generate a shuffled array of initiative IDs: `$initiatives->pluck('id')->shuffle()->values()->all()` — passed as `randomOrder`.

#### "Recent gedeeld" section unchanged
The editorial "Collega's deelden over..." carousel section at the top of the index page is independent of the sort/filter system and remains unchanged.

### 3. Shared: Sort Pill Pattern

Both pages use the same sort pill UI pattern. Consistent markup (not a Blade component — the options differ per page):
- Pill group container: `inline-flex rounded-full bg-[var(--color-bg-subtle)] p-1`
- Individual pill button: icon + text, with `title` attribute for tooltip
- Active state: `bg-white shadow-sm text-[var(--color-text-primary)]`
- Inactive state: `text-[var(--color-text-secondary)] hover:text-[var(--color-text-primary)]`

### 4. Responsive behavior

Sort pills on mobile: use **icon + text** for all options. With 4 pills, they may wrap on very narrow screens — the pill group should allow wrapping (`flex-wrap`) rather than horizontal overflow. On mobile (<640px), pill text can be shortened if needed (e.g. "Veel fiches" → "Veel").

## Data Flow

### Initiative Detail Page
```
Controller loads all published fiches with tags, user, files, bookmark count
  → passes to Blade as $initiative->fiches (eager loaded, no change)
  → Blade builds Alpine data array: [{id, title, description, kudosCount, createdAt}, ...]
  → Note: kudosCount comes from the denormalized `kudos_count` column, not a withCount
  → Also passes server-generated randomOrder array (shuffled fiche IDs)
  → Alpine reads URL params on init, applies sort + search
  → On user interaction, Alpine updates filtered/sorted view + replaceState URL
```

### Initiatives Index Page
```
Controller loads all initiatives with fiches_count (no change to query)
  → removes discoverOrder computation (rich/growing/needsLove interleaving)
  → passes server-generated randomOrder array instead (shuffled initiative IDs)
  → Blade builds Alpine data array (already exists, add nothing new)
  → Alpine reads URL params on init (new), applies sort + search + goal filters
  → On user interaction, Alpine updates + replaceState URL
```

## Edge Cases

- **Empty search results:** Show empty state with "Geen fiches gevonden" and a "Wis zoekopdracht" button (same pattern as initiatives index)
- **"Veel fiches" / "Hulp nodig" with search:** Search filters within the sort's subset. If searching for "kerst" while on "Hulp nodig", only show initiatives with <3 fiches that match "kerst"
- **URL params on first visit:** No params = default sort, no search, no filters. Clean URLs.
- **Invalid URL params:** Ignore unknown sort values, fall back to default
- **Willekeurig consistency:** Random order is server-generated per page load. Pressing back restores the same random order (it's in Alpine data). Refreshing the page generates a new random order.

## Testing

- **Feature test:** Initiative show page loads all fiches (no pagination), passes correct Alpine data structure
- **Feature test:** Initiative index passes `randomOrder` instead of `discoverOrder`
- **Feature test:** URL query params don't break page load (e.g. `?sort=invalid` doesn't error)
- **Manual test:** Sort pills update URL, back button restores state, search filters correctly

## Out of Scope

- Tag-based filtering on initiative detail page (future enhancement)
- Livewire/server-side filtering (not needed at current data scale)
- Infinite scroll or pagination (all fiches loaded upfront)
