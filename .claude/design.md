# Anna Kalynyuk — Design Contract

> **This file is binding.** Every build, block, page, and mockup for this project
> must conform to the values below. Read it before writing any HTML/CSS, the same
> way you read `ci-cd.md` before deploying. If a value you need isn't here, derive it
> from the scales here — don't invent one.
>
> **Status:** v2.0.0 · 2026-07-27
> **Mode:** SCRAPE, reconciled against the **Figma UI Kit** (page `336:2947`).
> **Source priority:** (1) the UI Kit — 21 text styles, the Colors section, the
> Buttons/Inputs component sets; (2) Figma frame `1163:950` for layout/grid;
> (3) production computed styles via Playwright at 1440×1000, for anything the kit
> does not define.
> **Implemented in:** `https://www.kalynyuk.com` — Divi module settings + the
> `et_divi` option. There is **no stylesheet** implementing this system; see §13.
> **Not yet applied to:** pages 2484 (`en`) and 2485 (`ru`) — both empty, now drafts.

> ## ⚠️ v2.0.0 — what the UI Kit corrected
>
> v1.0.0 was reverse-engineered from production computed styles **before the kit was
> available**. Production is the OLD Divi implementation; the kit is what we build.
> Four v1 claims were wrong and are corrected in place below:
>
> 1. **`#609079` is a real token, not noise.** v1 §2 said *"there is no second accent
>    hue… #609079 appears exactly once as a border and is noise — do not propagate
>    it."* It is an official kit swatch and its label states its role: **stroke on a
>    green background**. It appeared once on production because the implementation
>    under-used it, not because it isn't a token.
> 2. **There is no 50px pill.** Every kit button variant is `cornerRadius: 24`. At the
>    standard 48px button height that reads as a full pill, which is why production's
>    literal 50px looked correct — but the kit's **wide** button is 493×**57** with the
>    same radius 24, so it is a rounded rectangle. The system is a constant 24px
>    radius; "always pill" was a Divi artifact. `--radius-pill` is removed.
> 3. **Button labels are SemiBold 600, not Medium 500.** The kit contains **no
>    Medium(500) and no Light(300) style at all**. Weights are 400 / 600 / 700 only —
>    so the Google Fonts request should drop 300 **and** 500.
> 4. **H2 is 32px, not 48px.** Production renders every H2 at 48px, identical to H1.
>    The kit separates them: H1 48, H2 32 (desktop).
>
> Two v1 gaps are now closed: the **mobile type scale** exists in the kit (gap #9 was
> "desktop only"), and **weight 300** is confirmed unused (gap #12).
>
> The kit also reproduces a defect: its primary-button fill is `#307156`, one digit
> off the `#307155` swatch — the same split as `et_divi`'s `accent_color` vs
> `footer_bg`. So the Divi typo **originated in the design file**. The swatch is
> canonical.

---

## 1. Identity

**Warm institutional trust.** A financial-advisory site that reads as calm and
personally vouched-for rather than corporate — cream paper, a single deep forest green,
and one large tightly-tracked sans for everything.

What carries it: (a) the **cream canvas** `#F7F2E9`, which is the page itself, not a
section treatment; (b) **one accent green** `#307155` used only for the two inverted
bands and the primary button; (c) **negative-tracked 48px headings at line-height 1.0**,
which is the single most identifying typographic move on the site; (d) generous
**24px-radius panels on a slightly lighter cream** `#FEF8EF`, so cards read as raised
paper rather than as bordered boxes.

Genre siblings: independent mortgage brokers and boutique wealth advisors — the
"warm fintech" register (Monzo-adjacent palette warmth, but with none of the playfulness).
Reference system: **none.** The system is Divi Theme Options plus per-module settings; it
was assembled by eye, not from a token file.

---

## 2. Colour

Measured by frequency across the rendered homepage. `n` = element count.

| Token | Hex | Role | n |
|---|---|---|---|
| `--ink` | `#2A2011` | Primary text, dark band background, Gravity Forms submit fill | 655 |
| `--canvas` | `#F7F2E9` | **The page.** `<body>` background, text on green, nav background | 148 / 19 |
| `--surface` | `#FEF8EF` | Panels, cards, form inputs — the raised cream, one step lighter than canvas | 41 |
| `--surface-sunken` | `#EEEADC` | Recessed / alternate panel fill | 11 |
| `--accent` | `#307155` | The one accent. Inverted bands, primary button, focus rules | 6 bg / 10 ink |
| `--accent-light` | `#609079` | **Stroke on a GREEN background** — kit swatch, role from its own label. Hairline only, never a fill. Added v2.0.0. | 1 |
| `--border` | `#D6D0C6` | **Stroke on a LIGHT background** — kit swatch, role from its own label | 21 |
| `--ink-muted` | `rgba(42,32,17,0.56)` | Secondary text on cream | 30 |
| `--ink-soft` | `rgba(42,32,17,0.50)` | **Part of the same line**, de-emphasised — the tail of a `Label: gloss` heading. Added v2.1.0 (Petr, 2026-08-03). | — |
| `--border-soft` | `rgba(42,32,17,0.24)` | Hairline on cream, lighter variant | 7 |
| `--canvas-muted` | `rgba(247,242,233,0.56)` | Secondary text on green | 4 |
| `--border-inverse` | `rgba(247,242,233,0.24)` | Hairline on green | 4 |
| `--white` | `#FFFFFF` | Rare. Only inside third-party widgets. | 3 |

**Divi Theme Options equivalents** (`et_divi`, for when you're editing in wp-admin
rather than writing CSS): `accent_color` → `#307155`, `secondary_accent_color` → `#2a2011`,
`primary_nav_bg` → `#f7f2e9`, `footer_bg` → `#307156` ⚠️ (a **typo** — the footer renders
`#307155`; see §13).

### Accent discipline 🔒

`--accent` `#307155` is allowed in exactly four places:

1. The background of an inverted band (§5 — two bands, no more).
2. The fill of a **primary** button.
3. A 1px or 3px rule / border used as emphasis (measured: `3px solid #307155` ×2, `1px solid #307155` ×2).
4. Icon or figure colour inside an otherwise-cream panel.

It is **not** allowed as: a text colour for body copy, a link colour in running text, a
panel fill, a hover tint on cream surfaces, or a gradient stop.

There is **no second accent hue** — but there **is** a second green, and it is a real
token. **CORRECTED in v2.0.0:** `--accent-light` `#609079` is an official UI Kit swatch
whose label states its role, *"Stroke on green bg."* v1.0.0 wrongly called it noise
because production uses it exactly once. Its scope is narrow and strict: **a 1px hairline
on a green band, and nothing else.** It is not a fill, not a text colour, not a second
accent — use `--accent` for anything that carries meaning.

> Note the implementation diverges here: production mostly draws hairlines on green with
> translucent cream `rgba(247,242,233,0.24)` (4×) and `#609079` only once. The kit says
> `#609079`. **New work follows the kit;** the translucent value stays documented only so
> existing Divi sections are legible.

### Foreign colours — do not adopt

These are present on the live page but belong to third-party widgets, not to this system.
Never sample them into new work:

| Hex | Source |
|---|---|
| `#1A7BFF` (n=17 ink, 1 bg) | Review Wall "Review us on Google" button — Google brand blue |
| `#007AFF` | Swiper default (`--swiper-theme-color`) |
| `#C02B0A` | third-party widget |
| `#112337`, `#686E77` | third-party widget |
| `#000000` (n=501) | **browser default**, not a decision — inherited by unstyled elements |

`#000000` appearing 501 times is a measurement artifact of unstyled leaf nodes, not a
palette entry. Real text is `--ink`.

---

## 3. Typography

### Families

| Role | Family | Weights loaded | Where |
|---|---|---|---|
| **Everything** | **Nunito Sans** | 300, 400, 600, 700 (variable `opsz 6..12`) | All headings, all body, all UI |
| *(foreign)* | Roboto Flex | 100–1000 variable | Review Wall widget only — **do not use** |
| *(icons)* | ETmodules | 400 | Divi's bundled icon font |
| *(icons)* | gform-icons-orbital | 400 | Gravity Forms' bundled icon font |

**Licensing:** Nunito Sans and Roboto Flex are both Google Fonts under the **SIL Open
Font License — free, commercially shippable, no cost.** ETmodules ships with the Divi
licence; gform-icons-orbital ships with Gravity Forms. **Nothing here is paid or
legally blocking.**

⚠️ Nunito Sans is currently loaded **twice** from `fonts.googleapis.com` — once by Divi's
font enqueue and once by a CSS `@import` in the CodeKit SCSS. That is a performance
defect recorded in `docs/05-issues.md`, not a design decision. Any new work should assume
one source.

### Weight rules

Four weights are loaded; three are used meaningfully.

- **600** — display headings (48px) only. Never below 24px.
- **700** — card titles (24px), bold lead (20px), small emphasis (14px). This is the "bold" of the system.
- **500** — buttons, UI labels, micro-copy. Never for headings.
- **400** — all body copy, and (oddly) the 34px stat figure and the 16px accordion title.
- **300** is loaded but unused on the homepage. Don't reach for it.

Note the system has **no 500-weight heading and no 600-weight body**. Weight jumps
straight from 400 to 700 below 24px, and sits at 600 only at 48px.

### Type scale

Curated from 30 measured combinations down to 10 real roles. `n` = element count;
anything at n≤2 was folded into its nearest role.

| Role | Size | Weight | Line-height | Tracking | n | Used for |
|---|---|---|---|---|---|---|
| `display` | **48px** | 600 | **48px (1.0)** | **−1.92px (−0.04em)** | 8 | H1 + every H2. The signature. |
| `stat` | 34px | 400 | 34px (1.0) | −0.2px | 1 | Large figure above a caption ("реалізованих кейсів") |
| `title` | **24px** | **700** | 27.84px (1.16) | −0.24px (−0.01em) | 14 | Every H3 — card and panel titles |
| `lead` | 20px | 400 | 26px (1.3) | −0.2px / −0.16px | 16 | Intro paragraphs, panel body |
| `lead-strong` | 20px | 700 | 23.2px (1.16) | −0.2px | 8 | Emphasised lead, accordion labels |
| `body` | **16px** | 400 | **22.4px (1.4)** | normal | 53 | **The workhorse.** Default paragraph. |
| `body-long` | 16px | 400 | 26px (1.625) | −0.16px | 24 | Long-form running text (FAQ answers, article body) |
| `ui` | 16px | 500 | 27.2px | normal | 6 | Buttons, form labels, nav |
| `accordion` | 16px | 400 | 16px (1.0) | normal | 8 | H5 accordion headers — flush line-height |
| `small` | 14px | 700 | 16.24px (1.16) | normal | 4 | Badges, captions, small emphasis |
| `micro` | 12px | 500 | 17.16px (1.43) | normal | 5 | Legal, disclaimers, meta |

**The two tracking rules that define the voice:**
- Anything **≥ 20px gets negative tracking** — `−0.01em` at 20–24px, `−0.04em` at 48px.
- Anything **≤ 18px gets `normal`** tracking. Never tighten body copy.

**The two line-height rules:**
- Display and flush roles sit at **1.0**. Headings do not breathe.
- Body sits at **1.4** for UI-adjacent copy and **1.625** for long-form reading. Pick by reading length, not by taste.

### Banned

- **Any second family.** No serif for display, no mono anywhere, no Roboto Flex outside the Review Wall widget.
- **Mono-as-display** — the site has no monospace face and must not gain one.
- **Uppercase eyebrow micro-labels** — `text-transform` measured `none` on every button and label on the page. There are no all-caps micro-labels in this system; don't introduce them.
- **Letter-spaced small caps.**
- **Weight 300** for anything user-facing.
- **`ls: -4%`** — appears once as a Divi authoring artifact (a percentage where px was expected). Always specify tracking in px or em, never %.

---

## 4. Spacing & shape

### Base unit

**8px.** The measured system is `8 · 16 · 20 · 24 · 32 · 40 · 50 · 56 · 120` — an 8px grid
with two off-grid survivors (20 and 50) that come from Divi module defaults.

| Token | Value | Applies to |
|---|---|---|
| `--space-1` | 8px | Icon gaps, tight inline spacing |
| `--space-2` | 16px | Intra-component spacing |
| `--space-3` | 24px | Panel inner padding, form field padding |
| `--space-4` | 32px | Section padding (compact bands), panel gaps |
| `--space-5` | 40px | Section padding (standard) |
| `--space-6` | 50px | Section padding (generous) |
| `--space-7` | 56px | Lede → content-stack gap (added v2.1.0) |
| `--space-8` | 120px | Hero / feature-band top padding |

⚠️ **`--space-7` was added in v2.1.0**, measured on Figma `1130:3906`: the `about`
band's heading box ends at y=1024 and its credential list starts at y=1080. It is
`7 × 8`, so it fills the one hole in the 8px scale rather than bending it, and it was
added here BEFORE being used — the rule in `.claude/CLAUDE.md`. Rounding to
`--space-6` (50px) was the alternative and was rejected: 6px on a gap this size is
visible next to the 120px band rhythm directly below it.

`20px` is the **mobile gutter** (measured `custom_padding_phone="40px|20px|32px|20px"`).
Treat it as the gutter token, not a spacing step.

`57.59px` appears twice in section padding — that is a computed `%`-based Divi value, not
a decision. Do not codify it; round to `--space-6` (50px) or `--space-4` (32px).

### Radius

Measured: 12 distinct values. The **real** system is four steps; the rest is noise.

| Token | Value | Applies to | n |
|---|---|---|---|
| `--radius-sm` | **8px** | Small chips, tight controls | 7 |
| `--radius-md` | **16px** | Form inputs, small cards | 21 |
| `--radius-lg` | **24px** | Panels, large cards, Gravity Forms submit | 26 |
| ~~`--radius-pill`~~ | ~~50px~~ | **REMOVED in v2.0.0** — the kit has no pill token. Buttons use `--radius-lg` (24px); at 48px height that reads as a pill. | — |
| `--radius-circle` | **50%** | Avatars, icon circles | 24 |

Split radii are permitted for stacked/joined panels: measured `24px 24px 0 0` and
`0 0 24px 24px`. Use `--radius-lg` on the exposed corners only.

⚠️ **Noise — do not use:** `3px` (n=1), `6px` (n=4), `12px` (n=1), `20px` (n=1),
`32px` (n=1, the foreign Review Wall button). Five stray radius values with no logic.
Recorded in §13.

### Elevation

**This system is border-first, not shadow-first.** Borders outnumber shadows 45 to 11.

| Approach | Spec | n |
|---|---|---|
| **Default: hairline** | `1px solid #D6D0C6` | 21 |
| Hairline, softer | `1px solid rgba(42,32,17,0.24)` | 7 |
| Hairline on green | `1px solid #F7F2E9` / `rgba(247,242,233,0.24)` | 6 / 4 |
| Emphasis rule | `3px solid #307155` | 2 |

**The only allowed shadow:**

```css
box-shadow: 0 1px 4px rgba(18, 25, 97, 0.08);
```

Measured n=8. Note the shadow hue `rgb(18,25,97)` is a **navy blue that appears nowhere
else in the palette** — it is almost certainly a Divi preset that was never adjusted.
It is documented here as the shipped value so new work matches, but it is flagged in §13
as the one thing in this contract that should probably change.

⚠️ **Banned shadows:** `0 2px 5px rgba(0,0,0,0.1)` (n=2) and `0 2px 18px rgba(0,0,0,0.3)`
(n=1) both appear on the live page and both belong to third-party widgets. Never add a
third shadow value.

### Signature detail

**The lighter-cream raised panel.** `--surface` `#FEF8EF` on `--canvas` `#F7F2E9` is a
~2% luminance step — barely visible, and that restraint *is* the detail. Panels are
distinguished by radius and hairline, not by contrast.

```css
.panel {
  background: #FEF8EF;
  border: 1px solid #D6D0C6;
  border-radius: 24px;
  padding: 24px;
}
```

---

## 5. Surfaces & the canvas rule

### 🔒 The one-canvas rule — **HELD**

**Canvas audit:** 13 sections measured → **2 distinct section backgrounds.**
Threshold is >3 = FAIL. **PASS, comfortably.**

| Band | Value | Sections |
|---|---|---|
| **Canvas** (default) | `#F7F2E9` | **10** |
| **Inverted** | `#307155` | **3** |

**Band budget: 2. Both are spent.** The page is one cream sheet with three green
inversions punched into it (hero, a mission statement, a CTA). **Zero gradients, zero
background images on sections.**

Rules:
- A new section defaults to **`--canvas`** and declares no background at all.
- A green band is reserved for a **rhetorical inversion** — hero, mission, or CTA. If a new section isn't one of those three jobs, it does not get a band.
- **Never introduce a third band colour.** Not `--surface`, not `--surface-sunken`, not white, not a tint.
- **Never introduce a gradient or a section background image.** The site currently has none; adding one breaks the paper metaphor that carries the whole design.

### Panel surfaces — colour *inside* a block

Colour lives at panel level, not section level.

| Surface | On canvas | On green band |
|---|---|---|
| Panel fill | `#FEF8EF` | `transparent` |
| Panel border | `1px solid #D6D0C6` | `1px solid rgba(247,242,233,0.24)` |
| Recessed fill | `#EEEADC` | — |
| Primary text | `#2A2011` | `#F7F2E9` |
| Secondary text | `rgba(42,32,17,0.56)` | `rgba(247,242,233,0.56)` |

On a green band, panels are **outlined, not filled**. That's the measured pattern (form
inputs on the CTA band are `transparent` with a `rgba(247,242,233,0.24)` hairline) and it
is the correct one — a cream fill on green would read as a hole.

---

## 6. Layout

### Container 🔒 — THE canonical rule, applies to every section

> **Set by Petr, 2026-07-27. This supersedes every measured value below it.**
> Canvas **1440** · container **1376** · padding **30 desktop / 20 mobile**.

```scss
// The BAND is full-bleed and owns the background.
// The __inner is capped. Never put a max-width on the band itself.
.some-section {
  background-color: …;

  &__inner {
    @include m.container;   // max-width 1376 + padding 30 / 24 / 20
  }
}
```

**Every** section follows this — header, footer and all future ones. The footer and
header both use `container()`; a new section that hand-rolls its own `padding-inline`
is a defect.

#### The one exception: a band carrying a fixed-composition photograph

> **Set by Petr, 2026-07-30.** `.hero` — and only `.hero` today — also gets
> `max-width: $canvas-max` (1440) and `margin-inline: auto`.

The hero's background is not a colour, it is a photograph exported at 2880×1468 —
exactly 2× the 1440×734 band, so the crop is part of the design. Left full-bleed, a
wider viewport makes `object-fit: cover` recrop it and eat the top and bottom of the
frame, which is where the subject and the baked-in gradient are.

This does **not** weaken the rule for anything else, and it moves no text: the
`__inner` still uses `container()`, whose cap (1376 + 2×30 = 1436) is under 1440, so
content sits in the same place at every viewport width either way. Only the band stops
stretching. A **colour** band never gets this treatment — reach for `$canvas-max` only
when a fixed image composition is the reason.

**Related:** the hero has **no gradient overlay element**. The Figma frame shows a
`shadow` layer, but that gradient is already baked into the exported photograph;
rendering it again darkened the frame twice. Removed 2026-07-30. If a future photo
ships without one, put the gradient on `.hero__media`, not on a new element.

<details>
<summary>What was measured before this rule, and why it is superseded</summary>

Three different numbers turned up while reverse-engineering, and all three were the
old implementation or the design file drifting rather than the intent:

| Source | Value | Verdict |
|---|---|---|
| Divi section `max_width` attribute | 1600px | the old build's number — **not** the design's |
| Figma section grid (`x=32, w=1376`) | 32px gutters | close, but the intent is 30 |
| Figma footer frame `1163:303` | 40px gutters | design-file drift |
| Production content column at 1440 | 1368px | the same 1376 measured through Divi's box model |

`$container-max` was 1600 in tokens v2.0.0 for exactly this reason. Corrected.
</details>

- **Content column:** ~1376px at 1440. Wide — treat it as the outer bound, not the reading measure.
- **Measure cap:** running prose must be capped separately. Measured half-column widths cluster at **604–663px**, which at 16px/1.625 is ~72 characters. **Cap long-form text at 680px.** Do not let body copy run the full width.
- **`--wp--style--global--content-size`** is `823px` and `wide-size` `1080px` — core block-editor defaults, governing only the two Gutenberg-only pages. Not this system's values.

- **Content column:** 1368px at 1440. This is wide — treat 1368px as the outer bound, not the reading measure.
- **Measure cap:** running prose must be capped separately. Measured half-column widths cluster at **604–663px**, which at 16px/1.625 is ~72 characters. **Cap long-form text at 680px.** Do not let body copy run the full 1368px.
- **`--wp--style--global--content-size`** is `823px` and `wide-size` `1080px` — those are core block-editor defaults and govern only the two Gutenberg-only pages. They are not this system's values.

### Signature grid

**Two-column halves.** The dominant measured pattern is a pair of ~604–663px columns
inside the 1368px content width — a 50/50 split with a gap in the 32–40px range. Card
grids on the homepage sit at 3-up (measured ~289px card width inside a half-column
context) and 2-up.

### Section rhythm

| Section type | Padding (top / bottom) |
|---|---|
| Hero / feature band | `120px` / `0` — or `120px` / `50px` |
| Standard | `50px` / `50px` |
| Compact | `40px` / `32px` or `32px` / `32px` |
| Flush (abutting bands) | `0` / `0` |

Bands that abut deliberately use `0` padding so the colour change *is* the separator.
That's correct — don't add padding "for breathing room" between a cream and a green
section.

### Breakpoints

Divi's set, which is what this site is authored against:

| Name | Width |
|---|---|
| Desktop | ≥ 981px |
| Tablet | 768–980px |
| Phone | ≤ 767px |

CodeKit exposes per-breakpoint SCSS files but **only the `desktop` one has ever been
used** — there is no responsive CSS in this project at all. Responsive behaviour is
entirely Divi's per-module tablet/phone settings.

### Grid-variety rule

The homepage runs 9 sections through 19 rows. **Do not add a fourth distinct grid
shape.** The vocabulary is: full-width, 50/50 halves, and 2-up/3-up card grids. A new
section picks one of those.

---

## 7. Components

All values measured off production.

### Buttons

Every kit button is **radius 24px** at **16px / weight 600 (SemiBold) / lh 116% / tracking -1% / 
`text-transform: none`**. Three variants, differing only in fill:

| Variant | Height | Padding | Fill | Text | Border |
|---|---|---|---|---|---|
| **Primary** (accent) | 49px | `10px 20px` (wide: `10px 22px`, tall: `15px 20px`) | `#307155` | `#F7F2E9` | `1px #307155` |
| **On-dark solid** | 49px | `10px 20px` | `#F7F2E9` | `#2A2011` | `1px #F7F2E9` |
| **On-dark ghost** | 49px | `10px 20px` | `transparent` | `#F7F2E9` | `1px #F7F2E9` |

- Standard width sits at **221–247px** — these are wide, generous buttons, not compact ones.
- A wide variant at **487px / 59px tall** with `15px 20px` padding exists for a single full-width CTA.
- There is **no cream-on-cream (secondary-on-canvas) button** in the measured set. If you need one, use the ghost pattern inverted: `transparent` fill, `1px solid #2A2011`, text `#2A2011`. Add it to this table when you do.
- Border is always declared at 1px even on solid fills, so hover can swap fill without a layout shift.

### Form controls (Gravity Forms)

| Element | Spec |
|---|---|
| **Input on canvas** | `background: #FEF8EF` · `border: 0` · `radius: 16px` · `padding: 24px 19px` · height **48px** · `16px / 400 / ls −0.16px` · text `#2A2011` |
| **Input on green band** | `background: transparent` · `border: 1px solid rgba(247,242,233,0.24)` · `radius: 16px` · `padding: 24px 19px` · height **50px** · text `#F7F2E9` |
| **Submit** | `background: #2A2011` · `color: #FEF8EF` · `border: 0` · **`radius: 24px`** · `padding: 19px` · height **54px** · `16px / 600` · full-width (`gform-button--width-full`) |

Note the submit button is **the one control that breaks the pill rule** — it uses
`--radius-lg` (24px) and weight 600, not the 50px pill at 500. That is deliberate and
consistent across the site: **Divi buttons are pills, Gravity Forms submits are 24px
rectangles filled with `--ink`.** Preserve the distinction.

Forms are all forced into AJAX mode by the child theme — see `docs/03-theme-architecture.md`.

### Accordion (FAQ)

| Part | Spec |
|---|---|
| Header | `16px / 400 / line-height 16px (1.0)` · text `#2A2011` (one measured at `#333` — drift, use `--ink`) |
| Panel | radius `16px` · `1px solid rgba(42,32,17,0.24)` · `padding 10px` · height 44px collapsed · width 289px in the measured 3-up context |
| Body | `body-long` — 16px / 400 / 1.625 / −0.16px |
| Chevron | ETmodules icon, `--ink` |

Measured on the homepage: 1 accordion, 7 items. The FAQ page (2440) uses the same pattern.

⚠️ **SUPERSEDED ON THE HOMEPAGE — v2.1.0.** The table above is the LEGACY Divi accordion.
The `trust` band was rebuilt to a different pattern entirely (Figma `1130:8979`) and the
two share nothing but the word: no panel boxes, no chevron, no 16px header.

| Part | Spec — the current pattern |
|---|---|
| Question | Desktop/H3 — 24 / 700 / 116% / −1%, `--ink`, max 548 |
| Answer | Desktop/Body, max 587, `--space-3` below the question |
| Marker | 32×32 lucide **plus** closed, **minus** open, `--ink`, hard right |
| Separator | 1px `--border` **between** items — `--space-4` above and below it |
| Panel | **none** — no fill, no box, no radius |

🔒 **It is a native `<details name="…">` accordion and must stay one.** Grouping by `name`
makes the items mutually exclusive in the browser — the requested "opening one closes the
other" is not code we own. That also buys keyboard operation, correct ARIA with no
`aria-expanded` to keep in sync, and Ctrl+F opening a collapsed answer to reveal a match.
The first item carries `open` declaratively, so it is never briefly collapsed on load.

⚠️ **No expand animation, and that is a §9 constraint rather than an oversight** — the two
motion moments are already spent, so an animated height would be a third. The marker swap
is instant for the same reason: an animated icon over an instant panel reads as a glitch.

The legacy table still applies to the FAQ page (2440), which has not been migrated.

### Panels / cards

```css
.card {
  background: #FEF8EF;
  border: 1px solid #D6D0C6;
  border-radius: 24px;
  padding: 24px;
}
.card__title { /* title */ font: 700 24px/27.84px "Nunito Sans"; letter-spacing: -0.24px; }
.card__body  { /* body  */ font: 400 16px/22.4px  "Nunito Sans"; }
```

On a green band, drop the fill and swap the border to `rgba(247,242,233,0.24)`.

### Stat figure

`34px / 400 / line-height 34px (1.0) / ls −0.2px`, colour `rgba(247,242,233,0.56)` when on
a green band. Caption below in `body`. Measured once (hero: "реалізованих кейсів"), so
treat this as a one-off pattern with a defined spec rather than a component library entry.

### Credential row — added v2.1.0

The `about` band's 01–04 list. Figma `1130:8820` (`Frame 9092`), 1376×744, four rows of
1376×174 with a **16px** gap. Each row: fill `--surface`, radius `--radius-lg`, padding
`--space-4`, and **no border** — the row's stroke exists in the file but is
`visible: false`, so this is the one panel in the system that is fill-only. Do not add
the hairline from "Panels / cards"; the 2% luminance step is the whole separation.

Three columns inside the padding box (1312px at a 1440 canvas), measured absolute
x = 64 / 296 / 728:

| Column | Width | Role | Spec |
|---|---|---|---|
| Numeral | 232px | `01`–`04` | H2 size (32px) / 600 / lh 116% / ls −4%, `--ink` at **opacity .4** |
| Label | 432px | Row title | Desktop/H3 verbatim — 24px / 700 / lh 116% / ls −1%, `--ink` |
| Body | 648px | Prose + inline `<strong>` | Desktop/Body verbatim — 20px / 400 / lh 124% / ls −1% |

- **Row 1 alone carries a button** — `--accent` fill, 48px tall, radius 24, hugging its
  label (188px), `--space-3` below the label. The other three rows have no action.
- **The three columns are TOP-aligned (`align-items: start`).** Figma puts the numeral at
  the padding edge (y=32) and both text columns 5px lower (y=37) — the two text tops are
  equal to each other and the numeral is the odd one out, which is a uniform nudge rather
  than a system rule. `align-items: baseline` was tried and **measured wrong**: it pushed
  the label to +8 and the body to +11, and split the two text columns 3px apart, which the
  frame does not do. `start` puts all three at 32 — numeral exact, text 5px high, text
  columns level as drawn — and cap-aligns marginally tighter than the frame (the numeral's
  cap lands 1.6px below the label's, against 3.4px in Figma). Do not reintroduce the 5px
  as a literal offset: it is a function of the type sizes and would rot if either changed.
- **Rows are equal-height by design** (all four are 174px in the file even though rows
  2–4 hold half the copy). Reproduce with equal grid tracks, not a `min-height` literal.
- ⚠️ **The built row is 164px, not 174px, and that is correct.** Figma's 174 = 37 + 100
  content + 37, i.e. a 37px padding with the numeral breaking out of it by −5. The build
  uses `--space-4` (32px) padding with everything top-aligned, giving 32 + 100 + 32 = 164.
  32 is a token and 37 is not, and the numeral sits at exactly 32 in the frame — so 32 is
  the intent and the 37 is the same optical text nudge described above. The four rows
  therefore run 40px shorter in total than the frame, and everything below the list sits
  ~40px higher. **Do not "fix" this by inventing a 37px padding step.**
- ⚠️ **Zero the `li` margin.** Divi's global list CSS puts a bottom margin on every `li`,
  and a grid item's margin sits *inside* its grid area — so against `grid-auto-rows: 1fr`
  it stretches every row and pushes everything below the list down. It rendered the
  four-row list 768px tall against the 744px its tracks and gaps add up to.

### Portrait crop — added v2.1.0

The `about` band's square portrait, Figma `1130:4797` (`Frame 162`): 332×332, radius
`--radius-lg`, background `--canvas`. The image inside it is **not** a `cover` crop — it
is a 841.55×560.90 box offset to (−277.62, −22.90), i.e. zoomed ~1.69× past `cover` and
pulled off-centre onto the face. Express every value as a **percentage of the frame** so
it survives a fluid container:

```css
width:     253.5%;   /* 841.55 / 332 */
height:    168.9%;   /* 560.90 / 332 */
left:      -83.6%;   /* -277.62 / 332 */
top:       -6.9%;    /* -22.90  / 332 */
max-width: none;     /* REQUIRED — see below */
object-fit: cover;
```

Height is stated explicitly rather than left to `auto` **because Figma's fill is
`scaleMode: FILL`**: the box aspect is fixed at 1.5:1 and the source is cropped *into* it,
so letting the source's own aspect drive the height reproduces a different crop. The two
crops compose — cover into a 1.5:1 box, then the frame's window over that — and the net
visible region is a square ~39.4% of the source centred on (52.7%, 39.2%).

⚠️ **`max-width: none` is load-bearing.** The base stylesheet's `img { max-width: 100% }`
clamps the 253.5% down to the frame's own width, which leaves a ~54px sliver of the image
inside a 332px frame and looks like a broken crop, not a CSS conflict. Any zoomed crop has
to opt out of that guard explicitly.

⚠️ The Figma layer is `blendMode: LINEAR_BURN`, which **has no CSS equivalent** — the
`mix-blend-mode` keyword list has no linear-burn. `multiply` is the nearest neighbour and
is what ships. Recorded in §13 as a gap, not passed off as a match.

### CTA banner — added v2.1.0

Figma `1130:3996` (`Frame 37`). A **container-width card, not a full-bleed band** — 1376
wide, radius `--radius-lg`, a photo background under a dark scrim, cream type centred on
it. The band around it is full-bleed and carries nothing but two hairlines.

| Part | Spec |
|---|---|
| Band | `1px --border` rule top **and** bottom, `--space-4` between each rule and the card |
| Card | 1376 × **512** (`min-height`, so a longer heading grows it), radius 24, `--ink` beneath the photo |
| Content | 925 wide, centred, `--space-8` above and below, `--space-5` between heading and button |
| Heading | Desktop/H1 — 48 / 600 / 95% / −4%, **centred**, `--canvas` |
| Button | The **shared site CTA**, cream fill / ink label (`.btn--on-accent`), 224 wide |

- **The hairlines belong to this section, not the one above.** Figma draws them as two
  full-bleed vectors 32px either side of the card, and the Divi section it replaces
  carries the identical `border_width_top/bottom: 1px #D6D0C6` with 32px padding. The
  120px of air above the top rule is the previous section's bottom padding.
- **`padding-inline` must go to 0 at desktop.** The mobile gutter that keeps type off the
  card edge otherwise eats 40px of the 925 cap, breaks the heading onto a fifth line and
  grows the card from 512 to 556.
- **The button is never a section field.** header-standard requires header / hero / footer
  / banner to be one funnel; this reads `ak_primary_cta()`, so its label is a Polylang
  string and its URL is chrome config — already correct in every language.

**The scrim.** `#0D0D0D`, opaque → transparent, stops `0 / 11.9 / 25.6 / 63.7%`, running
**top-down**.

⚠️ Unlike `.hero`, this scrim is a **real element and must not be removed**. The hero's
identical gradient was dropped because it was already baked into the exported photograph;
this photo has no vignette at all — it is a bright shot of a desk, and the live Divi
version puts cream text straight onto it, where it is barely legible. Direction is
*derived*: the layer's `gradientTransform` makes the ramp depend only on the vertical
axis, but the node's reported origin sits exactly one height from its rendered box — the
signature of a vertical flip — so the file itself is ambiguous about which end is dark.
Top-down is the reading that serves the layout, since the heading occupies the upper two
thirds and the button is a solid fill that needs no backdrop.

### Services grid — added v2.1.0

Figma `1136:9156`. A centred lede over a **3 × 3 grid of 448×342 cards, gap 16**
(448·3 + 16·2 = 1376 exactly). Band `--space-8` top / `--space-6` bottom with a **bottom
rule only** — the hairline above it belongs to the CTA banner, which draws both of its own.

| Part | Spec |
|---|---|
| Lede | 540 wide, centred; H1 + Body, both centred, `--space-3` apart, `--space-7` to the grid |
| Service card | `--surface` fill, radius 24, **no border**, padding `--space-3`; icon 48×48 at the top, text pinned to the **bottom** |
| Title | Desktop/H3 — 24 / 700 / 116% / −1%, `--ink` |
| Description | Desktop/Body at **80% layer opacity** (Figma fades the layer, as with the credential numeral — reproduce as `opacity`, not a new tint) |
| Illustration card | **Transparent with a 1px `--border` hairline**, artwork centred |

🔒 **The illustration card deliberately INVERTS the panel rule.** Every other panel in this
system is a fill with no border (§4 — "distinguished by radius and hairline, not by
contrast"); these are the reverse, so the artwork reads as sitting *on the page* rather
than inside a card. **That contrast between the two card shapes is the point of the grid**,
and it is exactly what the current Divi version loses by filling all nine identically.

- **Card order is the composition.** Illustrations sit at positions 3, 4 and 8 so the grid
  reads as a checkerboard rather than "cards, then pictures". One list, not two.
- **Zero the `li` padding AND margin.** Divi indents `.entry-content ul` from the left, and
  on a grid that comes out of the *tracks*: every card rendered 448 → 443 and the whole
  grid sat 16px right of the container. See the `--about` note in §7 and `_divi-compat.scss`.

### Inverted card — "Book a consultation" — added v2.1.0

Figma `1136:9153`. A lede on the cream page over a **full-container green card** — the
band itself has no background, the card does. `--space-8` above, `0` below.

| Part | Spec |
|---|---|
| Lede | heading 506 left · intro 680 starting at **x=696**, the shared section spine |
| Card | 1376 wide, fill `--accent`, radius `--radius-lg`, `--space-7` below the lede |
| Content column | 696 track, inset `--space-5` (40) — card heading (H2, `--canvas`), checklist, CTA |
| Photo column | 680 track, inset `--space-3` (24) on its three OUTER edges only |
| Checklist row | 20×20 `lucide/check` + title (Body Small SemiBold) / body (Body Small at **70%**), gap 12 |
| Row rule | 1px **`--accent-light`** between rows, `--space-3` either side |
| CTA | `.btn--on-accent` — cream fill on green, the shared site CTA |

🔒 **This is the first correct use of `--accent-light`.** §2 defines it as *"stroke on a
green background"* and v1.0.0 had dismissed it as noise because production used it once.
Here it is doing exactly its stated job, which is the confirmation the v2.0.0 correction
was right.

- **The lede sits OUTSIDE the card.** In the Divi original the heading and intro are inside
  the green panel; the redesign lifts them onto the cream page and leaves the card holding
  only the checklist, button and photo. That is the actual change — the rest is a restyle.
- **The two insets differ (40 content / 24 photo)**, so the padding belongs on the two
  children, not on the card. One card padding cannot express both.
- ⚠️ **The check mark is drawn, never typed.** The Divi original opens each title with a
  literal ✅ emoji: a colour glyph that ignores the palette, renders differently per
  platform, and is announced as "white heavy check mark". An inline SVG inherits
  `currentColor` and is `aria-hidden`, so the list reads as a list.

### Header / navigation — added v2.0.0

Behaviour and a11y are governed by **`vibe-frontend-standards/references/header-standard.md`**
(a hard rule). The values below are the *look*; that document owns the *mechanics*.
The design already satisfies its two hardest requirements — mobile drill-down and a
pinned action bar — so there is no conflict to resolve.

**Desktop** — Figma `1163:952`

| Part | Spec |
|---|---|
| Band | 1440×**78**, fill `--canvas`. A bottom stroke exists in the file but is set **invisible** — so: no separator. |
| Logo | `x=32`, 216×32, vertically centred |
| Nav | centred, horizontal auto-layout, **gap 24**, 6 items, labels use the `ui` role (16/600/-0.01em) |
| Right cluster | 288×48, gap **32**: language switcher + primary CTA |
| Dropdown panel | **548×176** (`1163:1809`), cream, one column of 4 links. Per header-standard §2 this is a **simple dropdown**, not a mega-menu — 4 flat links. |

**Mobile** — Figma `1166:2456` (root) and `1166:2560` (drill-down)

| Part | Spec |
|---|---|
| Band | 375×**61**, fill `--canvas` — stays cream even while the drawer is open |
| Gutters | header row **16px** (logo `x=16`, close right edge 359); drawer content **20px** |
| Logo | 168×24.9 |
| Language flag | **32×32** circle |
| Close button | **42×42**, radius `--radius-sm` (8px), fill `--accent` |
| Panel | **full-viewport-width**, fill `--accent` (green), cream text |
| Content top | `y=93` (61 header + 32) |
| Items | large left-aligned links; `Portugal` carries a **`chevron-right`** → drill-down |
| Below the divider | Телеграм · Інстаграм · phone · email |
| Drill-down | sub-panel with a **`‹ Назад`** row, then the 4 guide links |
| Pinned action bar | **88px** tall, full width, holds the CTA at **335×48** with a 20px inset. The root panel scrolls *under* it. |

⚠️ **Two things to fix while building, not to copy verbatim:**

1. **The flag button is a 32×32 tap target.** header-standard §5 requires **≥40×40**.
   Keep the 32px visual, pad the hit area to 40. `$icon-btn-visual` / `$tap-min`.
2. **The close button's fill is `#307156`** — the same one-digit drift as the primary
   button and `et_divi`'s `footer_bg`. Use `--accent` `#307155`.

Not in the design and therefore decided by header-standard: sticky behaviour, `aria-expanded`
/ `aria-controls`, chevron **rotation** (not glyph swap), one-open-at-a-time, Escape and
click-outside close, body-scroll-lock, focus into/out of the drawer, `prefers-reduced-motion`
fallbacks. **`Portugal` is a `<button>`, not a link** — Petr's decision, 2026-07-27; it may
become an archive later, and a non-navigating trigger is the more accessible default anyway.

### Tabs · Tables

**Neither exists on this site.** No `<table>` and no tab component was measured. If one is
needed: build it from `--surface` + `--border` + `--radius-lg`, type role `body` for cells
and `small` (14/700) for headers, and **add the spec to this section** before shipping.

### Third-party widget — Review Wall

The Review Wall Google widget renders with its own system: **Roboto Flex**, `#1A7BFF`
button, `32px` radius, `14px / 700`, `48px` height, `14px 28px` padding. **This is
foreign and is not part of the contract.** The only project CSS that exists (CodeKit
post 1907) is a `.page-id-2133` override forcing its header background transparent so it
sits on the cream canvas. Any further Review Wall styling follows that same pattern:
page-scoped override in CodeKit, never a change to the widget's own defaults.

---

## 8. Imagery

- **What:** portrait photography of the principal (Anna), plus flat SVG icons and small
  illustrative marks. 69 attachments: 20 SVG, 20 JPEG, 15 WebP, 13 PNG, 1 GIF.
  The homepage measures **17 image modules** across 9 sections.
- **Where from:** client-supplied. No stock library in use.
- **Treatment:** images sit inside `--radius-lg` (24px) or `--radius-circle` (50%)
  containers. **No filters, no duotone, no overlay tints** were measured. Photography is
  presented straight.
- **Logo:** `uploads/2025/07/Anna-Kalynyuk.svg` — SVG, set as `et_divi.divi_logo`.
- **Format policy:** WebP where Imagify has converted; SVG for all icons and the logo
  (SVG upload is enabled via the SVG Support plugin).
- **Banned:** background images on sections (§5 — currently zero, keep it that way),
  gradient overlays, stock photography of generic "business handshake" character, and any
  decorative image that carries no information on a page already at 199 KB of markup.
- **Runtime loading:** ⚠️ WP Rocket's `lazyload` is currently **off**. New image-heavy
  work must set explicit `width`/`height` (WP Rocket's `image_dimensions` is on) and
  should assume lazy-loading may be switched on later — so never rely on eager loading
  for above-the-fold correctness.

---

## 9. Motion

### Budget: ~~2 moments~~ **3 moments.** All three are spent.

1. **The page preloader** — a full-page overlay (`the-preloader` plugin) before first paint.
2. **Hover / state transitions** on interactive elements.
3. **The `trust` accordion's expand / collapse** — `block-size` on `::details-content`,
   `--dur-slow`. **Added by Petr, 2026-08-04**, raising the budget from two.

There is still no scroll-triggered animation system, no stagger, no parallax, and no
reveal-on-scroll. **Do not add a fourth.**

⚠️ **Why this is recorded rather than quietly built.** The two-moment budget was stated
here as a rule, and the accordion was built without an animation *because of it* — the
"no expand animation" note is still in that section's git history. Raising a locked budget
is a design decision, so it belongs in the contract with a name and a date on it. A build
that silently exceeds a documented limit leaves the next person unable to tell an
intentional exception from drift.

Two conditions came with it, and both are met:

- **It is the only thing that moves in that component.** The plus/minus marker swap stays
  instant — a moving icon on top of a moving panel is two things competing for one glance.
- **It is switchable off.** `@media (prefers-reduced-motion: reduce)` zeroes the
  transition. Any future motion must do the same; §9's reduced-motion clause is the price
  of admission, not a nicety.

### Allowed

Measured deliberate transitions, in order of frequency:

```css
/* interactive state change — the default */
transition: opacity 0.2s ease-in-out, background-color 0.2s ease-in-out;  /* n=24 */

/* slower, for larger elements */
transition: 0.4s ease-in-out;   /* n=24 */
transition: 0.3s ease-out;      /* n=18 */

/* fast, for small controls */
transition: 0.15s;              /* n=10 */
```

**Durations: 0.15s / 0.2s / 0.3s / 0.4s. Easing: `ease-in-out` or `ease-out`.** Nothing
else.

One Divi scroll effect is configured on the hero (`scroll_blur="0|7|99|100|10|0|0"`) — a
blur-on-scroll on the hero section. It is pre-existing; **do not extend it to other
sections.**

### Banned

- **`transition: all`.** Measured on **1,308 elements** — this is Divi's default and it is
  a defect, not a decision. Never write it in new CSS; always name the properties.
- Scroll-jacking, parallax, marquees, autoplaying carousels.
- Any animation over 0.4s.
- `transform`-based entrance animations (measured `transition: transform` n=21, but no
  keyframe animation exists — don't start one).

### Reduced motion

**Not currently implemented anywhere.** Any new motion must ship with:

```css
@media (prefers-reduced-motion: reduce) {
  *, *::before, *::after { animation: none !important; transition-duration: 0.01ms !important; }
}
```

This is a gap, recorded in §13.

---

## 10. Do / Don't

### Do

- Default a new section to `--canvas` `#F7F2E9` and declare **no background**.
- Use `--accent` `#307155` only for a band, a primary button fill, an emphasis rule, or an icon.
- Set display headings at **48px / 600 / line-height 1.0 / tracking −0.04em**.
- Pick body line-height by reading length: **1.4** for UI copy, **1.625** for long-form.
- Give tracking `normal` to anything ≤18px and negative tracking to anything ≥20px.
- Build panels as `#FEF8EF` + `1px solid #D6D0C6` + `24px` radius + `24px` padding.
- Give every button **radius 24px** and label **16px / 600 / -0.01em**. The 493×57 wide variant keeps radius 24 (a rounded rectangle, not a pill).
- Outline panels rather than filling them when they sit on a green band.
- Cap running prose at **680px** even though the content column is 1368px.
- Name transition properties explicitly, at 0.15/0.2/0.3/0.4s.
- Put new CSS in **CodeKit post 1907**, never the child `style.css` (see `docs/03-theme-architecture.md`).

### Don't

- Don't add a **third section background**. Two bands, both spent.
- Don't add a **second accent hue** — including the stray `#609079`.
- Don't add a **radius step**. The system is 8 / 16 / 24 / 50px / 50%; the stray 3, 6, 12, 20, 32 are noise.
- Don't add a **second shadow value**. One shadow: `0 1px 4px rgba(18,25,97,0.08)`.
- Don't add a **second typeface** — no serif, no mono, and no Roboto Flex outside the Review Wall widget.
- Don't use **weight 300 or weight 500 at all** — neither exists in the kit. Only 400 / 600 / 700.
- Don't tighten tracking on body copy.
- Don't write **`transition: all`**.
- Don't add a **gradient** or a **section background image** — the site has zero of both.
- Don't introduce **uppercase eyebrow micro-labels**; `text-transform` is `none` everywhere.
- Don't sample colours from the Review Wall widget (`#1A7BFF`) or from `#000000`.
- Don't let a new grid shape be a fourth option — full-width, 50/50, or 2-up/3-up cards.

---

## 11. Agent prompt guide

The eight values to paste inline when briefing a build:

```
Canvas #F7F2E9 · ink #2A2011 · one accent #307155 · panel #FEF8EF
Nunito Sans only — display 48/600/lh1.0/ls-0.04em · body 16/400/lh1.4 (1.625 long-form)
Radius 8/16/24 (no pill) · buttons = radius 24, 48px tall, label 16/600/-0.01em
Panels: #FEF8EF + 1px #D6D0C6 + 24px radius + 24px padding
Two section backgrounds only: cream #F7F2E9 (default) or green #307155 (hero/mission/CTA)
Border-first, not shadow-first. One shadow: 0 1px 4px rgba(18,25,97,.08)
Section padding 120/50/40/32 · mobile gutter 20px · content 1368px, prose capped 680px
Motion: 0.2s ease-in-out on opacity + background-color. Never `transition: all`.
```

### Worked example — a new 3-up "why us" card row on canvas

> Build a section on the default cream canvas `#F7F2E9`, no background declared, padding
> `50px` top and bottom, content capped at 1600px with a 20px mobile gutter. Heading in
> Nunito Sans 48px/600, line-height 48px, letter-spacing −1.92px, colour `#2A2011`,
> left-aligned. Below it a 3-up card grid with a 32px gap. Each card: background
> `#FEF8EF`, `1px solid #D6D0C6`, radius 24px, padding 24px. Card title Nunito Sans
> 24px/700, line-height 27.84px, letter-spacing −0.24px, `#2A2011`. Card body 16px/400,
> line-height 22.4px, no tracking, `#2A2011`. One SVG icon per card at the top, coloured
> `#307155`, inside a 50%-radius container. No shadows. On phone, stack to 1-up with a
> 16px gap.

### Worked example — a green CTA band with a form

> Build an inverted band: background `#307155`, padding `120px` top / `50px` bottom, no
> gradient, no background image. Heading Nunito Sans 48px/600/lh 48px/ls −1.92px in
> `#F7F2E9`. Supporting copy 20px/400/lh 26px/ls −0.2px in `rgba(247,242,233,0.56)`.
> Form inputs: **transparent** fill, `1px solid rgba(247,242,233,0.24)`, radius 16px,
> padding `24px 19px`, height 50px, text `#F7F2E9`, 16px/400/ls −0.16px. Submit button
> full-width: background `#2A2011`, colour `#FEF8EF`, radius **24px**, padding 19px,
> height 54px, 16px/600. Secondary action as an on-dark ghost pill: transparent,
> `1px solid #F7F2E9`, `#F7F2E9` text, 50px radius, `10px 20px`, height 49px, 16px/500.
> Transitions `opacity 0.2s ease-in-out, background-color 0.2s ease-in-out` only.

---

## 12. Code export

```css
:root {
  /* ── Colour ─────────────────────────────────────── */
  --ink:            #2A2011;
  --ink-muted:      rgba(42, 32, 17, 0.56);
  --ink-soft:       rgba(42, 32, 17, 0.50);  /* part of the SAME line — see §2 */
  --canvas:         #F7F2E9;   /* the page */
  --canvas-muted:   rgba(247, 242, 233, 0.56);
  --surface:        #FEF8EF;   /* panels, inputs */
  --surface-sunken: #EEEADC;
  --accent:         #307155;   /* the ONE accent */
  --border:         #D6D0C6;
  --border-soft:    rgba(42, 32, 17, 0.24);
  --border-inverse: rgba(247, 242, 233, 0.24);

  /* ── Type ───────────────────────────────────────── */
  --font: "Nunito Sans", sans-serif;

  --fs-display:   48px;  --lh-display:   48px;    --ls-display: -1.92px;
  --fs-stat:      34px;  --lh-stat:      34px;    --ls-stat:    -0.2px;
  --fs-title:     24px;  --lh-title:     27.84px; --ls-title:   -0.24px;
  --fs-lead:      20px;  --lh-lead:      26px;    --ls-lead:    -0.2px;
  --fs-body:      16px;  --lh-body:      22.4px;
  --lh-body-long: 26px;                           --ls-body-long: -0.16px;
  --fs-small:     14px;  --lh-small:     16.24px;
  --fs-micro:     12px;  --lh-micro:     17.16px;

  --fw-body: 400;  --fw-ui: 500;  --fw-display: 600;  --fw-bold: 700;

  /* ── Space ──────────────────────────────────────── */
  --space-1:  8px;
  --space-2: 16px;
  --space-3: 24px;
  --space-4: 32px;
  --space-5: 40px;
  --space-6: 50px;
  --space-7: 56px;
  --space-8: 120px;
  --gutter:  20px;

  /* ── Shape ──────────────────────────────────────── */
  --radius-sm:     8px;
  --radius-md:    16px;
  --radius-lg:    24px;
  /* --radius-pill removed in v2.0.0 — see the header note */
  --radius-circle: 50%;

  /* ── Elevation — one shadow only ────────────────── */
  --shadow: 0 1px 4px rgba(18, 25, 97, 0.08);

  /* ── Layout ─────────────────────────────────────── */
  --container-max: 1600px;
  --content-max:   1368px;
  --measure-max:    680px;   /* cap for running prose */

  /* ── Motion ─────────────────────────────────────── */
  --ease:       ease-in-out;
  --dur-fast:   0.15s;
  --dur-base:   0.2s;
  --dur-slow:   0.3s;
  --dur-slower: 0.4s;
}

body {
  background: var(--canvas);
  color: var(--ink);
  font: var(--fw-body) var(--fs-body)/var(--lh-body) var(--font);
}

@media (prefers-reduced-motion: reduce) {
  *, *::before, *::after {
    animation: none !important;
    transition-duration: 0.01ms !important;
  }
}
```

### Font loading

Nunito Sans is currently loaded twice (Divi enqueue + a CSS `@import`). **One source
only** in new work — preconnect + a single `<link>`:

```html
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Nunito+Sans:opsz,wght@6..12,300;6..12,400;6..12,600;6..12,700&display=swap">
```

Better still, host locally (WP Rocket's `host_fonts_locally` is currently `0`) and drop
both remote requests. Also note weight **300 is requested but never used** — it can come
out of the URL.

---

## 13. Provenance

| Source | What it gave |
|---|---|
| Playwright computed-style scrape of `https://www.kalynyuk.com/` @ 1440×1000, 2026-07-27 | **[measured: production homepage]** — all colour, type, radius, shadow, border, button, form, container, section-padding, transition and font values in §2–§9 |
| `document.fonts` + woff2 response capture, same run | Loaded families and weights: Nunito Sans 300/400/600/700, Roboto Flex, ETmodules, gform-icons-orbital |
| Section-background audit, same run | Canvas rule: 13 sections → 2 distinct backgrounds → **PASS** |
| `et_divi` option (215 keys) via WP-CLI | Divi Theme Options equivalents: logo, `header_style: left`, `body_font`/`heading_font: Nunito Sans`, `body_font_size: 16`, `body_header_size: 47`, `accent_color`, `secondary_accent_color`, `primary_nav_bg`, `footer_bg` |
| `wp-content/custom_codes/1907-scss-desktop.scss` | The project's entire custom stylesheet — 7 lines |
| Divi shortcode attributes on page 11 (hero section) | `max_width="1600px"`, `min_height="730px"`, `custom_padding="40px||32px||false|true"`, `custom_padding_phone="40px|20px|32px|20px"` → the 20px mobile gutter, `scroll_blur` on the hero |

### Known gaps

1. **There is no stylesheet implementing this system.** Every value above lives in Divi
   module settings or the `et_divi` option. The `:root` block in §12 is a **new artifact
   of this document** — nothing on the site consumes it yet. Treat §12 as the target, and
   as the reference when authoring Divi module values by hand.
2. **The shadow hue is wrong.** `rgba(18,25,97,0.08)` is a navy blue with no relationship
   to a palette built on cream, warm brown, and forest green. It is a Divi preset that was
   never adjusted. Documented as-shipped for consistency; **this is the one value in this
   contract that should probably be changed** (to something like `rgba(42,32,17,0.08)`).
   Changing it is a design decision — log it as `[DECISION]`.
3. **`footer_bg` is `#307156`, one digit off `accent_color`'s `#307155`.** The footer
   renders `#307155`, so the stored value is dead. A typo, not a token.
4. **Five stray radius values** — 3, 6, 12, 20, 32px — with no logic behind them. Not
   codified above. Their sources weren't traced.
5. **`transition: all` on 1,308 elements.** Divi's default. Can't be removed without
   touching the parent theme; new work must not add to it.
6. **No reduced-motion support anywhere** on the live site. The `@media
   (prefers-reduced-motion)` block in §12 is a proposal, not a measurement.
7. **No secondary button variant on canvas** was measured. The inverted-ghost fallback in
   §7 is a derivation, not a measurement — mark it as such if you ship it, and add the real
   spec back here.
8. **No tab or table component exists.** §7 says to define one before shipping. Not
   invented here.
9. **Only the desktop breakpoint was measured.** Tablet (768–980) and phone (≤767) values
   come from Divi per-module settings that were not enumerated. The 20px mobile gutter is
   the one mobile value with a source. **Rescan at 375px and 768px before doing responsive
   work.**
10. **Only the homepage was measured.** The FAQ (2440), Services (2423), About (2409),
    Trust (2435), Reviews (2133) and Calculator (566) pages were not scraped, and the
    calculator carries ~95 KB of its own CSS in Divi code modules
    (see `docs/05-issues.md`). **The calculator almost certainly has values not in this
    contract.** Rescan Stage 6 against `/calc/` before touching it.
11. **The Review Wall widget runs its own design system** (Roboto Flex, `#1A7BFF`, 32px
    radius) and is deliberately excluded. Only page-scoped CodeKit overrides bring it
    closer to the contract.
12. **Weight 300 is loaded and unused.** Declined — do not start using it; remove it from
    the font request instead.
13. **The `about` band has no mobile frame** — added v2.1.0. Searched the whole design
    page: «Офіційна реєстрація» / «Читати про мене» exist only in the desktop `Main`
    (`1130:3906`) and its stale duplicate `1163:950`. The credential row's mobile collapse
    (numeral inline with the label, body below) is **Petr's decision of 2026-08-03**, not
    a measurement. If a mobile frame lands later, reconcile against it.
14. **The credential-row body copy is `#000000` in Figma** — added v2.1.0. All four rows,
    consistently, while the label 400px to its left is `--ink` `#2A2011`. Pure black is
    not in the palette and §2 bans it, so the build ships `--ink`. Treated as a design-file
    slip; if it turns out to be deliberate, it belongs in §2 as a token first.
15. **`LINEAR_BURN` has no CSS equivalent** — added v2.1.0. The portrait in the `about`
    band uses it; `mix-blend-mode: multiply` ships as the nearest neighbour. The two are
    not the same operation and will differ in the shadows. The CTA banner's photo carries
    the same blend mode in Figma and does **not** reproduce it — over a dark scrim it
    would be invisible, and the scrim already does the darkening.
16. **The CTA banner's Figma crop does not match the shipped asset** — added v2.1.0. The
    frame holds the photo in a 1419×946 box (1.5:1) offset upward, but the attachment that
    actually ships is **2560×867** — 2.95:1, very nearly the card's own 2.69:1. A
    percentage crop built for a 1.5:1 box would discard most of the image, and the card's
    aspect ratio changes with the viewport anyway, so plain `object-fit: cover` +
    `object-position: center` ships instead. Either the design file holds an older asset
    or the export was recropped; worth reconciling if the photo is ever replaced.
17. **The scrim direction on the CTA banner is derived, not read** — added v2.1.0. See §7
    → "CTA banner". If the Figma layer's flip state is ever confirmed, check this against
    it rather than assuming the build is right.
18. **The services illustrations do not match their Figma frames** — added v2.1.0, and this
    one is a real asset gap rather than a note. The frame bleeds each illustration out of a
    box that overhangs the card differently per card — `362×542` at (43, −62), `265×397` at
    (356, −14), `342×511` at (405, −62) — all three at a **0.668** ratio. The SVGs that
    actually ship are `Group-17` 200×277, `Group-16` 292×223 and `Group-18` 288×243:
    **three different ratios, none of them 0.668.** They are not the artwork the frame was
    composed around, so no crop reproduces it, and the build centres them inside the card
    instead. **To get the bleeding composition, the illustrations must be re-exported from
    Figma at the frame's dimensions**; the CSS then becomes a per-card offset.
19. ~~**Which illustration goes in which slot is inferred**~~ — **CLOSED 2026-08-03.** The
    Figma frames are named after ChatGPT export timestamps and the library files after
    `Group-1x`, so there was nothing to match on; the first pass assigned them in the order
    Divi renders them and got slots 3 and 4 the wrong way round. Petr corrected it by eye.
    **The canonical order is `Group-16`, `Group-17`, `Group-18` → slots 3, 4, 8** — i.e.
    the woman at a laptop top-right, the walking woman below-left, the woman signing in the
    bottom row. Any re-seed (production included) must use this order, not Divi's.
20. **⚠️ THE PATTERN behind gaps 16 and 18: this design file's image frames routinely do
    not match the shipped assets** — added v2.1.0, after it happened a third time. The
    `trust` photo is framed as an 887×1331 crop (0.666) while the attachment is 1020×1101
    (0.926). Together with the CTA banner (frame 1.5:1, asset 2.95:1) and the services
    illustrations (frame 0.668, assets 1.31 / 0.72 / 1.19), **three of the four image
    placements in this migration were composed around artwork that was never exported.**
    Treat any Figma crop geometry here as a hint, not a spec: check the real attachment's
    dimensions FIRST and reproduce the crop only when the ratios are close. Where they are
    not, plain `object-fit: cover` is the honest answer — and the `.about` portrait is the
    counter-example proving the rule, since there the ratios did match and the crop was
    worth reproducing exactly.
