# Auto Fiche Icons — Design Spec

## Problem

The fiche list on initiative pages shows 74+ items that all look identical — same document icon, same styling. Users can't visually distinguish fiches at a glance, making it hard to find or remember specific activities.

## Solution

Automatically assign a contextual Lucide icon to each fiche based on its content, display it in a colored disc (like user avatar initials), and increase the visual weight of list items.

## Data Model

Add one nullable string column to the `fiches` table:

- **`icon`** — Lucide icon name (e.g., `music`, `flower-2`, `heart`). Nullable; when null, the existing document icon is shown as fallback.

Add `icon` to the Fiche model's `$fillable` array.

Color is **not stored** — it's computed at render time as `$fiche->id % 6`.

## Icon Assignment

### Trigger

A queued job `AssignFicheIcon` is dispatched when:
- A fiche is **created**
- A fiche's **title is updated** (re-assigns icon to match new content)

Use a Fiche model observer with `created` and `updated` events. In the `updated` handler, check `$fiche->isDirty('title')` to only re-assign when the title actually changed.

### AI Icon Selection

The job sends the fiche title (and description excerpt if available) to the Claude API via `laravel/ai`, along with a curated allowlist of ~100 Lucide icon names relevant to elderly care activities.

**Provider/model:** Use the `anthropic` provider with `claude-haiku-4-5-20251001` (fast, cheap, sufficient for single-word classification). This is the first `laravel/ai` usage in this codebase — ensure the `anthropic` provider is configured in `config/ai.php`.

**Why a curated subset?** Lucide has 1700+ icons, many tech/dev-oriented. A curated list ensures the AI never picks inappropriate icons (e.g., `git-branch`, `terminal`). The allowlist is stored as a config array in `config/fiche-icons.php`.

**Prompt structure:**
```
Given this activity title: "{title}"
And optional description: "{description_excerpt}"

Pick the single most representative icon from this list: [allowlist]

Return only the icon name, nothing else.
```

**Fallback:** If the AI call fails (network error, rate limit), the `icon` column stays null. The UI gracefully falls back to the document icon. Failed assignments can be retried via the backfill command.

### Curated Icon Allowlist (~100 icons)

Organized by activity theme. Examples:

- **Music/performance:** `music`, `mic`, `guitar`, `piano`, `headphones`, `radio`
- **Nature/outdoors:** `flower-2`, `trees`, `sun`, `cloud-sun`, `bird`, `leaf`, `sprout`
- **Food/cooking:** `cooking-pot`, `utensils`, `cake`, `apple`, `salad`, `coffee`
- **Arts/crafts:** `palette`, `scissors`, `paintbrush`, `pen-tool`, `origami`
- **Games/puzzles:** `puzzle`, `dice-5`, `trophy`, `target`, `gamepad-2`
- **Health/movement:** `heart-pulse`, `footprints`, `bike`, `dumbbell`, `smile`
- **Social/celebration:** `party-popper`, `gift`, `heart`, `users`, `handshake`
- **Learning/memory:** `brain`, `book-open`, `lightbulb`, `graduation-cap`, `newspaper`
- **Seasons/holidays:** `snowflake`, `sun`, `leaf`, `egg`, `star`, `candle`
- **Animals:** `dog`, `cat`, `bird`, `fish`, `rabbit`
- **General:** `camera`, `map`, `globe`, `compass`, `clock`, `calendar`, `flag`
- **Fallback:** `file-text` (matches current document icon)

The full list will be finalized during implementation by reviewing actual fiche titles in the database. The config array must be deduplicated (e.g., `sun` and `bird` appear in multiple categories above).

### Backfill Command

`php artisan fiches:assign-icons` — processes all fiches where `icon` is null. Dispatches `AssignFicheIcon` jobs with a 200ms delay between dispatches to respect API rate limits. Shows a progress bar.

Options:
- `--force` — re-assign icons even for fiches that already have one
- No options by default processes only null icons

Error handling: if a single job fails, processing continues for remaining fiches. Failed fiches can be retried by running the command again (it only processes null icons by default).

## Color System

6 deterministic colors assigned by `$fiche->id % 6`:

| Index | Name   | Background | Icon Color |
|-------|--------|-----------|------------|
| 0     | Orange | `#FDF3EE` | `#E8764B`  |
| 1     | Teal   | `#E8F6F8` | `#3A9BA8`  |
| 2     | Yellow | `#FEF6E0` | `#B08A22`  |
| 3     | Purple | `#F3E8F3` | `#9A5E98`  |
| 4     | Green  | `#E8F5E9` | `#4A8C5C`  |
| 5     | Rose   | `#FDE8EC` | `#C0506A`  |

Colors 0–3 match the existing user avatar palette. Colors 4–5 are new additions following the same pastel-bg + saturated-icon pattern.

**Why ID-based, not icon-based?** When an initiative has many fiches with the same icon (e.g., 8 music quizzes), ID-based assignment ensures visual variety in the list. Icon-based would create a wall of the same color.

## UI Changes

### New Component: `<x-fiche-icon>`

Blade component that renders the colored icon disc.

**Props:**
- `:fiche` — the Fiche model instance (required)
- `size` — `sm` (32px), `md` (48px, default), `lg` (64px)

**Rendering logic:**
1. Compute color index: `$fiche->id % 6`
2. Look up bg/text color from the 6-color palette
3. If `$fiche->icon` is set, render via `<x-dynamic-component :component="'lucide-' . $fiche->icon" />` inside the colored disc
4. If `$fiche->icon` is null, render the existing document SVG as fallback

### Modified: Fiche List Items (`initiatives/show.blade.php`)

Current → Proposed changes:
- **Icon:** 32×32px rounded-rectangle with document SVG → **48px circle** (`border-radius: 50%`) with `<x-fiche-icon>`
- **Title font-size:** 16px → **18px**
- **Padding:** 16px all → **18px vertical, 20px horizontal**
- **Gap:** 12px → **16px** between icon and text

The CSS classes `.fiche-list-item` and `.fiche-list-icon` in `app.css` will be updated accordingly. The existing hover rule (`.fiche-list-item:hover .fiche-list-icon`) that turns the icon orange should be removed — the colored disc already provides sufficient visual identity, and a fixed orange hover clashes with non-orange disc colors.

## Dependencies

- **`blade-ui-kit/blade-lucide-icons`** — Blade components for Lucide icons. Renders as `<x-lucide-{name}>`. Only bundles icons actually used (compiled on first render, cached).
- **`laravel/ai`** — already installed, used for Claude API calls.

## Testing

- **Unit test:** `AssignFicheIcon` job — mock AI response, verify icon is stored
- **Unit test:** `<x-fiche-icon>` component — verify correct color for given IDs, fallback when icon is null
- **Feature test:** Fiche creation dispatches `AssignFicheIcon` job
- **Feature test:** Fiche title update dispatches `AssignFicheIcon` job
- **Feature test:** Backfill command processes fiches with null icons

Update `FicheFactory` to include an `icon` field (default null) and add a `->withIcon(string $icon)` state for test convenience.

## Out of Scope

- No manual icon picker/editor UI
- No icon on initiative cards (only fiche list items within an initiative detail page)
- No changes to the fiche detail page layout
- No dark mode variants
- No icon on fiche cards elsewhere in the app (search results, bookmarks, etc.) — can be added later by reusing `<x-fiche-icon>`
