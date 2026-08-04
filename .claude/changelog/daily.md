# Daily Changelog — Anna Kalynyuk

<!-- Entries go under a `## YYYY-MM-DD` header for today. When a new day
starts, this file is compressed into weekly.md. See README.md for the protocol. -->


## 2026-08-04

- Rolled the changelog per `README.md`: week **2026-W31** compressed into `changelog.md`, week **2026-W32** into `weekly.md`, and this file reset. Nothing was dropped that the compression rules protect — every `[DECISION]` and `[INTEGRATION]` from both weeks survives in the tier above.
- ⚠️ **Diagnosed the stray consent checkbox properly before parking it** (Petr: leave it for the calculator session). It is **not** plugin junk and **not** in `post_content`: the rule `.contact-form .ginput_container_checkbox { position: absolute; bottom: 40px !important; max-width: 460px !important; }` ships inside `<style id="et-builder-module-design-deferred-11-cached-inline-styles">` — Divi's generated CSS for page 11, from a module that no longer exists in the content. Its `offsetParent` is `div.et_builder_inner_content`, so with no positioned ancestor the `bottom: 40px` anchors to the whole content area and lands the checkbox over the FAQ.
  **The general lesson is bigger than this bug: removing a Divi section at render time does NOT remove the CSS Divi generated for it.** Divi builds its dynamic stylesheet from `post_content` independently of our `the_content` filter, so every migrated section may have left orphan rules behind. Worth a sweep during phase 4.

### De-Divi migration — `seo`, the long-form copy block. **A new section, not a replacement.**

Figma `1146:12634`. Heading left, four sub-headed prose blocks right at the shared x=696 spine. Ukrainian and Portuguese.

#### The mechanism grew a THIRD placement route

This is the first section the Divi page never had, and both existing routes need something to anchor to: `ak_sections` strips the leading N and draws in their place, `ak_inline_sections` swaps one out where it sits. A **new** section has nothing to replace.

Added `ak_append_sections` — a slug list rendered after the content on `the_content` at **priority 21**, i.e. after `wpautop` (10) and after the token swap (20), so the markup never meets a content filter. That is the same lesson `ak_section_token()` records: rendered HTML injected before `wpautop` comes back with stray paragraphs inside it.

**The alternative was rejected on principle:** inventing a Divi section in `post_content` purely so there would be something to replace would write to the blob this whole file exists to leave alone, and leave a decoy section for the next person to puzzle over.

#### Notable

- **Heading levels are the point here.** Section H2, blocks H3 — the outline stays one level deep rather than flattening. This is the only block whose job is to be read by a crawler as well as a person, and it sits last precisely so it does not compete with the page's selling sections.
- Block spacing (80) is deliberately larger than anything inside a block (32), so four sub-headings read as four topics rather than one list.
- ⚠️ **I added `position: sticky` to the heading and then removed it.** The copy column runs ~1440px and it seemed a kindness — but a Figma frame cannot express scroll behaviour either way, so "the design doesn't say" is not permission to invent it. Left static, as drawn, with the reasoning in the partial so it is not re-added by reflex.

#### Verified

- Placed **between the FAQ and the footer**: FAQ 8756–9483, seo 9483–11044, footer 11044. Heading 506 at the container edge, body 680 at x=726 — the same spine as `about`, `order` and `faq`.
- 4 blocks, 4 H3s, one H1 on the page. Columns 2 → 1 below desktop. **No `.seo` element overflows at 375 / 641 / 768 / 1024 / 1025 / 1280 / 1440 / 1600 / 2560.** No console errors.
- `/pt/` renders the whole block in Portuguese.

- **`seo` band closes with 120 below as well as above** (Petr). The footer has no top padding of its own, so a 0 there put the last paragraph hard against the green band — the only place on the page where two blocks touched with nothing between them. Verified: 120px from the last paragraph to the footer.
- **[DECISION] The `h1` stays on the hero; the SEO block keeps its `h2`.** Asked whether to swap them now that a keyword-rich block sits at the bottom. Checked rather than guessed: RankMath's focus keyword on page 11 is «Іпотека в Португалії», and the hero `h1` already opens with that phrase verbatim — so the swap buys nothing on the axis it was proposed for, while costing a document outline that opens on `h2` and puts its only `h1` ~9 500px down, where heading-navigation users reach it last. It would also mean hard-coding an exception into `ak_claim_h1()`, whose entire point is that a section cannot know its own rank and the first one rendered takes the `h1`. If the "для іноземців та нерезидентів" phrasing should carry more weight, the levers are the hero copy or a secondary-keyword target — not the heading level.

### 🚀 Production deploy #3 — trust, order, testimonials, faq, seo

All five live on `https://www.kalynyuk.com`, uk and pt. **The homepage now runs ONE Divi section: the calculator**, held back deliberately for its own session.

**Files first again**, same reasoning as deploy #2 and for the same two reasons: without their meta the new templates render nothing, so the Divi originals simply keep rendering (verified between the steps — after the sync and before seeding, production still showed `trust` + `calculator` as Divi with zero console errors); and four of the five sections use repeaters, which cannot be pre-written because `update_field()` on an unregistered field group stores a serialised array instead of flattened cells.

Pre-flight checks all passed before anything was written: both photos present by slug, Review Wall active with 20 reviews, target pages 2133 / 2440 present, and all four Divi sections still in `post_content` to be replaced.

- One consolidated seed rather than five — every attachment resolved **by slug**, the FAQ answers **extracted from `post_content`** rather than retyped (they carry rates and tax bands), and the script aborts unless it parses exactly 7 FAQ items or finds both photos.
- `ak_append_sections` used for the first time on production, for `seo` — the section with no Divi original.
- Polylang translations for the five new UI strings written in the same pass, so pt shipped with «Todos os testemunhos» and «Todas as perguntas e respostas» rather than Ukrainian placeholders.
- **Post-deploy meta diff: zero unexpected differences.** The only deltas are the six `ak_about_*_body` values (local's `<p>` stripped by TinyMCE — identical rendering via `wpautop`), `ak_about_portrait` (2570 vs 2567 — same file, different IDs per environment), and `ak_inline_sections` (local carries `calculator = calculator`, production deliberately does not). Every new section matches exactly.

**Verified on production, both languages:** nine native sections; `.et_pb_section` down to 1 (`calculator`); trust marker `rgb(48,113,85)`; 5 trust items, 5 order items, 9 testimonial cards, 7 FAQ items, 4 SEO blocks; SEO heading 506 wide; one `h1`; no overflow at 1440 or 375; **zero console errors**.

Backups: `~/backup-theme-20260804.tgz` + `~/backup-akmeta-20260804.tsv` (the two earlier pairs are still there). Rollback stays cheap — `post_content` has never been modified, so clearing `ak_inline_sections` and `ak_append_sections` returns every Divi original.
