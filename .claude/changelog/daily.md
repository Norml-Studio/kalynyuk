# Daily Changelog — Anna Kalynyuk

<!-- Entries go under a `## YYYY-MM-DD` header for today. When a new day
starts, this file is compressed into weekly.md. See README.md for the protocol. -->


## 2026-08-04

- Rolled the changelog per `README.md`: week **2026-W31** compressed into `changelog.md`, week **2026-W32** into `weekly.md`, and this file reset. Nothing was dropped that the compression rules protect — every `[DECISION]` and `[INTEGRATION]` from both weeks survives in the tier above.
- ⚠️ **Diagnosed the stray consent checkbox properly before parking it** (Petr: leave it for the calculator session). It is **not** plugin junk and **not** in `post_content`: the rule `.contact-form .ginput_container_checkbox { position: absolute; bottom: 40px !important; max-width: 460px !important; }` ships inside `<style id="et-builder-module-design-deferred-11-cached-inline-styles">` — Divi's generated CSS for page 11, from a module that no longer exists in the content. Its `offsetParent` is `div.et_builder_inner_content`, so with no positioned ancestor the `bottom: 40px` anchors to the whole content area and lands the checkbox over the FAQ.
  **The general lesson is bigger than this bug: removing a Divi section at render time does NOT remove the CSS Divi generated for it.** Divi builds its dynamic stylesheet from `post_content` independently of our `the_content` filter, so every migrated section may have left orphan rules behind. Worth a sweep during phase 4.
