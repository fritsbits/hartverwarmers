# Hartverwarmers — Brand Guide

A complete reference for recreating the Hartverwarmers look and feel across presentations, websites, print, and digital assets.

---

## Brand Identity

**Name:** Hartverwarmers (Dutch: "heart warmers")
**Tagline:** *Laat je bewoners schitteren* ("Let your residents shine")
**Domain:** A platform for activity coordinators in elderly care homes to share practical initiative elaborations, organized around the DIAMANT pedagogical model.
**Language:** Dutch throughout.
**Tone:** Warm, caring, approachable, professional, cozy (*gezellig*). Celebrates capability and joy — never limitation.
**Parent brand:** Soulcenter (red `#ED3D3F` wordmark).

---

## Logo

An interlocking heart shape made of three overlapping paths:

| Layer | Color | Hex |
|---|---|---|
| Teal wing | Cyan/teal | `#50C5DC` |
| Red wing | Bright red | `#FF4143` |
| Overlap center | Deep burgundy/plum | `#741448` |

Proportions: 33 × 52 (portrait, roughly 2:3). The heart is abstract — two mirrored curves that interlock, not a traditional symmetrical heart.

In navigation, a simplified heart icon in primary orange (`#E8764B`) is paired with the lowercase word **hartverwarmers** in the heading font (Aleo bold). On small screens it abbreviates to **HVW**.

---

## Color Palette

### Primary

| Name | Hex | RGB | Use |
|---|---|---|---|
| **Coral orange** | `#E8764B` | 232, 118, 75 | Main brand color. CTAs, links, active states, section labels, accent backgrounds |
| **Coral hover** | `#D4683F` | 212, 104, 63 | Hover/pressed states |

### Accent colors

| Name | Hex | RGB | Use |
|---|---|---|---|
| **Teal** | `#4CB7C5` | 76, 183, 197 | Cool contrast accent. Decorative, illustrative |
| **Golden yellow** | `#F4C44E` | 244, 196, 78 | Bright, cheerful decorative accent |
| **Muted purple** | `#B57BB3` | 181, 123, 179 | Soft, unexpected accent. Used sparingly |

These four colors (coral, teal, yellow, purple) are the only saturated colors in the system. Everything else is warm neutral.

### Text

| Name | Hex | Use |
|---|---|---|
| **Primary text** | `#231E1A` | Headings, body text |
| **Secondary text** | `#756C65` | Metadata, descriptions, muted labels |
| **Muted text** | `#B0A89F` | Inactive elements, disabled states |

### Backgrounds

| Name | Hex | Use |
|---|---|---|
| **White** | `#FFFFFF` | Default page background, cards |
| **Cream** | `#FEF8F4` | Hero sections, warmest tone |
| **Subtle** | `#F5F0EC` | Alternating sections, inactive states |
| **Accent light** | `#FDF3EE` | Light orange tint, hover states, diamond indicators |

### Borders

| Name | Hex | Use |
|---|---|---|
| **Border light** | `#EBE4DE` | Default borders, separators |
| **Border hover** | `#DDD5CD` | Hover state for bordered elements |

### Warm neutral scale

All grays are warm-tinted (hue ~24, toward orange/cream). Never use pure/cool grays.

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

| Level | Size | Line-height | Font | Weight |
|---|---|---|---|---|
| H1 | 40px (32px mobile) | 1.25 | Aleo | 700 |
| H2 | 32px (24px mobile) | 1.25 | Aleo | 700 |
| H3 | 24px | 1.25 | Aleo | 700 |
| H4 | 20px | 1.25 | Aleo | 700 |
| Body | 16px | 1.6 | Fira Sans | 400 |
| Small | 14px | 1.6 | Fira Sans | 400 |
| Tiny | 12px | 1.6 | Fira Sans | 400 |

### Weight usage

| Weight | Value | Use |
|---|---|---|
| Light | 300 | Intro text, metadata, decorative text |
| Regular | 400 | Default body text |
| Medium | 500 | Badge text, indicators |
| Semibold | 600 | Section labels, CTA links, buttons, nav items |
| Bold | 700 | All headings, card titles, brand name |

### Section labels (eyebrow text)

Uppercase, tracking-widest, semibold (600), Fira Sans, primary coral color. Always appears above an H2 heading.

---

## Spacing

| Token | Value |
|---|---|
| xs | 4px |
| sm | 8px |
| md | 16px |
| lg | 24px |
| xl | 32px |
| 2xl | 48px |
| 3xl | 64px |
| 4xl | 96px |

---

## Effects

### Border radii

| Use | Value |
|---|---|
| Subtle rounding | 6px |
| Cards, containers | 12px |
| Pills, badges, avatars | 9999px (full) |

### Shadows

Shadows are warm-tinted — `rgba(60, 40, 20, ...)`, not neutral gray.

| State | Shadow |
|---|---|
| Default card | `0 2px 8px rgba(60, 40, 20, 0.05)` |
| Hover card | `0 4px 16px rgba(60, 40, 20, 0.10)` |

### Hover behavior

Cards lift 2px (`translateY(-2px)`) with shadow deepening on hover. CTA text links have an animated right-arrow (`→`) that shifts right on hover.

### Transition speed

All interactions: `0.2s ease`.

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

### The Diamant Gem icon

A faceted gem-shaped polygon (five-sided: pointed top-left and top-right, wider middle, pointed bottom). Internal facet lines create a cut-gem appearance. The letter sits centered in Aleo bold with a subtle drop shadow.

**Shape** (SVG polygon): `30,0 70,0 100,35 50,100 0,35`

**States:**

| State | Fill | Letter color | Facet lines |
|---|---|---|---|
| Active | `#E8764B` (coral) | White | White at 30% opacity |
| Inactive | `#F5F0EC` (subtle gray) | `#B0A89F` (muted) | Black at 8% opacity |
| Inverted | White | `#48423C` (dark warm) | `#AEA59C` (gray) |

**Sizes:** From 20px (xxs) to 48px (lg).

### DIAMANT Profile

A horizontal row of 7 small gems spelling D-I-A-M-A-N-T. Active goals are coral-colored; inactive goals are muted gray. Used to show which goals an initiative covers.

### Diamant Pill

A compact link badge: small gem icon + goal label text, with subtle background. Used inline to tag content with its DIAMANT goals.

---

## Iconography

All icons are **Heroicons** (outline style, stroke-based, inline SVG). No icon fonts, no sprite sheets.

- Standard size: 16–20px
- Stroke width: 1.5–2
- Color follows the surrounding text color

Commonly used: heart, book-open, lightbulb, users, calendar, document, search (magnifying-glass), chevron-down, arrow-right, bookmark, swatch, flag.

---

## Layout Patterns

### Page structure

- **Max content width:** 1152px, centered
- **Horizontal padding:** 24px
- **Section vertical padding:** 64px
- **Navigation:** Sticky top, 72px height

### Section rhythm

```
[Cream hero section]
   Section label (coral uppercase)
   H1 heading
   Description text

── border separator ──

[White content section]
   Content...

── border separator ──

[White content section]
   Content...
```

Hero sections use the cream background (`#FEF8F4`). Content sections alternate on white (`#FFFFFF`). Sections are separated by light border rules (`#EBE4DE`).

### Card grids

1 column on mobile, 2 on tablet, 3 on desktop. Gap: 24px.

---

## Card Design

White background, 12px border-radius, light border (`#EBE4DE`). On hover: lifts 2px, shadow deepens, border darkens.

- **Card image:** Aspect ratio 16:10, object-fit cover
- **Card content:** 24px padding
- **Card title:** Aleo bold
- **Card description:** Secondary text color, regular weight

---

## Photography Style

Top-down studio photography of iconic objects on single-color matte paper.

### Concept

Each image shows **one hero object + 1–3 small supporting elements** representing an activity — not the person doing it. Objects celebrate the *doing*: the fun, creativity, togetherness. No people, no scenes, no environment.

**Empowerment-first rule:** Choose objects you'd pick for anyone enjoying the activity. Never use medical, assistive, or age-related objects (no canes, walkers, pill boxes, etc.).

### Camera & lighting

- **Angle:** Strict top-down (bird's-eye), no exceptions
- **Lens:** 80mm macro equivalent, f/8 for full sharpness
- **Key light:** Single large softbox at 10 o'clock, 45° above surface
- **Fill:** White card at lower-right — opens shadows gently
- **Character:** Broad, heavily diffused. Calm, even, studio-controlled

### The paper backdrop

A single sheet of thick, matte colored paper covers the entire frame, edge to edge. Fine real paper texture, never glossy.

**Strict four-color palette** (matching brand accent colors):

| Paper color | Hex | Best with |
|---|---|---|
| Coral orange | `#E8764B` | Cool-toned or neutral objects (default) |
| Teal | `#4CB7C5` | Warm-toned objects |
| Golden yellow | `#F4C44E` | Darker objects |
| Muted purple | `#B57BB3` | Yellow or green objects |

**Objects keep their natural, real-world colors** — they are NOT color-matched to the paper. The paper is always saturated and present, never pale.

### Shadows

Barely-there warm shadow toward 4 o'clock (matching 10 o'clock light). Extremely soft, diffused, 8–12% darker than lit paper. No hard edges — shadow gradually appears and dissolves. At a glance you almost wonder if it's there.

### Composition

- Hero object **off-center**, shifted toward a corner or edge
- Rotated **15–35 degrees** off-axis (never 0, never 45)
- All objects fill **50–70% of frame area**
- **Unequal margins** — asymmetric, editorially confident
- Objects may extend beyond frame edges — bold, deliberate cropping
- Sparse and curated, never cluttered

### Object examples

| Activity | Hero object | Supporting elements |
|---|---|---|
| Team support | Teapot | Two cups + cookies |
| Cooking together | Steaming pot | Wooden spoon + fresh herbs |
| Knitting & crafts | Ball of yarn | Needles + half-finished piece |
| Outing | Binoculars | — |
| Movement & fitness | Badminton racket + shuttlecock | — |

### What to avoid

- People, characters, body parts
- Medical/assistive objects
- Centered or symmetrical placement
- Hard or dark shadows
- Environmental context or backgrounds
- Text, labels, or typography on the image
- Illustrated or digitally rendered objects
- Gradients, patterns, or prints on the paper
- Cluttered scenes

---

## Illustration Assets

52 SVG illustrations covering care home themes: workshops, activities, tools, and decorative elements. Located in `/public/img/illustration/`. These are used for content sections about workshops, tools, and video lessons.

---

## Quick Reference: Recreating the Style

When creating a new asset (presentation slide, social media post, website section, folder cover):

1. **Background:** Use cream (`#FEF8F4`) or white, or one of the four accent colors for bold sections
2. **Headings:** Aleo bold 700, primary text color (`#231E1A`)
3. **Body text:** Fira Sans 400, same text color or secondary (`#756C65`)
4. **Accent color:** Coral orange (`#E8764B`) for links, labels, highlights
5. **Decorative accents:** Teal, yellow, or purple — sparingly
6. **Grays:** Always warm-tinted, never cool or pure gray
7. **Shadows:** Warm brown tone (`rgba(60, 40, 20, ...)`), very subtle
8. **Corners:** 12px on cards, full-round on badges/pills
9. **Photography:** Top-down objects on colored paper (see Photography section)
10. **Gems:** Use DIAMANT gem shapes for goal-related content
11. **Overall feel:** Warm, clean, spacious, confident. Not clinical, not playful — *gezellig*.
