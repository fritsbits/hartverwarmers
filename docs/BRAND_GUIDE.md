# Hartverwarmers — Brand Guide

A complete reference for recreating the Hartverwarmers look and feel across presentations, websites, print, and digital assets. Source of truth: `resources/css/app.css`, `tailwind.config.js`, and the components in `resources/views/components/`.

---

## Brand Identity

**Name:** Hartverwarmers (Dutch: "heart warmers")
**Tagline:** *Laat je bewoners schitteren* ("Let your residents shine") — used as homepage hero and meta title.
**Domain:** A platform for activity coordinators in Vlaams woonzorgcentra to share practical initiative elaborations, organized around the DIAMANT pedagogical model.
**Language:** Vlaams Nederlands throughout (not Holland Dutch).
**Tone:** Warm, practical, encouraging. Celebrates capability and joy — never limitation. *Gezellig*, never clinical.

---

## Color Palette

### Primary

| Name | Hex | CSS variable | Use |
|---|---|---|---|
| **Coral orange** | `#E8764B` | `--color-primary` | Main brand color. CTAs, links, active states, section labels |
| **Coral hover** | `#D4683F` | `--color-primary-hover` | Hover/pressed states |

### Decorative accents

| Name | Hex | CSS variable | Use |
|---|---|---|---|
| **Teal** | `#4CB7C5` | `--color-secondary` / `--color-accent-blue` | Cool contrast accent. Decorative, illustrative |
| **Golden yellow** | `#F4C44E` | `--color-yellow` / `--color-accent-yellow` | Bright, cheerful decorative accent |
| **Muted purple** | `#B57BB3` | `--color-accent-purple` | Soft, unexpected accent. Used sparingly |

### Initiative colors (deterministic per contributor)

Six rotation colors used by `.initiative-color-0` through `.initiative-color-5` for contributor-specific accent in initiative section headers (color + matching tinted background).

| # | Hex | Background |
|---|---|---|
| 0 | `#E8764B` (coral) | `#FDF3EE` |
| 1 | `#4CB7C5` (teal) | `#EEF8FA` |
| 2 | `#F4C44E` (yellow) | `#FEF9EC` |
| 3 | `#B57BB3` (purple) | `#F6EEF6` |
| 4 | `#D4837B` (warm pink) | `#FAF0EE` |
| 5 | `#7BAF8E` (sage green) | `#EFF6F1` |

The four primary accents (coral, teal, yellow, purple) are the dominant saturated colors. Pink and sage extend the rotation when more contributors need a unique color.

### Text

| Name | Hex | CSS variable | Use |
|---|---|---|---|
| **Primary text** | `#231E1A` | `--color-text-primary` | Headings, body text |
| **Secondary text** | `#756C65` | `--color-text-secondary` | Metadata, descriptions, muted labels |
| **Tertiary text** | `#C0B5AE` | `--color-text-tertiary` | Meta icons, timestamps, inactive |

### Backgrounds

| Name | Hex | CSS variable | Use |
|---|---|---|---|
| **White** | `#FFFFFF` | `--color-bg-white` / `--color-bg-base` | Default page background, cards |
| **Cream** | `#FEF8F4` | `--color-bg-cream` | Hero sections, warmest tone |
| **Subtle** | `#F5F0EC` | `--color-bg-subtle` | Alternating sections, inactive states |
| **Accent light** | `#FDF3EE` | `--color-bg-accent-light` | Light orange tint, hover states, gem indicators |

### Borders

| Name | Hex | CSS variable | Use |
|---|---|---|---|
| **Border light** | `#EBE4DE` | `--color-border-light` | Default borders, separators |
| **Border hover** | `#DDD5CD` | `--color-border-hover` | Hover state for bordered elements |

### Warm neutral scale

All grays are warm-tinted (hue ~24°, toward orange/cream). Both `gray-*` and `zinc-*` Tailwind scales are overridden so all Flux/Tailwind components inherit the warm palette.

| Level | Hex |
|---|---|
| 50 | `#FDFAF8` |
| 100 | `#F6F2EE` |
| 200 | `#EBE5E0` |
| 300 | `#DBD4CD` |
| 400 | `#AEA59C` |
| 500 | `#7E756D` |
| 600 | `#5D564F` |
| 700 | `#48423C` |
| 800 | `#2E2823` |
| 900 | `#1F1B17` |
| 950 | `#100D0B` |

### Warm red (destructive states)

Standard `red-*` Tailwind scale is replaced with a warmer variant (hue ~8°) that harmonizes with the orange palette.

| Level | Hex |
|---|---|
| 50 | `#fef3f1` |
| 100 | `#fee5e1` |
| 200 | `#fecfc8` |
| 300 | `#fcada1` |
| 400 | `#f87e6c` |
| 500 | `#ee5540` |
| 600 | `#dc3a26` |
| 700 | `#b92d1c` |
| 800 | `#99291b` |
| 900 | `#7f291d` |
| 950 | `#45110a` |

---

## Typography

### Font families

| Role | Font | Type | Weight(s) | Fallback |
|---|---|---|---|---|
| **Headings** | **Aleo** | Slab-serif | 700 (bold only) | system-ui, serif |
| **Body** | **Fira Sans** | Sans-serif | 300, 400, 500, 600, 700 | system-ui, sans-serif |
| **Handwritten quotes** | **Nanum Pen Script** | Cursive | 400 | cursive |

Loaded from **Bunny Fonts** (privacy-friendly, GDPR-compliant — not Google Fonts).

### Type scale

| Level | Size (desktop) | Size (mobile) | Line-height | Font | Weight |
|---|---|---|---|---|---|
| H1 | 40px (`2.5rem`) | 32px (`2rem`) | 1.25 | Aleo | 700 |
| H2 | 32px (`2rem`) | 24px (`1.5rem`) | 1.25 | Aleo | 700 |
| H3 | 24px (`1.5rem`) | — | 1.25 | Aleo | 700 |
| H4 | 20px (`1.25rem`) | — | 1.25 | Aleo | 700 |
| Body | 16px (`1rem`) | — | 1.6 | Fira Sans | 400 |
| Small | 14px (`0.875rem`) | — | 1.6 | Fira Sans | 400 |
| Tiny | 12px (`0.75rem`) | — | 1.6 | Fira Sans | 400 |

Mobile breakpoint at `max-width: 768px` reduces H1/H2 only.

### Form text

Flux UI form fields are bumped from 14px to **17px (`1.0625rem`)** for accessibility — labels, inputs, textareas, selects, and editor content. Helper text is 15px (`0.9375rem`). Wizard forms use 19px (`1.1875rem`) heading-style labels in Aleo bold.

### Weight usage

| Weight | Value | CSS variable | Use |
|---|---|---|---|
| Light | 300 | `--font-weight-light` | Intro text, metadata, decorative text |
| Regular | 400 | `--font-weight-regular` | Default body text |
| Medium | 500 | `--font-weight-medium` | Badge text, indicators |
| Semibold | 600 | `--font-weight-semibold` | Section labels, CTA links, buttons, nav items |
| Bold | 700 | `--font-weight-bold` | All headings, card titles, brand name |
| Black | 900 | `--font-weight-black` | Reserved for special emphasis (rare) |

### Section labels (eyebrow text)

Uppercase, tracking-widest (`letter-spacing: 0.12em`), semibold (600), Fira Sans, primary coral color, 18px (`1.125rem`). Always appears above an H2 heading. Hero variant: 24px (`text-2xl`).

### Card titles

`<flux:heading>` does NOT render as `<h1>`–`<h3>`, so it doesn't inherit heading styles. Always add `class="font-heading font-bold"` to flux card titles.

---

## Spacing

| Token | Value | CSS variable |
|---|---|---|
| xs | 4px (`0.25rem`) | `--space-xs` |
| sm | 8px (`0.5rem`) | `--space-sm` |
| md | 16px (`1rem`) | `--space-md` |
| lg | 24px (`1.5rem`) | `--space-lg` |
| xl | 32px (`2rem`) | `--space-xl` |
| 2xl | 48px (`3rem`) | `--space-2xl` |
| 3xl | 64px (`4rem`) | `--space-3xl` |
| 4xl | 96px (`6rem`) | `--space-4xl` |

Note: do not register `xl`, `2xl`, `3xl`, `4xl` as Tailwind spacing tokens — they conflict with Tailwind v4's `max-w-*` resolution.

---

## Effects

### Border radii

| Use | Value | CSS variable |
|---|---|---|
| Subtle rounding | 6px | `--radius-sm` |
| Cards, containers | 12px | `--radius-md` |
| Pills, badges, avatars | 9999px | `--radius-full` |

Tailwind utilities `rounded-xl` (12px) and `rounded-2xl` (16px) are also used in some components (e.g., search command palette).

### Shadows

Shadows are **warm-tinted** — `rgba(60, 40, 20, ...)`, never neutral gray.

| State | CSS variable | Value |
|---|---|---|
| Default card | `--shadow-card` | `0 2px 8px rgba(60, 40, 20, 0.05)` |
| Hover card | `--shadow-card-hover` | `0 4px 16px rgba(60, 40, 20, 0.10)` |

For paper effects (polaroid, fiche stack, ruled-paper quotes), shadows shift to a warmer brown: `rgba(120, 90, 60, ...)`.

### Hover behavior

Cards lift 2px (`translateY(-2px)`) with shadow deepening and border darkening on hover. CTA text links have an animated right-arrow (`→`) that shifts right on hover and the gap between text and arrow widens from `gap-1` to `gap-2`.

### Transition speed

All interactions: `0.2s ease` (CSS variable `--transition-fast`).

### Animations

- **Confetti** — wizard completion celebration, 20 particles in primary/secondary/yellow falling 2.4s–3.5s
- **Kudos floating heart** — gentle upward drift with fan-out on like
- **Welcome toast** — slide-in/out from top
- **Nudge** — gentle horizontal sway on CTA buttons becoming available
- **Checkmark draw-in** — for completion states

All respect `prefers-reduced-motion`.

---

## The DIAMANT Model

The DIAMANT model is the pedagogical core. Each letter represents a care goal:

| Letter | Goal | Dutch tagline |
|---|---|---|
| **D** | Doen | Zelf aan de slag |
| **I** | Inclusief | Iedereen erbij |
| **A** | Autonomie | De bewoner kiest |
| **M** | Mensgericht | Bij het levensverhaal |
| **A** | Anderen | Samen is meer |
| **N** | Normalisatie | Gewoon waar het kan |
| **T** | Talent | Krachten laten schitteren |

See [research/diamant-model.md](research/diamant-model.md) for the full pedagogical framework.

### The DIAMANT gem icon

A faceted gem-shaped polygon (five-sided: pointed top-left and top-right, wider middle, pointed bottom). The letter sits centered in Aleo bold.

**Shape** (SVG polygon points): `30,0 70,0 100,35 50,100 0,35`

**States:**

| State | Fill | Letter color |
|---|---|---|
| Active | `#E8764B` (coral) | White |
| Inactive | `#F5F0EC` (subtle) | `#B0A89F` (muted) |
| Pronounced (outline only) | none, stroke `--color-primary` width 8 | (used for nav logo) |

**Sizes:** xxs / xs / sm / md / lg.

### DIAMANT Profile

A horizontal row of 7 small gems spelling D-I-A-M-A-N-T. Active goals are coral; inactive are muted. Used to show which goals an initiative covers.

### DIAMANT Pill (`.diamant-pill`)

A compact link badge: small gem icon + goal label, semibold 13px Fira Sans on subtle background. Inactive variant uses 0.55 opacity, transparent bg, muted text. Small variant `.diamant-pill-sm` is 12px with tighter padding.

---

## Iconography

Icons via **Flux UI's `<flux:icon>`** component (Heroicons under the hood — outline default, optional `variant="mini"`). No icon fonts.

- Standard size: 16–20px (`size-4` to `size-5`)
- Stroke width: 1.5
- Color follows surrounding text color, often `--color-text-tertiary` for meta icons
- Meta icon-to-number gap: `gap-1.5`; gap between meta pairs: `gap-4`

Commonly used: `heart`, `book-open`, `lightbulb`, `users`, `calendar`, `document`, `magnifying-glass`, `chevron-down`, `arrow-right`, `bookmark`, `swatch`, `flag`, `arrow-down-tray`.

---

## Layout Patterns

### Page structure

- **Max content width:** `max-w-6xl` (1152px), centered
- **Horizontal padding:** 24px (`px-6`)
- **Section vertical padding:** 64px (`py-16`)
- **Navigation:** Sticky top, 72px height (`h-18`)

### Section rhythm

```
[Cream hero section]
   Section label (coral uppercase)
   H1 heading
   Description text

── border separator ──

[White content section]
   Content...
```

Hero sections use cream (`#FEF8F4`). Content sections alternate on white. Sections separated by light border rules (`#EBE4DE`).

### Card grids

1 column on mobile, 2 on tablet, 3 on desktop. Gap: 24px.

---

## Component Patterns

### Content card (`.content-card`)

White background, 12px border-radius, light border. On hover: lifts 2px, shadow deepens, border darkens.

- **Image:** aspect ratio 16:10, object-fit cover
- **Padding:** 24px
- **Title:** Aleo bold, H4 size
- **Description:** Secondary text color, regular weight

### CTA link (`.cta-link`)

Inline-flex, semibold, primary coral color. Animated right-arrow appears via `::after`. On hover: gap widens, color darkens, arrow translates right.

### Pill button (`.btn-pill`)

Filled coral, white text, fully rounded, semibold. Hover: shadow + darker bg. Use sparingly — one primary CTA per section.

### Fiche card with paper-stack header (`.fiche-card-header`)

The signature visual treatment for fiche previews: 3 stacked landscape papers (3:2 aspect) rotated slightly off-axis, displayed on a `--color-bg-subtle` background. On group hover, the papers settle into a neater stack. Each paper has a warm shadow `rgba(120, 90, 60, 0.18)` and subtle warm border.

### Polaroid photo frame (`.photo-polaroid`)

White frame with extra bottom padding for caption space, rotated -2° on default. Caption uses **Nanum Pen Script** at 1.2rem in secondary text color. Warm shadow.

### Ruled-paper quote (`.quote-paper`)

White paper rotated -2.5° with repeating horizontal lines drawn in 18% primary color. Text inside is **Nanum Pen Script** 1.5rem, line-height 32px (matches the ruling). Decorative `"` mark in Garamond at 7rem, top-left, rotated 180°.

Variants:
- `.quote-paper-lg` — looser ruling for checklist content; supports `.checklist-label` and `.checklist-item` with question-badge.
- `.quote-paper-xl` — large display quote, 3.5rem text.

### Question badge (`.question-badge`)

30×30px circle, accent-light bg, primary coral text in Aleo bold. Used for numbered reflection questions.

### Section label (`.section-label`)

Uppercase, semibold, primary coral, 18px, tracking 0.12em. `.section-label-hero` bumps to 24px.

### Field tag (`.field-tag`)

Tiny inline label (11px uppercase) on subtle bg, secondary text. Used next to form field labels.

### Diamond indicator (`.diamond-indicator`)

Pill with gem icon + label, 15px, accent-light bg, secondary text. Used to surface DIAMANT goals adjacent to content.

### Initiative section (`.initiative-section` + `.initiative-section-header`)

White card, light border, 12px radius. Header has a tinted background and a 4px left border in the contributor's `--initiative-color`.

### Featured badge (`.featured-badge`)

Coral filled pill, white text, 12px. For "uitgelicht" content.

### Similar fiches tip (`.similar-fiches-tip`)

Cream panel, 4px primary left border, lightbulb-style hint. 15px secondary text.

### Materials callout (`.materials-callout`)

Accent-light background, 20%-primary border. For download bundles attached to a fiche.

### Syllabus stepper (`.syllabus-step` + `.syllabus-number`)

Vertical timeline for lesson series. 48px circle with lesson number in Aleo. Connector is dashed `--color-border-light`. States: default / current (coral filled) / done (faded coral with checkmark).

### Fiche list item (`.fiche-list-item`)

Compact row card for initiative detail pages. 16×20px padding, subtle bg circle icon (48px), kudos counter on right. Hover: lift + shadow + border. Viewed state: 0.55 opacity.

### Search command palette (`[data-flux-command]`)

Hartverwarmers-branded variant of Flux command palette: borderless, rounded-2xl, dark drop shadow. Top input bar is filled coral with white placeholder. Modal backdrop is 50% black.

### Admin toolbar button (`.admin-bar-btn`)

Inline-flex, 12px text, secondary color, white bg. Hover: primary text color + cream bg. Active state: primary coral.

---

## Photography Style

The platform combines **real photography of activities and people** (hero photos in `/public/img/wonen-en-leven/`, `/public/img/initiatives/`, etc.) with two visual framings:

### 1. Polaroid framing

Photos in editorial contexts (homepage, story sections, contributor highlights) are wrapped in `.photo-polaroid` — white frame, slight rotation, warm shadow, optional handwritten caption in Nanum Pen Script. Evokes a care-home bulletin board.

### 2. Paper-stack framing (fiche covers)

Fiche preview photos appear as a stack of 3 rotated landscape papers (`.fiche-paper-0/1/2`). The top one carries the actual image; the others suggest depth and a "stack of practice notes". On hover the stack settles.

### Subject matter

Real photos celebrate the *doing*: residents engaged in activities, hands at work, materials in use. Empowerment-first: choose moments that show capability, joy, dignity. Avoid medical, assistive, or institutional context.

### Editorial vs. content photography

The brand also uses an editorial photography concept for marketing assets: single hero object on a saturated paper backdrop in one of the four brand colors (coral, teal, yellow, purple), top-down at f/8, single softbox at 10 o'clock, off-center composition rotated 15–35°. Used for hero illustrations, cover images, and seasonal banners — not for activity content itself.

---

## Illustration Assets

**118 SVG illustrations** in `/public/img/illustration/` covering care home themes: workshops, activities, tools, decorative elements. Used for content sections about workshops, tools, and video lessons. Style: warm, line-based, hand-drawn feel that matches the *gezellig* tone.

---

## Quick Reference: Recreating the Style

When creating a new asset (presentation slide, social media post, website section, folder cover):

1. **Background:** Cream (`#FEF8F4`) or white. Bold sections may use one of the four accent colors.
2. **Headings:** Aleo Bold 700, primary text color (`#231E1A`).
3. **Body text:** Fira Sans 400, primary or secondary text color.
4. **Accent color:** Coral (`#E8764B`) for links, labels, highlights.
5. **Decorative accents:** Teal, yellow, purple — sparingly.
6. **Grays:** Always warm-tinted (warm neutral scale), never cool or pure gray.
7. **Shadows:** Warm brown tone (`rgba(60, 40, 20, ...)` or `rgba(120, 90, 60, ...)` for paper effects).
8. **Corners:** 12px on cards, full-round on badges/pills.
9. **Photography:** Real photos in polaroid or paper-stack frame, or editorial top-down objects on colored paper.
10. **Gems:** DIAMANT polygon (`30,0 70,0 100,35 50,100 0,35`) for goal-related content.
11. **Handwritten touches:** Nanum Pen Script for captions, quotes, and notebook-style content.
12. **Overall feel:** Warm, tactile, handcrafted. Not clinical, not playful — *gezellig*.

---

*Source of truth: `resources/css/app.css`. Component reference: [`DESIGN_SYSTEM.md`](DESIGN_SYSTEM.md) and the living pattern library at `resources/views/admin/design-system.blade.php` (admin-only).*
