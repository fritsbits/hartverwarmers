# About Page — Technical Design Spec

## Overview

Full-width narrative page at `/over-ons` (route: `about`). Six content blocks following the UX briefing's narrative arc: opening → community → foundation → story → personal commitment → call to action.

Currently a Blade stub with placeholder text. Will remain a **Blade view** (not Livewire) with one **Livewire component** for the inline contact form in the CTA section.

**SEO:** Pass `description` attribute to `<x-layout>`: "Hartverwarmers is een gratis platform van en voor activiteitenbegeleiders in de ouderenzorg. Ontdek het verhaal, de community en het DIAMANT-model."

## Architecture

### Routing

The existing `Route::view('/over-ons', 'about')->name('about')` in `routes/web.php` stays unchanged. No controller needed — View Composers work with `Route::view`.

### View: `resources/views/about.blade.php`

Full-width layout (`<x-layout :full-width="true">`), using the page section pattern: `<section>` + `<div class="max-w-4xl mx-auto px-6 py-16">` with `<hr>` separators. Narrower `max-w-4xl` (deviation from the design system's `max-w-6xl`) suits the narrative/editorial nature — no sidebar, prose-focused. The existing stub already uses `max-w-4xl`.

**Exception:** Block 2 (stats grid) uses `max-w-5xl` so the 4-column stat layout doesn't feel cramped.

### Dynamic stats

Add a **View Composer** (`AboutComposer`) registered for the `about` view, providing cached stats:

- `fiches_count` — `Fiche::count()`
- `contributors_count` — `User::whereHas('fiches')->count()`
- `users_count` — `User::count()`

Note: The 4th stat card displays "Gratis" (branding, not a dynamic stat), so `organisations_count` is not needed here.

Cache for 1 hour (same as footer). Available as `$aboutStats` in the view.

**Why a separate composer instead of reusing FooterComposer?** The about page needs `users_count` which the footer doesn't. Adding it to FooterComposer would run an extra query on every page for a stat only used here.

### Contact form: Livewire component `SupportContactForm`

**Toggle architecture:** The parent Blade view wraps the CTA button and the Livewire component in a single Alpine `x-data="{ open: false }"` scope. The button sets `@click="open = !open"`. The Livewire component sits inside a `<div x-show="open" x-collapse>`. This keeps visibility state in the parent — the Livewire component only handles form logic. Reference pattern: `resources/views/initiatives/show.blade.php` uses a similar `x-collapse` approach.

**Fields:** name (required), email (required|email), message (required|max:2000)

**Validation:** Inline `#[Validate]` attributes on component properties. This is Livewire's idiomatic approach — the project's Form Request rule applies to controllers, not Livewire components.

**Rate limiting:** Throttle the `send` action to 3 attempts per 10 minutes per IP using Laravel's `RateLimiter`. Show a friendly Dutch message when throttled.

**Action:** Sends a `Mailable` to the configured support address. Shows success message inline after send. No database storage — email only.

### Mail configuration

Add to `config/mail.php`:
```php
'support_address' => env('SUPPORT_ADDRESS', 'frederik.vincx@gmail.com'),
```

Add `SUPPORT_ADDRESS=` to `.env.example`.

### Assets to add

| Asset | Source | Destination |
|---|---|---|
| Book cover | Download from Standaard Boekhandel CDN | `public/img/covers/hartverwarmers.jpg` |
| Lancering photo 1 | Desktop screenshot (virtueel museumbezoek) | `public/img/about/lancering-activiteit.jpg` |
| Lancering photo 2 | Desktop screenshot (boekvoorstelling) | `public/img/about/lancering-boek.jpg` |
| Maite photo | Already exists | `/img/wonen-en-leven/maitemallentjer.jpg` |
| Nadine photo | Already exists | `/img/wonen-en-leven/nadinepraet.jpg` |

### YouTube embeds

Two videos in block 5. Use privacy-enhanced `youtube-nocookie.com` embeds with `loading="lazy"` and responsive aspect-ratio wrapper (`aspect-video`).

- `https://www.youtube.com/watch?v=k8zetWJ-Pro`
- `https://www.youtube.com/watch?v=TeNR4O0TJRc`

### Share button (block 6, tertiary CTA)

Alpine component using Web Share API with clipboard fallback. Wrapped in try/catch to handle denied share dialogs and clipboard permission issues:

```js
{
    copied: false,
    async share() {
        const data = { title: 'Hartverwarmers', url: window.location.origin };
        try {
            if (navigator.share) {
                await navigator.share(data);
            } else {
                await navigator.clipboard.writeText(data.url);
                this.copied = true;
                setTimeout(() => this.copied = false, 2000);
            }
        } catch (e) {
            // User cancelled share dialog or clipboard denied — no action needed
        }
    }
}
```

Button text toggles to "Link gekopieerd!" on clipboard fallback.

## Block-by-block structure

### Block 1 — Hero (cream bg)
- `section-label section-label-hero`: "OVER HARTVERWARMERS"
- `h1`: Two-line headline (line break with `<br>`)
- Subtext paragraph, `text-2xl font-light`

### Block 2 — Community (white bg, `max-w-5xl`)
- `section-label` + `h2`
- Body paragraph
- 2×2 grid of stat cards (responsive: `grid-cols-2 md:grid-cols-4`). Each stat: large number (`text-4xl font-heading font-bold text-[var(--color-primary)]`) + label below
- `cta-link` to `/bijdragers`

### Block 3 — Foundation (subtle bg)
- `section-label` + `h2`
- Body paragraph mentioning DIAMANT model
- Maite & Nadine: reuse `photo-polaroid` pattern from goals page (slight rotation, figcaption with name + role)
- `cta-link` to `/doelen`
- Book mention: cover image (tilted with `transform: rotate()`, `shadow-md`) + title + publisher + year. `cta-link` to Standaard Boekhandel external link

### Block 4 — Story (white bg)
- `section-label` + `h2`
- Two prose paragraphs. No images — text carries this block

### Block 5 — Personal commitment (cream bg)
- `section-label` + `h2`: Two-line headline
- First-person prose from Frederik
- Cost breakdown as a simple prose sentence listing what he pays for (no bullet list — keeps the personal tone)
- Two lancering photos in a `grid grid-cols-1 md:grid-cols-2 gap-6` with `rounded-xl shadow-lg`
- Two YouTube embeds below photos, same grid layout, `aspect-video rounded-xl overflow-hidden`

### Block 6 — Call to action (white bg)
Three sub-sections stacked vertically:

1. **Primary — Steun** (full-width cream card with padding):
   - `section-label` + `h2` + body
   - `<flux:button variant="primary">` with `@click="open = !open"` to toggle contact form
   - `<div x-show="open" x-collapse>` wrapping `<livewire:support-contact-form />`

2. **Secondary — Bijdragen** (inline):
   - `h3` + body + `cta-link` to initiative creation route

3. **Tertiary — Delen** (inline):
   - `h3` + body + share button (Alpine component)

## Files to create/modify

| Action | File |
|---|---|
| **Modify** | `resources/views/about.blade.php` — full page implementation |
| **Create** | `app/View/Composers/AboutComposer.php` — cached stats |
| **Register** | `app/Providers/AppServiceProvider.php` — register AboutComposer |
| **Create** | `app/Livewire/SupportContactForm.php` — contact form component |
| **Create** | `resources/views/livewire/support-contact-form.blade.php` — form template |
| **Create** | `app/Mail/SupportMessage.php` — mailable |
| **Create** | `resources/views/mail/support-message.blade.php` — email template |
| **Modify** | `config/mail.php` — add `support_address` key |
| **Modify** | `.env.example` — add `SUPPORT_ADDRESS` |
| **Add** | `public/img/about/lancering-activiteit.jpg` — photo |
| **Add** | `public/img/about/lancering-boek.jpg` — photo |
| **Add** | `public/img/covers/hartverwarmers.jpg` — book cover |
| **Create** | `tests/Feature/AboutPageTest.php` — page + form + mailable tests |

## Testing

- Page loads with 200 status and contains expected section labels
- Dynamic stats are present and numeric
- Contact form validates required fields (name, email, message)
- Contact form validates email format
- Contact form sends `SupportMessage` mailable (Mail::fake assertion)
- Mailable envelope has correct subject and recipient
- Rate limiting: 4th submission within 10 minutes returns throttle error
- Share button markup renders

## Out of scope

- Donation payment integration (contact form replaces this)
- User testimonials
- Dark mode styling
- Analytics/tracking
