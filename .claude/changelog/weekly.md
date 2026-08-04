# Weekly Changelog — Anna Kalynyuk

<!-- Entries go under a `## Week YYYY-Www` header (ISO week). When a new
week starts, this file is compressed into changelog.md. See README.md for the protocol. -->

## Week 2026-W32

**The homepage went from five Divi sections to zero.** Rebuilt `about`, `cta`, `services`,
`trust`, `order`, `testimonials` and `faq` natively, in Ukrainian and Portuguese, plus two
production deploys. The calculator was deliberately held back — it is still Divi on
production and continues in its own session.

### The mechanism grew twice

- **Repeater support** — `ak_section_rows()`. ACF flattens a repeater into one meta row per cell, which is what makes the multilingual fallback work at all: each cell reads through `ak_section_field()`, so a translation that has localised two of four labels shows those two and inherits the rest.
- **[DECISION] A second addressing scheme, `label:Order = order`.** Three sections (`Order`, `Reviews`, `FAQ`) have no `module_id` and are not leading sections, so neither existing mechanism could reach them. The alternative was writing a CSS ID into `post_content` — the exact thing `native-sections.php` exists to avoid. `admin_label` is already in the shortcode and costs no database write. **`module_id` stays preferred**: `admin_label` is a display name an editor can rename without realising it addresses anything.

### Content decisions (all Petr's)

- **`services`: Figma's copy wins over Divi's.** Divi carried 8 long keyword-dense cards, one of them duplicated; the grid is a fixed 3×3 with three illustration slots, so 8 did not fit. Nothing is deleted — clearing the map restores the old copy whole.
- **`faq`: Divi's copy wins over Figma's** — the opposite call, for the opposite reason. The design has 8 questions but only ONE answer, and four have no counterpart in Divi, so shipping it would have meant *writing* interest rates and tax bands for a licensed credit intermediary.
- **`about` mobile collapse** (numeral inline with the label, body below, button last) — no mobile frame exists anywhere in the design file.
- **The `trust` accordion animates open/close — a THIRD motion moment** against §9's locked budget of two. §9 was amended rather than left to contradict the code, with two conditions met: it is the only thing that moves in the component, and it is switchable off via `prefers-reduced-motion`.

### Design-contract changes

- **[DECISION] `--space-7: 56px`** — fills the one hole in the 8px scale; added to the contract *before* use.
- **[DECISION] `--ink-soft: rgba(42,32,17,0.50)`** — a third ink alpha, 0.06 from `--ink-muted`. The distinction is recorded as one of ROLE: `-muted` is a separate secondary element, `-soft` is part of the *same line*.
- **`--accent-light` finally used as intended** — the row rule inside the green `order` card. §2 defines it as "stroke on a green background" and v1.0.0 had wrongly dismissed it as noise.
- **§7 gained five component specs** (credential row, portrait crop, CTA banner, services grid, testimonial card, FAQ accordion) and **§13 gained six gaps** (13–22), including the pattern below.
- ⚠️ **Gap 20 — this design file's image frames routinely do not match the shipped assets.** Three of four image placements were composed around artwork that was never exported. Treat Figma crop geometry as a hint: check the attachment's real dimensions first, reproduce the crop only when the ratios are close.

### Two plugin defects fixed by rebuilding around them

- **Review Wall's heading and button are `get_option()`** — one global value for all four languages, and its shortcode takes no arguments. Shipped values were «Подивіться, що кажуть наші клієнти» and, on a Ukrainian site, the English "Review us on Google". The rebuild leaves the plugin owning the DATA (so Google sync keeps working) and gives the theme every string. Translations were **filled in**, not merely enabled.
- The rating label is a **format string** (`%s Оцінка` → `Avaliação %s`) following the existing `ak_read_minutes_*` convention — word order around a number differs by language.

### Bugs worth remembering

- **Divi indents `.entry-content ul`/`ol` and outspecifies our classes.** On a grid the indent comes out of the *tracks*: services cards rendered 443 wide against 448 and the whole grid sat 16px right. Three sections have needed the fix; every future `ul`/`ol` section will too.
- **A zoomed crop must opt out of `img { max-width: 100% }`** — it clamped the `about` portrait to a 54px sliver, which reads as a broken image rather than a CSS conflict.
- **`minmax(0, …)` on scroller track columns silently kills the overflow** — the testimonials cards shrank to 139px and the row never scrolled.
- **Mixing one explicitly-placed grid child with auto-placed siblings** put the testimonial rating under the avatar.
- **I read Figma's optical nudges as baseline alignment and was wrong** — measured, `align-items: baseline` was further from the design than `start`.
- **[BUG in my own docs] "Leave empty and the Divi original comes back" was false in eight ACF instructions.** The MAP removes the Divi section; the template merely declines to draw. Only the leading-strip route restores. Corrected — editor instructions that are confidently wrong are worse than absent ones.

### Deploys

Two production deploys (`about` + `cta`; then `services`). Both stopped short of the calculator.

- **[BUG] Deploying code alone would have left the homepage with NO hero.** Production carried the legacy numeric `ak_native_sections = 1` and no `ak_sections`, so the new code would strip the Divi hero through the legacy fallback and render nothing in its place — on both languages. Not reproducible locally.
- **The deploy ORDER follows from what the old code does with the new meta, and it is not a ritual.** Deploy #1 needed meta-first; deploy #2 needed files-first (a repeater cannot be pre-written, because `update_field()` on an unregistered group stores a serialised array instead of flattened cells).
- **[BUG] A hardcoded attachment ID shipped a stale photo to production** — captured before the photo was swapped. It passed every structural assertion; only a local-vs-production computed-style diff caught it. **That diff is now standard practice**, and seeds resolve attachments by slug.
- `ci-cd.md` had read "🚫 DEPLOYS BLOCKED" for four days after both blockers closed, while being the file every dev skill reads first. Corrected, with the real server, deploy method (tar + scp — `rsync` is not on this workstation) and the fact that **production has no page cache**.

### Open

- ⚠️ **Stale Divi CSS survives section removal.** `.contact-form .ginput_container_checkbox { position: absolute; bottom: 40px !important }` still ships in Divi's cached inline styles for page 11, from a module no longer in `post_content`. With no positioned ancestor it anchors to `.et_builder_inner_content`, so the calculator's consent checkbox floats over the FAQ. **Removing a Divi section at render time does not remove its generated CSS** — expect more orphan rules. Left for the calculator session (Petr).
- The testimonial card's location line has no data source; the FAQ design's question list has no answers. Both recorded in §13 rather than invented.
