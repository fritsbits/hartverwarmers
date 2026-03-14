# About Page — Technical Design Spec

## Overview

Full-width narrative page at `/over-ons` (route: `about`). Six content blocks following the UX briefing's narrative arc: opening → community → foundation → story → personal commitment → call to action.

Currently a Blade stub with placeholder text. Will remain a **Blade view** (not Livewire) with one **Livewire component** for the inline contact form in the CTA section.

## Architecture

### View: `resources/views/about.blade.php`

Full-width layout (`<x-layout :full-width="true">`), using the standard page section pattern: `<section>` + `<div class="max-w-4xl mx-auto px-6 py-16">` with `<hr>` separators between sections. Narrower max-width (`max-w-4xl`) suits the narrative/editorial nature — no sidebar needed.

### Dynamic stats

Reuse the `FooterComposer` pattern. Add a **View Composer** (`AboutComposer`) registered for the `about` view, providing cached stats:

- `fiches_count` — `Fiche::count()`
- `contributors_count` — `User::whereHas('fiches')->count()`
- `users_count` — `User::count()`
- `organisations_count` — reuse footer query

Cache for 1 hour (same as footer). Available as `$aboutStats` in the view.

**Why a separate composer instead of reusing FooterComposer?** The about page needs `users_count` which the footer doesn't. Adding it to FooterComposer would run an extra query on every page for a stat only used here.

### Contact form: Livewire component `SupportContactForm`

Progressive disclosure: hidden by default, toggled open with Alpine `x-show`/`x-collapse` on button click.

**Fields:** name (required), email (required|email), message (required|max:2000)

**Action:** Sends a `Mailable` to Frederik's email (configurable via `config('mail.support_address')` or fallback to admin). Shows success message inline after send. No database storage — email only.

**Form Request:** Inline validation in the Livewire component is acceptable here since it's a simple 3-field form with no reuse.

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

Alpine component using Web Share API with clipboard fallback:

```js
{
    async share() {
        const data = { title: 'Hartverwarmers', url: window.location.origin };
        if (navigator.share) {
            await navigator.share(data);
        } else {
            await navigator.clipboard.writeText(data.url);
            this.copied = true;
            setTimeout(() => this.copied = false, 2000);
        }
    }
}
```

Button text toggles to "Link gekopieerd!" on clipboard fallback.

## Block-by-block structure

### Block 1 — Hero (cream bg)
- `section-label-hero`: "OVER HARTVERWARMERS"
- `h1`: Two-line headline (line break with `<br>`)
- Subtext paragraph, `text-2xl font-light`

### Block 2 — Community (white bg)
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
   - `<flux:button variant="primary">` to toggle contact form
   - `<livewire:support-contact-form />` below, hidden by default with Alpine `x-collapse`

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
| **Add** | `public/img/about/lancering-activiteit.jpg` — photo |
| **Add** | `public/img/about/lancering-boek.jpg` — photo |
| **Add** | `public/img/covers/hartverwarmers.jpg` — book cover |
| **Create** | `tests/Feature/AboutPageTest.php` — page loads, stats display, form works |

## Testing

- Page loads with 200 status
- Stats are present and non-zero
- Contact form renders hidden, toggles visible
- Contact form validates required fields
- Contact form sends email (Mail::fake assertion)
- Share button renders

## Out of scope

- Donation payment integration (contact form replaces this)
- User testimonials
- Dark mode styling
- Analytics/tracking
