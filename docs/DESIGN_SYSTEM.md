# Hartverwarmers Design System

> Component usage rules for consistent UI across the platform.
> Generated from the Design System Rule Editor playground.

## Colors

### Primary

**Class:** `--color-primary`

**When to use:** The main brand color. Use for primary CTAs, active states, links, and brand-identifying elements.

**When NOT to use:** Don't use for large background fills — it's too intense. Use --color-bg-accent-light or --color-bg-cream for tinted backgrounds.

### Primary Hover

**Class:** `--color-primary-hover`

**When to use:** Hover/pressed state for primary-colored elements. Always pair with --color-primary as the base state.

**When NOT to use:** Never use as a standalone color — only as a hover/active variant of primary.

### Secondary (Teal)

**Class:** `--color-secondary`

**When to use:** Accent color for illustrative/decorative elements, secondary badges, or visual variety alongside primary.

**When NOT to use:** Don't use for CTAs or interactive elements — primary orange owns the action color.

### Accent Yellow

**Class:** `--color-accent-yellow`

**When to use:** Warm accent for decorative elements, highlights, or DIAMANT-related visual accents.

**When NOT to use:** Don't use for text (poor contrast on light backgrounds) or primary CTAs.

### Accent Purple

**Class:** `--color-accent-purple`

**When to use:** Soft accent for decorative variety. Use sparingly alongside teal and yellow for visual richness.

**When NOT to use:** Don't use as a primary action color. Keep it decorative only.

### Background Subtle

**Class:** `--color-bg-subtle`

**When to use:** Warm neutral background for alternating sections, inactive states, and subtle containers (e.g. question rows).

**When NOT to use:** Don't use for card backgrounds — cards should be white. This is for full-width section bands or inset containers.

### Background Cream

**Class:** `--color-bg-cream`

**When to use:** Warmest background tone. Use for hero sections or full-page backgrounds to create a cozy, warm feel.

**When NOT to use:** Don't nest cream backgrounds inside cream sections — use white or subtle for contrast.

## Typography

**Fonts:** Headings use **Aleo** (slab-serif, weight 700 bold) via `font-heading`. Body text uses **Fira Sans** (sans-serif, weights 300–700) via `font-body`. Both loaded from Bunny Fonts.

**Card titles:** `<flux:heading>` components don't render as `<h1>`–`<h3>`, so they don't inherit heading styles automatically. Always add `class="font-heading font-bold"` to card title headings.

**Nav brand name:** Uses `font-heading font-bold` explicitly to display "hartverwarmers" in Aleo.

### Heading 1

**Class:** `h1`

**When to use:** Main page title. One per page only. Use inside .intro-block for index pages, or standalone for detail page heroes. Renders in Aleo Bold (700) automatically.

**When NOT to use:** Never use more than one h1 per page. Don't use for section headings within a page — use h2 or h3.

### Heading 2

**Class:** `h2`

**When to use:** Major section heading within a page. Use for top-level content divisions (e.g. "Initiatieven", "Doelen"). Renders in Aleo Bold (700) automatically.

**When NOT to use:** Don't use for card titles or minor sub-sections — use h3 or h4 instead.

### Heading 3

**Class:** `h3`

**When to use:** Sub-section heading. Use for content groupings within a section, or as titles for card groups. Renders in Aleo Bold (700) automatically.

**When NOT to use:** Don't use inside cards — card titles should use h4 or .card-title.

### Heading 4

**Class:** `h4`

**When to use:** Smallest heading level. Use for card titles, sidebar section headings, and minor content labels. Renders in Aleo Bold (700) automatically.

**When NOT to use:** Don't use for page-level or section-level headings — use h2 or h3.

### Section Label

**Class:** `.section-label`

**When to use:** Eyebrow label that sits above a heading to categorize the section. Always pair with an H2 below it. Use the orange primary color.

**Content system:** The accent label is a short **keyword** (1–2 words) that names the category or topic. The H2 below it is **outcome-driven** — it tells the reader what they'll get, not just what the section is. Example: accent "Gidsen" + H2 "Ontdek bewezen aanpakken voor je team".

**HTML pattern:**

```html
<span class="section-label">Keyword</span>
<h2 class="mb-6">Outcome-driven benefit title</h2>
```

**When NOT to use:** Don't use standalone without a heading. Don't use for navigation or metadata — only for section categorization.

### Text Meta

**Class:** `.text-meta`

**When to use:** Light, muted text for metadata (dates, counts, author names, secondary information).

**When NOT to use:** Don't use for primary content or actionable text — it's deliberately de-emphasized.

### Meta Group

**Class:** `.meta-group` + `.meta-item`

**When to use:** Icon + text metadata rows showing counts, durations, authors, and other secondary information. Each `.meta-item` pairs an SVG icon with a text label. The SVG inherits size (16px) and stroke-width (1.5) from the class.

**HTML pattern:**

```html
<div class="meta-group">
    <span class="meta-item">
        <svg ...><!-- icon --></svg>
        Label text
    </span>
</div>
```

**When NOT to use:** Don't use for primary content or navigation. Don't add extra size/stroke classes to the SVG — the component handles it.

### Intro Block

**Class:** `.intro-block`

**When to use:** Centered hero/intro area at top of index pages. Contains h1 + descriptive paragraph. Max-width 800px keeps text readable.

**When NOT to use:** Don't use on detail/show pages — those have their own hero layouts. Don't put multiple intro-blocks on one page.

## Buttons

### Pill Button

**Class:** `.btn-pill`

**When to use:** /

**When NOT to use:** Don't use.

### Flux Button (Primary)

**Class:** `<flux:button variant="primary">`

**When to use:** Standard form submit button. Use for all form submissions (login, register, save profile, post comment). Squared corners signal "system action" vs pill's "brand CTA".

**When NOT to use:** Don't use for page-level marketing CTAs — use .btn-pill. Don't use where a text link (cta-link) would suffice.

### Flux Button (Ghost)

**Class:** `<flux:button variant="ghost">`

**When to use:** Low-emphasis action button. Use for secondary actions: cancel, dismiss, toggle filters, toolbar icons.

**When NOT to use:** Don't use for primary actions — too subtle. Don't use for CTAs that need visual prominence.

### Flux Button (Danger)

**Class:** `<flux:button variant="danger">`

**When to use:** Destructive action button. Use only for irreversible or dangerous actions: delete account, remove content.

**When NOT to use:** Don't use for non-destructive actions. Always pair with a confirmation dialog.

### Flux Button (Small)

**Class:** `<flux:button size="sm">`

**When to use:** Compact button for inline or tight-space contexts: table actions, card footers, tag chips with actions.

**When NOT to use:** Don't use for page-level CTAs — too small to notice. Use regular size for standalone buttons.

### Flux Button Variants Quick Reference

| Variant | Markup | Use for |
|---------|--------|---------|
| **Primary** | `<flux:button variant="primary">` | Main form actions (save, submit, confirm) |
| **Filled** | `<flux:button variant="filled">` | Secondary actions on list rows and cards (e.g. "Bewerk") — use instead of custom outlined buttons |
| **Ghost** | `<flux:button variant="ghost">` | Low-emphasis actions (cancel, dismiss, filters) |
| **Danger** | `<flux:button variant="danger">` | Destructive actions (delete, remove) — always pair with confirmation |
| **Primary sm** | `<flux:button variant="primary" size="sm">` | Compact primary actions (table rows, card footers) |
| **Filled sm** | `<flux:button variant="filled" size="sm">` | Compact secondary actions on list rows (e.g. "Bewerk" on mijn-fiches) |

All variants accept an `icon` prop: `<flux:button variant="filled" icon="pencil-square">Bewerk</flux:button>`

## Links

### CTA Link

**Class:** `.cta-link`

**When to use:** Text link with animated arrow (→). The default way to say "see more" or "read more". Use inside cards, at end of sections, or as secondary navigation prompts.

**When NOT to use:** Don't use for form actions or button-like interactions. Don't use for navigation menu items (those are plain links). Don't add your own arrow — the ::after pseudo-element adds it automatically.

### Nav Link

**Class:** `Plain <a> / flux:navlist`

**When to use:** Standard navigation link. Use for menus, breadcrumbs, footer links, and any navigational context.

**When NOT to use:** Don't add arrows or extra decoration — keep it clean. Use .cta-link instead if you want a "read more" prompt.

## Badges

### DIAMANT Badge

**Class:** `.diamant-badge`

**When to use:** Large circular badge showing a single DIAMANT letter (D, I, A, M, A, N, T). Use on goal index/overview pages where goals are displayed prominently.

**When NOT to use:** Don't use inline with text — too large. Use .diamant-badge-sm for compact contexts.

### DIAMANT Badge (Small)

**Class:** `.diamant-badge-sm`

**When to use:** Compact DIAMANT letter badge. Use inline alongside initiative titles or in compact goal references within cards.

**When NOT to use:** Don't use on goal overview pages where the large badge gives better visual hierarchy.

### DIAMANT Badge (Inactive)

**Class:** `.diamant-badge-sm-inactive`

**When to use:** Greyed-out DIAMANT letter badge for goals NOT associated with the current initiative. Shows the full DIAMANT spectrum with active/inactive distinction.

**When NOT to use:** Don't use standalone — always display alongside active .diamant-badge-sm badges to create the full D-I-A-M-A-N-T row.

### Diamond Indicator

**Class:** `.diamond-indicator`

**When to use:** Pill-shaped badge with light orange background showing DIAMANT goal association. Displays goal name as readable text (not just a letter). Use in detail pages where context is needed.

**When NOT to use:** Don't use in compact lists — too wide. Use .diamant-badge-sm for letter-only indicators.

### Flux Badge

**Class:** `<flux:badge>`

**When to use:** General-purpose tag/status badge. Use for content tags (themes, seasons, target groups), status indicators, and filter chips.

**When NOT to use:** Don't use for DIAMANT goals — those have their own dedicated badge components (.diamant-badge variants).

## Form Labels

### Field Tag

**Class:** `.field-tag`

**When to use:** Small inline label placed next to form field labels to indicate status or requirements (e.g. "Verplicht", "Optioneel", "Nieuw"). Cream background with text-secondary color. Place inside `<flux:label>` after the label text.

**HTML pattern:**

```html
<flux:label class="text-base font-heading font-bold">
    Titel <span class="field-tag ml-1">Verplicht</span>
</flux:label>
```

**When NOT to use:** Don't use for large status indicators or badges — use `<flux:badge>` instead. Don't use outside of form contexts.

### Form Section Spacing

**Class:** `space-y-8` (32px)

**When to use:** Consistent vertical spacing between form sections/field groups in wizard and multi-section forms. Use `space-y-8` on the wrapping container instead of `<hr>` dividers between sections.

**When NOT to use:** Don't use `<hr>` elements between form sections — rely on generous whitespace (32px) for visual separation. Don't use smaller spacing like `space-y-4` or `space-y-6` for top-level form sections.

### Wizard Form Footer

**Class:** `.wizard-form-footer`

**When to use:** Action bar at the bottom of each wizard step. Provides cream background, top border, and negative margins to bleed into the container padding — creating a full-width footer that visually anchors the form.

**HTML pattern:**

```html
<div class="wizard-form-footer mt-8 flex justify-between">
    <flux:button variant="ghost" icon="arrow-left">Vorige</flux:button>
    <flux:button variant="primary" icon-trailing="arrow-right">Volgende</flux:button>
</div>
```

**When NOT to use:** Don't use for inline button groups within the form content. Only use at the very bottom of a wizard step.

## Cards

### Fiche List Item

**Class:** `.fiche-list-item` + `.fiche-list-icon` + `.fiche-list-kudos` + `.fiche-list-expand`

**When to use:** Compact card-style rows listing elaborations on initiative detail pages. Each row is a bordered card with file-text icon (tint background), two-row layout (title + author), outline heart kudos, and hover lift shadow.

**When NOT to use:** Don't use for full fiche previews — use `<x-fiche-card>` instead. Don't use outside initiative detail pages.

### Content Card

**Class:** `.content-card`

**When to use:** Clickable content card with hover lift effect. Use for browsable content grids: goal cards, initiative cards, theme cards. Always wrap in an <a> tag.

**When NOT to use:** Don't use for static/non-clickable content — use flux:card instead. Don't nest interactive elements inside.

### Flux Card

**Class:** `<flux:card>`

**When to use:** Static content container. Use for form wrappers, profile sections, settings panels — any grouped content that is NOT clickable as a unit.

**When NOT to use:** Don't use for browsable content grids — use .content-card for clickable cards with hover effects.

### Quote Card

**Class:** `.quote-card`

**When to use:** Full-orange card for displaying inspirational quotes on goal detail pages. White text on orange background.

**When NOT to use:** Don't use for general content — only for quotes/testimonials. One per page maximum.

### Practice Example (Zigzag)

**Pattern:** `grid grid-cols-1 md:grid-cols-2 gap-8 items-center` with alternating `md:order-1`/`md:order-2`

**When to use:** Zigzag editorial layout for practice examples on goal detail pages. Large rectangular images (aspect-4/3, object-contain, white bg) alternate left/right across two examples. Centered section header above. On mobile: stacks vertically (image on top, text below). Uses `h3` for activity-focused headings.

**When NOT to use:** Don't use outside of goal detail pages. Don't show more than 2 examples.

## Layout

### Reflection Question

**Pattern:** `flex items-start gap-4` with `.question-badge` + lightweight text

**When to use:** Display reflection questions on goal detail pages. Text is `text-xl font-light` without background blocks. Stack vertically with `space-y-6`.

**When NOT to use:** Don't use for FAQ-style content with expandable answers — this is for simple question display only.

### Question Badge

**Class:** `.question-badge`

**When to use:** Circle badge with diamond icon inside a reflection question row. Light orange background with orange text.

**When NOT to use:** Don't use standalone — always within a reflection question flex container.

### Page Sections

**Pattern:** `<section>` + `<div class="max-w-6xl mx-auto px-6 py-16">`

**When to use:** Full-width page layout for Tools & Inspiratie pages and other content-heavy pages. Creates a consistent visual rhythm with alternating backgrounds and clear section boundaries.

**Structure:**
1. **Hero section** — `bg-[var(--color-bg-cream)]` with section-label + h1 + description
2. **Content sections** — `bg-white` (or `bg-[var(--color-bg-base)]`) with h2 + content
3. **HR separators** — `<hr class="border-[var(--color-border-light)]">` between sections

**Requirements:**
- Layout must use `:full-width="true"` on `<x-layout>` so sections span the full viewport
- Each `<section>` contains a `<div class="max-w-6xl mx-auto px-6 py-16">` for consistent inner padding
- Use `py-16` (64px) for all sections including the hero

**HTML pattern:**

```html
<x-layout title="Page Title" :full-width="true">
    {{-- Hero --}}
    <section class="bg-[var(--color-bg-cream)]">
        <div class="max-w-6xl mx-auto px-6 py-16">
            <span class="section-label section-label-hero">Category</span>
            <h1 class="text-5xl mt-1">Page Title</h1>
            <p class="text-2xl text-[var(--color-text-secondary)] mt-4">Description</p>
        </div>
    </section>

    <hr class="border-[var(--color-border-light)]">

    {{-- Content Section --}}
    <section>
        <div class="max-w-6xl mx-auto px-6 py-16">
            <h2>Section Title</h2>
            <!-- content -->
        </div>
    </section>
</x-layout>
```

**When NOT to use:** Don't use for pages that already have clear background alternation (like index pages with cream/white bands). Don't nest sections — keep them flat siblings.

## Quick Reference

| Component | Class | Use for | Don't use for |
|-----------|-------|---------|---------------|
| Primary | `--color-primary` | The main brand color | Don't use for large background fills — it's too intense |
| Primary Hover | `--color-primary-hover` | Hover/pressed state for primary-colored elements | Never use as a standalone color — only as a hover/active variant of primary |
| Secondary (Teal) | `--color-secondary` | Accent color for illustrative/decorative elements, secondary badges, or visual variety alongside primary | Don't use for CTAs or interactive elements — primary orange owns the action color |
| Accent Yellow | `--color-accent-yellow` | Warm accent for decorative elements, highlights, or DIAMANT-related visual accents | Don't use for text (poor contrast on light backgrounds) or primary CTAs |
| Accent Purple | `--color-accent-purple` | Soft accent for decorative variety | Don't use as a primary action color |
| Background Subtle | `--color-bg-subtle` | Warm neutral background for alternating sections, inactive states, and subtle containers (e | Don't use for card backgrounds — cards should be white |
| Background Cream | `--color-bg-cream` | Warmest background tone | Don't nest cream backgrounds inside cream sections — use white or subtle for contrast |
| Heading 1 | `h1` | Main page title | Never use more than one h1 per page |
| Heading 2 | `h2` | Major section heading within a page | Don't use for card titles or minor sub-sections — use h3 or h4 instead |
| Heading 3 | `h3` | Sub-section heading | Don't use inside cards — card titles should use h4 or  |
| Heading 4 | `h4` | Smallest heading level | Don't use for page-level or section-level headings — use h2 or h3 |
| Section Label | `.section-label` | Eyebrow keyword above an outcome-driven H2 | Don't use standalone without a heading |
| Text Meta | `.text-meta` | Light, muted text for metadata (dates, counts, author names, secondary information) | Don't use for primary content or actionable text — it's deliberately de-emphasized |
| Meta Group | `.meta-group` + `.meta-item` | Icon + text metadata rows (counts, durations, authors) | Don't use for primary content or navigation |
| Intro Block | `.intro-block` | Centered hero/intro area at top of index pages | Don't use on detail/show pages — those have their own hero layouts |
| Pill Button | `.btn-pill` | / | Don't use |
| Flux Button (Primary) | `<flux:button variant="primary">` | Standard form submit button | Don't use for page-level marketing CTAs — use  |
| Flux Button (Ghost) | `<flux:button variant="ghost">` | Low-emphasis action button | Don't use for primary actions — too subtle |
| Flux Button (Danger) | `<flux:button variant="danger">` | Destructive action button | Don't use for non-destructive actions |
| Flux Button (Small) | `<flux:button size="sm">` | Compact button for inline or tight-space contexts: table actions, card footers, tag chips with actions | Don't use for page-level CTAs — too small to notice |
| CTA Link | `.cta-link` | Text link with animated arrow (→) | Don't use for form actions or button-like interactions |
| Nav Link | `Plain <a> / flux:navlist` | Standard navigation link | Don't add arrows or extra decoration — keep it clean |
| DIAMANT Badge | `.diamant-badge` | Large circular badge showing a single DIAMANT letter (D, I, A, M, A, N, T) | Don't use inline with text — too large |
| DIAMANT Badge (Small) | `.diamant-badge-sm` | Compact DIAMANT letter badge | Don't use on goal overview pages where the large badge gives better visual hierarchy |
| DIAMANT Badge (Inactive) | `.diamant-badge-sm-inactive` | Greyed-out DIAMANT letter badge for goals NOT associated with the current initiative | Don't use standalone — always display alongside active  |
| Diamond Indicator | `.diamond-indicator` | Pill-shaped badge with light orange background showing DIAMANT goal association | Don't use in compact lists — too wide |
| Flux Badge | `<flux:badge>` | General-purpose tag/status badge | Don't use for DIAMANT goals — those have their own dedicated badge components ( |
| Field Tag | `.field-tag` | Small inline label next to form field labels (e.g. "Verplicht", "Optioneel") | Don't use for large status indicators — use `<flux:badge>` |
| Fiche List Item | `.fiche-list-item` + `.fiche-list-icon` | Compact card-style elaboration rows on initiative detail pages | Don't use for full fiche previews — use `<x-fiche-card>` |
| Content Card | `.content-card` | Clickable content card with hover lift effect | Don't use for static/non-clickable content — use flux:card instead |
| Flux Card | `<flux:card>` | Static content container | Don't use for browsable content grids — use  |
| Quote Card | `.quote-card` | Full-orange card for displaying inspirational quotes on goal detail pages | Don't use for general content — only for quotes/testimonials |
| Practice Example | Zigzag grid + `md:order-*` | Editorial zigzag layout with large images on goal detail pages | Don't show more than 2 examples |
| Reflection Question | `flex items-start gap-4` + `.question-badge` | Lightweight text questions on goal detail pages | Don't use for FAQ-style content |
| Question Badge | `.question-badge` | Circle badge with diamond icon in reflection questions | Don't use standalone |
| Page Sections | `<section>` + `max-w-6xl mx-auto px-6 py-16` | Full-width page layout with cream hero + white content sections + HR separators | Don't use for pages with clear background alternation already |
