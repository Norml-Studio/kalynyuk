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

### The last four Divi sections went, and the homepage reached ONE

Built `trust`, `order`, `testimonials`, `faq` and `seo` natively in both languages and
deployed all five. **The homepage now runs one Divi section: the calculator.**

- **A THIRD placement route, `ak_append_sections`.** `seo` is the first section the Divi page never had, and both existing routes need something to anchor to. Renders after the content at **priority 21** — after `wpautop` (10) and after the token swap (20) — so the markup never meets a content filter. **The alternative was rejected on principle:** inventing a Divi section in `post_content` just to have something to replace would write to the very blob `native-sections.php` exists to leave alone, and leave a decoy for the next person.
- **[DECISION] The `h1` stays on the hero; the SEO block keeps its `h2`.** Checked rather than guessed: RankMath's focus keyword on page 11 is «Іпотека в Португалії» and the hero `h1` already opens with that phrase verbatim, so the swap buys nothing on the axis it was proposed for — while costing an outline that opens on `h2` and puts its only `h1` ~9 500px down. It would also hard-code an exception into `ak_claim_h1()`, whose whole point is that a section cannot know its own rank.
- ⚠️ **`position: sticky` on the SEO heading was added and then removed.** The copy column runs ~1440px and it seemed a kindness, but a Figma frame cannot express scroll behaviour either way — "the design doesn't say" is not permission to invent it.
- **Header submenu: the reported "gap should be 16px" was already 16.** The rows were 40px tall against the design's 20, so the list measured 208 against 128 — bigger rows read as bigger spacing. The 40 came from `min-height: $tap-min`, whose own note says *not* to shrink a target to match the art, so both requirements were real and in conflict. **Resolved by separating flow height from hit area:** the row keeps 20px in flow, an absolutely-positioned `::after` grows the clickable box ±8 into the gaps. ⚠️ **±8 is the maximum that does not overlap** — the gap is 16, so adjacent targets meet exactly. Do not raise it chasing 40.
- **The FAQ was translated into Portuguese** — it had been Ukrainian under a Portuguese heading. Every figure carried across unchanged and spot-checked programmatically; this is regulated copy for a licensed credit intermediary and the Ukrainian original is the reviewed source.

### The footer, and the answer to "how does Gravity Forms work with Polylang?"

**It doesn't** — *Gravity Forms Multilingual* is a WPML product. GF's own interface strings are ordinary WordPress i18n and translate for free; form CONTENT lives in GF's tables where Polylang's string scanner never sees it. That is why "0 de 600 máximo de caracteres" was Portuguese while the label beside it was Ukrainian.

- **[DECISION] One form, filtered — not one form per language.** The usual workaround duplicates the form and picks it at render time; rejected, because four forms means four entry streams, four notification configs, and a field added to one and forgotten in the other three — for a four-field contact form. `ak_gform_translate()` filters the form OBJECT instead, so there stays one form and every string becomes editable in Polylang → Strings.
- ⚠️ **All three hooks are required.** `gform_pre_render` for what the visitor sees, `gform_pre_validation` to survive a failed submit, `gform_pre_submission_filter` to carry it into submission — and that last one is also how the **confirmation** gets translated, because `GFFormDisplay::process_form()` reads the confirmation out of the form object it returns. Deliberately **not** `gform_confirmation`: by then the message is merged with the entry and wrapped in GF markup.
- ⚠️ **A source string in the wrong language cannot be translated.** `ak_copyright` was registered with the *Portuguese* default, so uk rendered "© 2026 Todos os direitos reservados." and pt looked right only by accident. Polylang always returns the source for the default language. The email field's custom `errorMessage` had the same shape in English. Both fixed at the source.
- ⚠️ **Polylang returns 0, not the original, for an untranslated privacy page.** It filters `option_wp_page_for_privacy_policy` and hands back `0` when there is no translation; every caller reads that as "none configured", so the footer link **vanished** on pt/en/ru — and so did core's `get_privacy_policy_url()`. Fixed at the **option** level (capture at 19, restore at 21), not in `footer.php`, which was only where it was noticed.
- **Social labels came from an ACF options repeater** — global, one value for all languages, exactly what multilingual rule 3 warns against. Now routed through `ak_str()`, with rows registered so a *fifth* social an editor adds later appears in the Strings screen instead of failing silently.
- **Four `__()` strings reached assistive tech in English** — the theme ships no `languages/` folder, so every `__()` renders as its English source. Moved to `ak_str()`. **The language switcher announced "uk, pt" instead of "Українська, Português"**: it asked Polylang for `display_names_as => 'slug'`, and the switcher is flags-only, so those two visually-hidden spans are the *only* place a language is named in words.
- **[DECISION] The consent record stays as it is** (Petr): entries keep storing the Ukrainian consent text in every language, because only `choices[].text` is translated, never `value`, which keeps entries comparable across languages.
- ⚠️ **Translating form 1 also translated part of form 3.** `pll__()` matches on the SOURCE, and the calculator form's consent sentence is byte-identical to the footer's. Anything sharing a source across forms translates together.
- ⚠️ **A "valid" test email was rejected and it was not our bug** — `ak-test@example.com` fails `GF_Field_Email::is_email_rejected()`; GF blocklists the reserved domain. **Use a real domain when smoke-testing a GF email field.**

### Deploys #3 and #4

Five sections (uk + pt), then the footer translation. **Files first again** for #3 — without their meta the new templates render nothing, so the Divi originals simply keep rendering, verified between the steps.

- ⚠️ **File sync alone would have been WRONG for #4**, which is what made it different from #1–#3: two fixes rewrite *source* strings, and Polylang matches on the source, so shipping code without re-seeding leaves production with strings whose translations no longer resolve.
- **Every source string was read out of production's own database rather than reconstructed** — the consent sentence embeds an environment-specific absolute privacy URL and the confirmation carries a cream `<span>`. The seed swaps only the words between them and aborts if any source reads empty. **This is the discipline the deploy-#2 attachment-ID bug bought.**
- Post-deploy meta diff on #3: **zero unexpected differences.** Both deploys verified in both languages with zero console errors beyond the known Review Wall one.
- ⚠️ **A backup was written into the docroot and was briefly reachable over HTTP.** Moved to `~/` immediately. `ABSPATH` is a webroot — dump files belong in the home directory.
- `/en/` and `/ru/` return **404** — their front pages exist but are still drafts. Strings still resolve on those URLs because Polylang sets `curlang` from the path, which made the 404s look like working pages in an early check.

### 📌 The lesson that outlives the calculator: a Polylang translation copies `post_content`, NOT post meta

Petr reported the calculator "съехал" on `/pt/`. **Root cause: `_et_pb_custom_css` is post META.** Page 11 carries 24 163 chars of Divi *Page Settings → Custom CSS*; pages 2483 / 2484 / 2485 carry **0**. The `post_content` was duplicated faithfully and only the meta was left behind. 111 selectors missing, including `.input-block { display: flex; flex-direction: column }` — while `.interest-rate-wrapper { margin-top: -34px !important }` **survives**, because that one lives in the Code module's inline `<style>`, which *is* part of `post_content`. With no flex column to absorb it, the negative margin drags the radio row over its own label.

⚠️ Ruled out first, each being the more obvious suspect: not the footer deploy, not Divi's file cache (`et-cache/11/` and `et-cache/2483/` are byte-identical), not the native-sections mechanism.

⚠️ **A verbatim copy of the meta is only a HALF fix.** The 24 KB contains **40 Cyrillic runs** — tooltip bodies and the «років» suffix written as CSS `content:` strings. Copying it would fix the layout and simultaneously ship Ukrainian tooltip text onto the Portuguese page. **Recommendation taken: don't patch it, migrate it** — the native section fixes both at the root. Anything else living in meta silently does not exist on a translation; page **#566 (Калькулятор, 22 867 chars)** carries the same payload and is only safe because it has no translations *yet*.

### Open

- ⚠️ **Stale Divi CSS survives section removal — now diagnosed, not just observed.** `.contact-form .ginput_container_checkbox { position: absolute; bottom: 40px !important; max-width: 460px !important }` ships inside `<style id="et-builder-module-design-deferred-11-cached-inline-styles">` — Divi's generated CSS for page 11, from a module no longer in `post_content`. Its `offsetParent` is `div.et_builder_inner_content`, so with no positioned ancestor the `bottom: 40px` anchors to the whole content area and lands the checkbox over the FAQ. **Divi builds its dynamic stylesheet from `post_content` independently of our `the_content` filter**, so every migrated section may have left orphan rules behind. Worth a sweep during phase 4.
- The testimonial card's location line has no data source; the FAQ design's question list has no answers. Both recorded in §13 rather than invented.
- **GF ships no Ukrainian translation**, so its own strings stay English on `/`. Portuguese is complete. Fixing it means supplying a `gravityforms-uk.mo`.
- The **privacy policy page itself has no translations** — every language links to the Ukrainian one. Content gap, not a code gap.
- **`remodal.min.js` from the Review Wall plugin throws `b.map is not a function` on every page load.** Third-party, pre-existing. Not dequeued: the plugin's shortcode is still used on page 2133.
