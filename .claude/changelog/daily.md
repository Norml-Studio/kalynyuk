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

### Header submenu spacing — the gap was never the problem

Reported as "the gap between items should be 16px". It already was — `.site-nav__sublist` had `gap: $space-2` all along. What was wrong is that each item was **40px tall instead of the design's 20**, so the list measured 208 against Figma's 128 (`1163:1809`: four 20px rows, `itemSpacing: 16`). Bigger rows read as bigger spacing.

The 40 came from `min-height: $tap-min` plus 8px block padding, added for header-standard §5's ≥40px target — and `$tap-min`'s own note says in as many words **not** to shrink a target to match the art. So both requirements were real and in conflict.

**Resolved by separating flow height from hit area.** The row keeps the design's 20px in flow; an absolutely-positioned `::after` grows the clickable box ±8 into the gaps, which are dead space anyway. That gives **36×321 per item** at zero layout cost — comfortably over WCAG 2.2 AA's 24px floor, on a pointer-only menu (mobile uses the drawer, never this panel).

⚠️ **±8 is the maximum that does not overlap:** the gap is 16, so adjacent targets meet exactly and tile the list with no ambiguous region. Do not raise it chasing 40.

Verified by probing `elementFromPoint`: on-text hits its own row, 4px into the gap still hits the row above, and the boundary resolves cleanly to the row below — no dead pixels, no overlap. List now 130 against the design's 128 (the 0.48px line-height rounding).

### FAQ translated into Portuguese

Page 2483 was showing the Ukrainian questions and answers under a Portuguese heading — the seed had deliberately let them inherit, and nobody had translated them since.

⚠️ **Translation only.** Every figure was carried across unchanged and then spot-checked programmatically: 10–20% / 30–40%, TAEG 3,5–4,5%, fixa 3,5–4%, 18–35 anos, 15%, 450 000 €, 83 696 €, 7–10%, IMT 0–8%, 316 272 €, Imposto do Selo 0,8% / 0,6%, 1500–2500 €, 10–15%, 3–6 meses, 1 ano. Nothing rounded, restated or "improved" — this is regulated copy for a licensed credit intermediary and the Ukrainian original is the reviewed source. Portuguese terminology uses the local names (IMT — Imposto Municipal sobre as Transmissões, contrato sem termo, declarações de IRS).

### Footer translation — the whole footer, and the answer to "how does Gravity Forms work with Polylang?"

**It doesn't.** There is no Polylang integration for Gravity Forms — *Gravity Forms Multilingual* is a WPML product, and only `polylang/polylang.php` is active here. What was being conflated:

- **GF's own interface strings** — the character counter, "This field is required" — are ordinary WordPress i18n. Polylang switches the locale, GF loads its matching `.mo`, and they translate for free. That is why "0 de 600 máximo de caracteres" was already Portuguese while the label beside it was Ukrainian.
- **Form CONTENT** — labels, placeholders, error messages, choice text, button, confirmation — is editor-entered and lives in **GF's own tables**. Polylang's string scanner never sees it. Nothing translated it, so the footer form stayed Ukrainian in every language.

**[DECISION] One form, filtered — not one form per language.** The usual workaround is duplicating the form and picking it at render time. Rejected: four forms means four entry streams, four notification configs, and a field added to one and forgotten in the other three — for a four-field contact form. The cost lands forever on whoever maintains it. Instead `ak_gform_translate()` filters the form OBJECT, so there stays one form, one entry stream, one set of notifications, and every string becomes editable in Polylang → Translations → Strings like all the others (multilingual rule 3).

⚠️ **All three hooks are required**, and this is not belt-and-braces. `gform_pre_render` translates what the visitor sees; `gform_pre_validation` keeps the re-rendered form translated after a failed submit; `gform_pre_submission_filter` carries it into submission — and is also how the **confirmation message** gets translated, because `GFFormDisplay::process_form()` picks the confirmation out of the form object that filter returns (`form_display.php`, `handle_submission()` at ~line 210). Deliberately **not** the obvious `gform_confirmation` filter: by the time it fires the message is merged with the entry and wrapped in GF markup, so the string handed to `pll__()` would be per-submission HTML matching no registered source.

Registration is **admin-only** — `pll_register_string()` only populates the Strings screen; `pll__()` translates by source string and needs no registration. Doing it on the front end would mean a `GFAPI::get_forms()` query on every page view to build a screen no visitor opens. Notifications are deliberately left untranslated: they go to Anna, not the visitor.

#### Four more footer strings were broken in ways a translation layer cannot fix

- ⚠️ **A source string in the wrong language cannot be translated.** `ak_copyright` was registered with the **Portuguese** default `'Todos os direitos reservados.'`, so the Ukrainian site rendered "© 2026 Todos os direitos reservados." and Portuguese only looked right *by accident* — the source doubled as the pt translation. Polylang always returns the source for the default language, so no amount of translating could have fixed it. The email field's custom `errorMessage` had the same shape: typed in English, so Ukrainian visitors got an English validation error. **Both fixed at the source**, with pt/en/ru re-seeded off the new Ukrainian keys.
- **Social labels came from an ACF options repeater** — global, one value for all languages, which is exactly what multilingual rule 3 warns against. «Телеграм»/«Інстаграм» were *already translated* in Polylang and still rendered Ukrainian on `/pt/`, because `ak_socials()` returned `(string) $row['label']` directly; the `ak_str()` branch in that function was a legacy fallback that never ran. Now routed through `ak_str()`, with the repeater rows registered (admin-only) so a *fifth* social an editor adds later shows up in the Strings screen instead of failing silently the same way.
- ⚠️ **Polylang returns 0, not the original, for an untranslated privacy page.** It filters `option_wp_page_for_privacy_policy` at priority 20 and hands back the translation — or `0` when there is none. Every caller reads 0 as "no privacy policy configured", so on `/pt/`, `/en/` and `/ru/` the footer link **vanished entirely**, and so did core's `get_privacy_policy_url()` (login screen, `the_privacy_policy_link()`, personal-data export mail). A missing translation must degrade to the Ukrainian page, not to nothing — for a credit intermediary the policy has to be reachable from every page in every language. Fixed at the **option** level, not in `footer.php`, because the footer was only where it was noticed. Capture at priority 19 / restore at 21, so Polylang's own filter is left completely alone rather than torn out or recursed through.
- **Four `__()` strings reached assistive tech in English.** The theme calls `load_child_theme_textdomain()` but ships no `languages/` folder, so *every* `__()` renders as its English source. Harmless for admin labels; not for the skip link (visible on keyboard focus), the footer nav label, the drawer label and the language-switcher label. Moved to `ak_str()`. The remaining `__()` calls are admin-only, plus «Livro de Reclamações» / «Intermediário de crédito n.º» — Portuguese legal names that stay Portuguese in every language by design.
- **The language switcher announced "uk, pt" instead of "Українська, Português".** `ak_language_switcher()` asked Polylang for `display_names_as => 'slug'`, and since the switcher is flags-only the name exists *solely* in the two visually-hidden spans — the one place a language must be named in words. Switched to `'name'`; the separate `slug` key still drives `hreflang`, `lang` and the flag lookup, so nothing machine-readable lost anything.

#### Verified

- Full footer sweep on `/pt/` and `/en/`: **zero Cyrillic left**, in text nodes and in `aria-label` / `alt` / `placeholder`.
- Form submitted end-to-end in the browser on `/` and `/pt/`. Empty submit → errors in the page language, and the re-rendered form keeps its translated labels (this is what `gform_pre_validation` buys). Valid submit → confirmation in the page language, cream `rgb(247,242,233)` preserved through the translated markup.
- The 9 QA entries this created were deleted by matching the `ak-qa*@norml.studio` addresses; the 99 real entries were not touched.

#### Two dead ends worth recording

- ⚠️ **A "valid" test email was rejected, and it was not our bug.** `ak-test@example.com` fails `GF_Field_Email::is_email_rejected()` — GF blocklists the reserved domain. Confirmed pre-existing by re-running the same submission with all three filters removed. Cost real time; **use a real domain when smoke-testing a GF email field.**
- `/en/` and `/ru/` return **404** — their front pages (2484 / 2485) exist but are still drafts, so only `uk` and `pt` are live. Strings still resolve on those URLs because Polylang sets `curlang` from the path, which made the 404s look like working pages in an early check.

#### Known, not fixed

- **`remodal.min.js` from the Review Wall plugin throws `b.map is not a function` on every page load.** Third-party, pre-existing, unrelated to the footer. Not dequeued: the plugin's shortcode is still used on the Відгуки page (2133), so its assets are still needed there.
- **GF ships no Ukrainian translation**, so its own strings ("This field is required.") stay English on `/`. Portuguese is complete. Fixing it means supplying a `gravityforms-uk.mo`.
- The **privacy policy page itself has no translations** — every language links to the Ukrainian one. Content gap, not a code gap: the link works.
- **Consent entries record the Ukrainian text in every language.** Only `choices[].text` is translated, never `value`, so entries stay comparable across languages — but the stored record then does not show what a Portuguese visitor actually read. For a regulated intermediary that may matter; flagged for Petr rather than decided here.
- Forms **2 and 3** (calculator) are now registered in Polylang but deliberately left untranslated — form 2's confirmation is in English on a Ukrainian site. That belongs to the calculator session.

⚠️ **This work is not deployable by file sync alone.** Production needs the same DB seeding: the `PLL_MO` strings, and the GF form-1 `errorMessage` source rewritten to Ukrainian. Deploying files without it leaves production with an English error message whose source no translation matches.

### 🚀 Production deploy #4 — footer translation

Seven PHP files (`footer`, `header`, `acf-options`, `integrations`, `nav`, `lang-switch`, `nav-drawer`) plus a DB seed. **No `dist/` change** — nothing touched SCSS or JS.

⚠️ **File sync alone would have been wrong here**, which is what makes this deploy different from #1–#3. Two of the fixes rewrite *source* strings (`ak_copyright`, the email `errorMessage`), and Polylang matches on the source — so shipping the code without re-seeding would have left production with strings whose translations no longer resolve.

**Every source string was read out of production's own database rather than reconstructed.** The consent sentence embeds an absolute privacy URL that differs per environment (`kalynyuk.loc` vs `www.kalynyuk.com`), and the confirmation carries a cream `<span style="…">` wrapper. The seed pulls production's `<a …>` tag and its span verbatim and only swaps the words between them, so a scheme or trailing-slash difference cannot silently seed a translation that nothing ever looks up. It aborts if any source reads empty or if the consent text has no anchor. **This is the discipline the deploy-#2 attachment-ID bug bought** — resolve from the target environment, never from local.

Backups before anything ran: `~/backup-theme-20260804b.tgz` and `~/backup-strings-20260804.json` (the full `PLL_MO` table for all four languages plus the complete form-1 object).

⚠️ **The strings backup was written into the docroot first and was briefly reachable over HTTP.** Moved to `~/` immediately; the webroot was then confirmed clean. `ABSPATH` is a webroot — dump files belong in the home directory. Worth remembering before the next seed script.

**Verified live on `https://www.kalynyuk.com`, both languages:**
- `/pt/` footer has **zero Cyrillic left** — text nodes and `aria-label` / `alt` / `placeholder` alike.
- The privacy link **renders again** on `/pt/` ("Privacidade"); it had been absent entirely.
- Consent `href` survives translation on both languages: `https://www.kalynyuk.com/privacy-policy/`.
- Copyright now reads «© 2026 Усі права захищено.» on uk and "Todos os direitos reservados." on pt — previously Portuguese on both.
- Validation path exercised on both: errors come back in the page language and the re-rendered form keeps its translated labels and button. **No entries created** (103 before and after) and no notification sent — the happy path was deliberately not run on production.
- No console errors beyond the known Review Wall `remodal.min.js` one.

Rollback is the tarball plus `backup-strings-20260804.json`, which restores both the `PLL_MO` entries and the form object.
