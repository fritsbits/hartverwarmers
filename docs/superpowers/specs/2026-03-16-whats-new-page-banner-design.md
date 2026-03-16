# Wat is er nieuw — Page & Banner Design

## Overview

Two components for launch communication: a static "What's New" page and a dismissable homepage banner. Both are temporary — meant for the first weeks after launch, then manually removed.

**Target audience:** Returning users (activity coordinators in Flemish care homes) who will encounter the rebuilt platform for the first time.

**Tone:** Warm, Flemish, concise. No marketing speak. Jij-vorm.

---

## Component 1: Static Page `/wat-is-er-nieuw`

### Route

```php
// web.php — place alongside other Route::view() declarations (near /over-ons)
Route::view('/wat-is-er-nieuw', 'wat-is-er-nieuw')->name('whats-new');
```

Public, no authentication required. Not in main navigation — reachable via the homepage banner and future reactivation email.

### View

**File:** `resources/views/wat-is-er-nieuw.blade.php`

Follows the `about.blade.php` pattern:
- `<x-layout title="Wat is er nieuw" description="Ontdek wat er veranderd is op Hartverwarmers: nieuwe structuur, betere navigatie en automatische PDF-conversie." :full-width="true">`
- Hero section: cream background, `section-label` ("Nieuw"), `h1`, intro paragraph
- Content sections: white background, separated by `<hr>` dividers
- Content width: `max-w-6xl` outer container (matching other full-width pages), with `max-w-3xl` on text blocks for comfortable reading line length (matching `about.blade.php` pattern)

### Content Blocks

**Block 1: Opening (Hero)**
- h1: "Een nieuwe Hartverwarmers. Gebouwd door jullie."
- Intro paragraph about the 500 activities milestone and origin story
- Inline link to `/over-ons` (uses `route('about')`)

**Block 2: Eerst het praktische**
- h2 section title
- Reassurance about login and data preservation
- `<flux:callout>` component for visual accent on the "je moet opnieuw inloggen" message

**Block 3: Wat er anders is**
- h2 section title
- Two sub-blocks (h3): terminology changes (initiatieven/fiches) and photo upload removal
- Explanation of why photo uploads were removed (copyright fines)

**Block 4: Wat er beter is**
- h2 section title
- Two sub-blocks (h3): better browsing/previews and automatic PDF conversion

**Block 5: Wat er aankomt**
- h2 section title
- DIAMANT model teaser
- `<flux:badge>` with "Binnenkort" indicator

**Block 6: Jouw feedback telt**
- h2 section title
- User testing mention and feedback invitation
- `cta-link` styled mailto to info@hartverwarmers.be
- Closing line about the 500th activity milestone

### Page Text

All copy is provided verbatim in the briefing and must be used as-is. Key links:
- `/over-ons` → `route('about')`
- `info@hartverwarmers.be` → mailto link styled as `.cta-link`

---

## Component 2: Dismissable Homepage Banner

### File

`resources/views/components/whats-new-banner.blade.php`

### Visibility Logic

The banner and the existing onboarding banner (`<livewire:onboarding-banner />`) are **mutually exclusive**:

| User type | Condition | Sees |
|-----------|-----------|------|
| Guest (not logged in) | — | Whats-new banner |
| Existing user | `created_at < config('hartverwarmers.launch_date')` | Whats-new banner |
| New user | `created_at >= config('hartverwarmers.launch_date')` | Onboarding banner |

**Config value:** `config('hartverwarmers.launch_date')` set to `2026-03-19` in `config/hartverwarmers.php`.

**Server-side checks:**

Banner component (`whats-new-banner.blade.php`):
```php
@php
$isNewUser = auth()->check() && auth()->user()->created_at->gte(\Carbon\Carbon::parse(config('hartverwarmers.launch_date')));
@endphp

@unless($isNewUser)
    {{-- render banner with Alpine.js dismiss --}}
@endunless
```

OnboardingBanner (`app/Livewire/OnboardingBanner.php`) — add guard at top of `mount()`:
```php
// Existing users see whats-new banner instead
if (auth()->check() && auth()->user()->created_at->lt(\Carbon\Carbon::parse(config('hartverwarmers.launch_date')))) {
    return;
}
```

**Client-side dismiss:** Alpine.js checks `localStorage.getItem('whatsNewDismissed')`. On close, sets the flag. Banner stays dismissed across page loads.

### Markup & Design

- Full-width bar below hero, above rest of homepage content
- `bg-[var(--color-bg-cream)]` with `border-b border-[var(--color-border-light)]`
- Horizontal flex layout:
  - Text: "Hartverwarmers is volledig vernieuwd. Ontdek wat er veranderd is."
  - Link: "Lees meer" → `route('whats-new')`, styled as `cta-link`
  - Close button: `×` with hover state, right-aligned
- Alpine.js `x-data` / `x-show` / `x-transition` for smooth dismiss
- Responsive: text and link stack vertically on mobile, close button stays right-aligned

### Homepage Integration

In `home.blade.php`, below the hero section:

```blade
<x-whats-new-banner />
<livewire:onboarding-banner />
```

Only one will ever render based on user state.

---

## Config

**New file:** `config/hartverwarmers.php`

```php
return [
    'launch_date' => env('HARTVERWARMERS_LAUNCH_DATE', '2026-03-19'),
];
```

---

## Testing

### Feature Tests

**Whats-new page:**
- `GET /wat-is-er-nieuw` returns 200
- Response contains key heading text

**Banner visibility:**
- Guest sees whats-new banner on homepage
- Existing user (created before launch date) sees whats-new banner
- New user (created on or after launch date) does NOT see whats-new banner
- New user sees onboarding banner instead

**Onboarding banner (updated):**
- Existing user (created before launch date) does NOT see onboarding banner

### Edge Cases

- User created exactly on launch date → treated as new user (gets onboarding)
- localStorage unavailable (private browsing) → banner shows every visit; acceptable for temporary feature
- Banner dismissed via localStorage → persists across auth state changes

---

## Removal Plan

When the campaign is over:
1. Delete `resources/views/components/whats-new-banner.blade.php`
2. Remove `<x-whats-new-banner />` from `home.blade.php`
3. Remove the `created_at` check from `OnboardingBanner.php`
4. Optionally remove the route from `web.php` and the view file
5. Config value can stay inert
