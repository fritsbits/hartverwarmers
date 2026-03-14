# Per-User Fiche View & Download Tracking

**Date:** 2026-03-14
**Status:** Draft
**Context:** User testing revealed that activity directors browsing 70+ fiches within an initiative lose track of which ones they've already opened and which ones they've downloaded. They need subtle visual cues to distinguish explored fiches from fresh ones.

## Problem

When browsing a long list of fiches (e.g. 74 quizzes), activity directors have no way to tell which fiches they've already opened or downloaded. Every item looks identical, making it hard to pick up where they left off across sessions.

## Why not reuse the `likes` table?

The existing `likes` table has the same polymorphic structure and could technically store `view` and `download` types. However, interactions are fundamentally different from likes:
- **Volume:** Every page view creates a record. Likes are deliberate user actions — interactions are passive. Mixing them would bloat the likes table and slow queries for kudos/bookmarks.
- **Lifecycle:** Likes are user-managed (toggle on/off). Interactions are system-recorded and permanent.
- **Querying:** Likes are queried globally (total kudos count). Interactions are only queried per-user. Different access patterns benefit from separate tables.

## Solution

### Data: Polymorphic `user_interactions` table

A single table tracking per-user fiche views and downloads, following the same polymorphic pattern as the existing `likes` table.

**Migration:**
```php
Schema::create('user_interactions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->morphs('interactable'); // creates interactable_type + interactable_id + index
    $table->string('type'); // 'view' or 'download'
    $table->timestamp('created_at')->useCurrent();

    $table->unique(['user_id', 'interactable_type', 'interactable_id', 'type'], 'user_interactions_unique');
    $table->index(['user_id', 'interactable_type', 'type']);
});
```

**Key decisions:**
- `cascadeOnDelete()` on `user_id` — interactions are deleted when user account is deleted
- `$table->morphs('interactable')` — uses Laravel's helper for the polymorphic columns + index
- `->useCurrent()` on `created_at` — database sets the timestamp since `$timestamps = false` on the model (we don't need `updated_at`)

### Model: `UserInteraction`

```php
class UserInteraction extends Model
{
    const UPDATED_AT = null; // only created_at, no updated_at

    protected $fillable = ['user_id', 'interactable_type', 'interactable_id', 'type'];

    public function interactable(): MorphTo { ... }
    public function user(): BelongsTo { ... }
}
```

Using `const UPDATED_AT = null` instead of `$timestamps = false` so Laravel still auto-sets `created_at`.

### Recording interactions

**View tracking:** When a logged-in user opens a fiche detail page (`FicheController@show`), insert a `type=view` record if one doesn't already exist. Use `firstOrCreate` to avoid duplicates. This should be lightweight — no queue needed, the unique constraint prevents duplicates.

**Download tracking:** When a logged-in user downloads fiche files (`FicheController@downloadFiles`), insert a `type=download` record if one doesn't already exist. This happens alongside the existing `$fiche->increment('download_count')` — the global counter stays.

### Querying interactions for display

To show viewed/downloaded state on fiche lists, load interaction data for the current user across all displayed fiche IDs in a single query. Use a **service class** (`FicheInteractionService`) to avoid duplicating this logic across controllers:

```php
class FicheInteractionService
{
    /**
     * Returns a map of fiche ID → array of interaction types for the current user.
     * Example: [42 => ['view', 'download'], 87 => ['view']]
     *
     * @param  Collection|array  $ficheIds
     * @return array<int, array<string>>
     */
    public function forUser(?User $user, $ficheIds): array
    {
        if (!$user || empty($ficheIds)) {
            return [];
        }

        return UserInteraction::where('user_id', $user->id)
            ->where('interactable_type', Fiche::class)
            ->whereIn('interactable_id', $ficheIds)
            ->get()
            ->groupBy('interactable_id')
            ->map(fn ($interactions) => $interactions->pluck('type')->all())
            ->all();
    }
}
```

Each controller that loads fiches calls this service and passes the result to the view as `$ficheInteractions`.

### Passing interaction data to Blade components

The interaction map (`$ficheInteractions`) is passed from the controller to the view as a keyed array. In the view, each fiche list item or card receives two booleans:

**For inline list items** (initiative show page):
```blade
@php
    $viewed = isset($ficheInteractions[$fiche->id]) && in_array('view', $ficheInteractions[$fiche->id]);
    $downloaded = isset($ficheInteractions[$fiche->id]) && in_array('download', $ficheInteractions[$fiche->id]);
@endphp
<a class="fiche-list-item {{ $viewed ? 'fiche-list-item-viewed' : '' }}" ...>
    ...
    @if($downloaded)
        <flux:icon name="arrow-down-tray" class="size-3 text-[var(--color-text-secondary)]" />
    @endif
</a>
```

**For `<x-fiche-card>` component:**
Add two optional boolean props: `viewed` (default false) and `downloaded` (default false). The component applies the muted styling and download icon based on these props.

### Visual indicators

**Viewed fiches — muted appearance:**
- The fiche list item's text opacity is reduced (e.g. `opacity-60` on the title and author)
- The icon container background becomes slightly more faded
- The overall effect: unvisited fiches "pop" more, visited ones recede — like browser visited-link behavior
- CSS class: `fiche-list-item-viewed` applied to the `<a>` element

**Downloaded fiches — subtle download icon:**
- A small download-complete icon (e.g. `arrow-down-tray` from Heroicons, 12×12px) appears next to the kudos count or after the author name
- Muted color (secondary text color), not attention-grabbing
- This is additive — it appears alongside the viewed state if both apply

**State combinations:**
1. **Unvisited, not downloaded** — full opacity, no extra icons (default)
2. **Visited, not downloaded** — muted opacity
3. **Visited and downloaded** — muted opacity + download icon
4. **Downloaded but not visited** — this shouldn't happen in practice (you visit before downloading), but if it does: full opacity + download icon

### Where indicators appear

Everywhere fiches render as list items or cards, with interaction data loaded via `FicheInteractionService` in each controller:

- **Initiative detail page** — compact fiche list (`InitiativeController@show`)
- **Fiche detail page** — "Meer fiches" sidebar (`FicheController@show`)
- **Profile bookmarks page** — bookmarked fiches
- **Contributor profile page** — contributor's fiches
- **Search results** — Livewire search component (if it renders fiche results)

**Homepage:** The homepage renders recent fiches as lightweight data arrays (not Eloquent models) via `InitiativeController@index`. Adding interaction tracking there requires either passing fiche IDs through the array format or restructuring the data. **Deferred to a follow-up** — the homepage shows only 3 fiches at a time, so the value is low compared to initiative detail pages with 70+ fiches.

### Privacy

This tracking is compliant with the existing privacy policy:
- **Section 2** already lists "Activiteitstijdstempels" (activity timestamps) under automatically collected data
- **Section 3** legal basis is "Uitvoering overeenkomst (art. 6.1.b)" — improving the platform experience
- **Logged-in users only** — no cookies, no anonymous tracking
- Data is deleted when the user deletes their account via `cascadeOnDelete()` on the foreign key

## Edge Cases

- **Guest users:** No tracking, no visual indicators. All fiches look the same (default state).
- **Duplicate interactions:** Unique constraint prevents duplicates. `firstOrCreate` handles race conditions gracefully.
- **Soft-deleted fiches:** Interactions remain in the database but are irrelevant since the fiche won't appear in lists.
- **Performance:** The batch query approach (one query per page load for all displayed fiche IDs) keeps this to 1 additional query per page, not N+1.

## Testing

- **Unit test:** `UserInteraction` model creation, unique constraint enforcement
- **Feature test:** Viewing a fiche page creates a `type=view` interaction for the logged-in user
- **Feature test:** Downloading fiche files creates a `type=download` interaction AND increments `download_count`
- **Feature test:** Duplicate views/downloads don't create additional records
- **Feature test:** Guest users don't create interactions
- **Feature test:** Initiative show page passes interaction data to view for logged-in users
- **Feature test:** Initiative show page works without interaction data for guests

## Out of Scope

- Fiche list item visual redesign (bigger icons, author colors, preview images) — separate project
- View/download analytics dashboard for admins
- "Most viewed" sorting option
- Tracking views on non-fiche pages
- Homepage interaction indicators (deferred — low value, different data format)
