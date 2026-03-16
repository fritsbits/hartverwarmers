# Fiche Edit Button & Author-Aware Comment Copy

**Date:** 2026-03-16
**Status:** Draft

## Problem

1. The edit button for fiches is hidden inside an admin-only dropdown. Regular authors (contributors) who own a fiche cannot see the edit button on the show page, even though the policy and route already allow them to edit.
2. When the logged-in user views their own fiche, the comment placeholder says "Bedank de auteur, stel een vraag of deel een tip..." — which doesn't make sense when you ARE the author.

## Solution

### 1. Standalone Edit Button

Add an outline-style edit button on the **right side of the breadcrumb row**, visible to anyone who passes the `@can('update', $fiche)` gate (author, admin, curator).

**Placement:** Inside the existing `flex items-center justify-between` container at the top of the hero section in `fiches/show.blade.php` (line 49).

**Style:** `<flux:button>` with `variant="ghost"`, `size="sm"`, `icon="pencil-square"`, plus `border border-[var(--color-border-light)]` for a subtle outline. Text: "Bewerk". Neutral color — not orange.

**Link:** Uses `href="{{ route('fiches.edit', $fiche) }}"` attribute (renders as `<a>`, not `<button>`).

**Admin coexistence:** When the user is an admin, both the edit button and the admin dropdown appear side-by-side. The duplicate "Bewerk" menu item inside the admin dropdown is removed since the standalone button now covers it.

### 2. Author-Aware Comment Placeholder

In `fiche-comments.blade.php`, change the placeholder text conditionally:

- **Default (not the author):** "Bedank de auteur, stel een vraag of deel een tip..."
- **When viewing own fiche:** "Voeg een opmerking toe..."

This applies only to the **logged-in user** textarea placeholder (line 123). The guest comment section (lines 157, 160) is unchanged — guests cannot be authors, so the conditional would be dead code there.

The `FicheComments` Livewire component exposes an `$isAuthor` computed property (`auth()->id() === $this->fiche->user_id`) for the view to use.

## Files Changed

| File | Change |
|------|--------|
| `resources/views/fiches/show.blade.php` | Add `@can('update')` edit button on breadcrumb row; remove duplicate "Bewerk" from admin menu |
| `resources/views/livewire/fiche-comments.blade.php` | Conditional placeholder text based on `$isAuthor` |
| `app/Livewire/FicheComments.php` | Add `isAuthor` computed property |

## No New Files

No new routes, controllers, policies, migrations, or components needed. Everything builds on existing infrastructure.

## Testing

- Feature test: author sees edit button on own fiche show page
- Feature test: curator sees edit button on any fiche
- Feature test: non-author contributor does not see edit button
- Feature test: admin sees both edit button and admin dropdown (without duplicate "Bewerk" in menu)
- Feature test: comment placeholder shows "Voeg een opmerking toe..." when viewing own fiche
- Feature test: comment placeholder shows "Bedank de auteur..." when viewing another user's fiche
