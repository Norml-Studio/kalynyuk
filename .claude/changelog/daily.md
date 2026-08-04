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
