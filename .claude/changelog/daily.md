# Daily Changelog — Anna Kalynyuk

<!-- Entries go under a `## YYYY-MM-DD` header for today. When a new day
starts, this file is compressed into weekly.md. See README.md for the protocol. -->

## 2026-07-27

- Ran `dev-wp-init-project` Mode A (scaffold + full scrape) on the child theme. Created `.claude/` with `CLAUDE.md`, `ci-cd.md`, `design.md`, `wp-config.json`, the three-tier changelog, `skills/`, and `docs/01`–`docs/05` + `docs/README.md`.
- Scraped Stages 1–5 locally via WP-CLI (WordPress 7.0.2 · PHP 8.1.1 · MySQL 5.7.33 under OpenServer). Scraped Stage 6 (`design.md`) against production `https://www.kalynyuk.com/` with Playwright at 1440×1000 — homepage only.
- [DECISION] `ci-cd.md` declares `pattern: local-bitbucket-prod` (confirmed by Petr). Declared as a **target, not a description** — deploys are blocked because there is no git repo and no `credentials/projects/anna-kalynyuk.json`. A prerequisites checklist is in `ci-cd.md`.
- [DECISION] Revoked the pattern's default "autonomous REST content edits on production." On this project content and design are the same object (a page edit rewrites a ~200 KB Divi shortcode blob), there is no git history to revert to, and no staging to preview on. Rationale recorded in `ci-cd.md` → Override notes.
- [DECISION] `design.md` v1.0.0 records the measured system as binding: canvas `#F7F2E9`, ink `#2A2011`, one accent `#307155`, panel `#FEF8EF`; Nunito Sans only; radius 8/16/24/50px/50%; one shadow. Canvas audit passed — 13 sections, 2 distinct backgrounds.
- [INTEGRATION] Confirmed the CSS layer is the CodeKit plugin, not the theme: `custom-code` post 1907 → `wp-content/custom_codes/1907-scss-output.css`, **outside** the theme folder. A theme-only deploy ships no CSS. The child `style.css` is empty.
- Recorded the three duplication traps that will cause drift: the site header exists in triplicate (Divi layouts 284 / 2213 / 2214, all titled `All Category Pages`); ~95 KB of calculator CSS is byte-identical across pages 11 and 566; the homepage exists twice (uk 11 + pt 2483, ~200 KB each).
- Recorded that `[vertical_menu]` matches the menu by display name `"Меню"` — renaming that menu silently breaks navigation in all three header layouts.
- Found and documented in `docs/05-issues.md`: 79% of the 194 MB database is post revisions (516 on the homepage alone, `WP_POST_REVISIONS` uncapped); pages 2484 (`en`) and 2485 (`ru`) are published at 0 bytes; web-reachable stale PHP in `wp-content/` (`*-old`, `*-backup.php`); orphaned data from six uninstalled plugins (Yoast, Formidable, Elementor + add-ons, Depicter, MetaSlider, LiteSpeed) including third-party API keys; `.htaccess` has no expires/gzip rules; Nunito Sans loaded twice, once via CSS `@import`.
- Noted for the record: `norml-personal-setup` is at v1.15.0, this machine last ran v1.7.2 (2026-05-22).

### De-Divi migration — phase 0 (foundations, zero visual change)

- Brought the theme under version control. Repo root is the theme folder; remote `git@github.com:petyasavenok-dev/kalynyuk.git` added, three commits local, **nothing pushed yet**.
- [DECISION] Introduced **Vite** as the theme build step, approved by Petr. Reason: `dev-bem-scss` requires `src/scss/` partials with `@use`/`@forward` and a one-section-per-file rule; CodeKit compiles a single SCSS blob from the database and cannot do partials, so the methodology and the existing toolchain are incompatible. Deviates from the Norml Sage/Vite standard in one way — no manifest, no hashed filenames, `dist/` committed — because there is no CI and production has no build step.
- [DECISION] **Mobile-first**, resolving a conflict between two hard rules. `dev-bem-scss` mandates mobile-first; `dev-wp-developer` summarises `vibe-frontend-standards` as desktop-first `max-width` queries. The source shows that requirement lives under *Print / PDF* and is justified by Playwright rendering **mockups** to PDF at 1440 — the rationale does not transfer to a production theme. The three structural boundaries (≥1025 / 641–1024 / ≤640) are preserved exactly; only the direction is inverted. Reasoning recorded in `_tokens.scss` so it is not re-litigated.
- [DECISION] Text domain shortened `anna-kalynyuk---norml-studio-theme` → `kalynyuk`, and `load_child_theme_textdomain()` now actually runs. It never did before, so the theme was unlocalisable on a four-language Polylang site. Done now because there were zero `__()` calls to migrate.
- Generated `src/scss/abstracts/_tokens.scss` from `.claude/design.md` — no invented values. Added the `bp()` / `container()` / type-role mixins, a minimal reset, the canvas layer, and `.ak-prose` typography scoped so it cannot leak into Divi-rendered content mid-migration.
- Split `functions.php` into `inc/{setup,helpers,assets,shortcodes,integrations}.php`. Renamed two unprefixed globals to `ak_*` (shortcode tag unchanged, so Divi header layouts keep working), added `ak_is_external_url()` per `dev-wp-developer`, and `ak_languages()` / `ak_current_language()` which read Polylang and never hardcode a language list.
- Fixed the enqueue chain and **corrected a wrong entry in `docs/05-issues.md`**: the duplicated stylesheet was the *child's*, not the parent's. Divi picks its branch by testing whether the child already enqueued a parent `style*.css`, and the child `functions.php` loads first, so the old code won that race. Enqueuing neither stylesheet lets Divi load both itself, once. Our CSS now goes on at priority 20 with a real dependency on `divi-style`.
- Verified by rendering: HTTP 200, 12 Divi sections, no fatals, `body` background `rgb(247,242,233)` matching the contract, no horizontal scroll, one redundant stylesheet request removed. The pre-existing `b.map is not a function` JS error was confirmed pre-existing by stashing the changes and re-rendering.

**Two new Critical findings on PRODUCTION**, added to `docs/05-issues.md`:

- The visible primary nav is a **Divi `et_pb_menu` module with no menu assigned**, so Divi falls back to `wp_page_menu()` — 12 items over three lines, including two orphaned pages, the privacy policy, and a category archive. The curated 8-item menu is not what visitors see. `[vertical_menu]` renders the correct menu but its output has no CSS anywhere, so it sits on the page unstyled while the wrong nav displays.
- **No language switcher renders at all.** The 195 KB Portuguese homepage is unreachable from the UI. Phase 1 fixes both.

### De-Divi migration — phase 1 (custom header + footer)

- Built `header.php`, `footer.php`, `template-parts/{nav-drawer,lang-switch}.php`, `inc/{nav,acf-options}.php`, and SCSS for header / drawer / footer / button / lang-switch. Dependency-free JS (`src/js/header.js`).
- Both templates **keep Divi's wrapper contract** — `#page-container` and `#et-main-area` open in the header and close in the footer, plus `et_before_main_content` / `et_after_main_content`. Divi's CSS and Theme Builder body layouts target those IDs, so dropping them mid-migration would break every page still built in Divi. They come out in phase 4.
- [DECISION] Chrome text vs config split. Translatable text → `pll_register_string()` (editable per language in Polylang → Strings, works on free Polylang); non-translatable config (phone, email, socials, logo) → one global ACF options page; the CTA's **target page** is a single ACF field resolved per language by `ak_translate_id()`. This avoids the per-language ACF options hack (`acf/validate_post_id`), which rewrites every options read in admin and front end — too much blast radius for a phone number.
- [DECISION] `Portugal` renders as `<button>`, not a link (Petr). May become an archive later; a non-navigating trigger is also the more accessible default per `header-standard` §1.

**Three real bugs found by rendering, not by reading:**

- **`_et_*_layout_enabled = 0` is the WRONG switch and made things worse.** Divi's predicate is `'override' => 0 !== $header_id || false === $header_enabled` (`theme-builder.php:481`) — `override` is true when a layout is assigned **OR when it is DISABLED**, because "disabled" means "this template deliberately renders no header". Correct detach is `_et_{area}_layout_id = 0` **AND** `_et_{area}_layout_enabled = '1'`. Original layout IDs backed up to `_ak_orig_*` meta; layouts 284/2213/2214/365 untouched in the DB.
- **`pll_the_languages(['raw' => 1])` returns `flag` as a URL string, not an `<img>` tag.** Echoing it printed a bare URL into the header. Now built with `printf` + `esc_url`.
- **`et_divi.divi_logo` is the wrong asset.** It points at `Anna-Kalynyuk.svg` — 128×19, a **single path**, the wordmark only, with no "AK" monogram and no "INTERMEDIÁRIO DE CRÉDITO". The lockup the old header actually rendered is `uploads/2026/05/logotype.svg` (216×32, 38 paths), referenced inside Divi header layout 284 rather than through Theme Options. ACF logo field now set to it (ID 2421), and the fallback no longer trusts `divi_logo`.

**Two defects fixed that pre-dated this work:**

- [DECISION] Removed Divi's hardcoded viewport meta (`Divi/functions.php:536`): `maximum-scale=1.0, user-scalable=0` disables pinch-zoom — a **WCAG 2.1 SC 1.4.4 failure** — is not configurable, and being later in `wp_head` it overrode ours. Unhooked in `inc/setup.php`.
- Stripped dead old-header JS from Divi Theme Options → Integration → head: a `#mobileMenuToggle` / `#customMobileMenu` handler that threw on every page once the old header was gone, plus a `.header-wrapp` hide-on-scroll block. The `.go-top` handler was **kept** — it may still be used inside Divi page content. Original 2308 bytes backed up to the `ak_backup_divi_integration_head` option.
- **Corrected the root cause in `docs/05-issues.md`.** The broken production nav was NOT "a Divi menu module with no menu assigned". `polylang/src/frontend/frontend-nav-menu.php:244` overwrites every menu location with `0` unless it has a per-language assignment, and `polylang.nav_menus` was empty — so `wp_nav_menu()` found nothing and fell back to `wp_page_menu()`. Fixed in data (uk → menu 2) **and** in code (`ak_nav_menu_location_fallback()`, so any language without its own assignment inherits the default language's menu).

**Verification:** 34/34 Playwright assertions pass — dropdown open/close on click, `aria-expanded`, chevron rotation, Escape + focus return, outside-click, one-open-at-a-time across nav and language switcher, ≥40px hit areas, drawer open/close, scroll lock and release, drill-down, Back, reset-to-root, and zero horizontal overflow at 375/768/1024/1440/1900. Remaining console error is `b.map` from `review-wall/assets/js/libs/remodal.min.js` — pre-existing, confirmed on the untouched baseline.

**Not done:** the menu still holds the old 8-item flat IA, not the design's 6 items with the `Portugal` dropdown, and the four `guide` posts do not exist yet. Footer paddings are from the design.md scale, not measured — the Figma bridge dropped before frame `1163:303` could be read.

## 2026-07-28

### Production SSH access — resolved

- Generated an ed25519 key for this project, `~/.ssh/anna-kalynyuk-admtools` (no passphrase, matching the other Norml deploy keys), fingerprint `SHA256:rAyJCKItH4+TkYWRcrlBXlPlbv8Xdef9VGI8tUjxo84`. Added `~/.ssh/config` alias `anna-kalynyuk` → `nu538012@nu538012.ftp.tools`. The client added the public key in the adm.tools panel; **key-based SSH verified working** (uid=2368, home `/home/nu538012`).
- Created the two credentials files that `.claude/CLAUDE.md` flagged as missing: `credentials/projects/anna-kalynyuk.json` and `credentials/servers/admtools-anna-kalynyuk.json`. Production docroot confirmed `/home/nu538012/kalynyuk.com/www`; `siteurl` = `home` = `https://www.kalynyuk.com`; active theme + Divi 4.27.7 match local exactly. **This clears one of the two `ci-cd.md` prerequisites** — the missing git repo is still open, so deploys stay blocked.
- No password stored anywhere. The panel only exposes "Change password", SSH is key-only, and there is still no WP REST application password.

**Two host constraints worth not rediscovering:**

- **Bare `wp` on production fatals.** WP-CLI is installed at `/usr/local/bin/wp`, but its `#!/usr/bin/env php` shebang resolves to the host default PHP **5.6.40** and dies on a Composer platform check (`requires >= 7.2.24`). Correct invocation is `php8.2 /usr/local/bin/wp` from inside the docroot; `/usr/bin/php5.2` … `php8.5` all exist (CloudLinux alt-php). `~/.cl.selector/defaults.cfg` is `php=native` and was **deliberately left alone** — it is account-wide. `/opt/alt/php-internal/usr/bin/php` is Permission-denied under CageFS.
- **The SSH account is not single-tenant.** `~` holds three sites — `hresko.com.ua`, `kalynyuk.com`, `s1904672.bolila.pt`. Anything recursive from the home directory reaches two unrelated projects. Scope to `~/kalynyuk.com/www`.

- Recorded both constraints in the credentials files and in `.claude/CLAUDE.md` (Production access + WP-CLI sections rewritten; the old "hard stop, credentials do not exist" warning replaced).
- ⚠️ Standing risk: the panel describes this access as *temporary delegated access* from the owner (granted to `vova.hresko@gmail.com`, ID 492271). It is not a Norml-owned hosting account and can lapse without notice.

### De-Divi migration — CodeKit retired, conventions locked

- Petr moved the CodeKit `custom-code` CSS into `_base.scss` and deactivated the plugin. Verified: `1907-scss-output.css` no longer loads and no `fonts.googleapis.com` reference is left in the HTML — which incidentally closes the "Nunito Sans loaded twice" issue in `docs/05-issues.md`.
- The migrated `@import url(…)` for Nunito Sans **was valid** (Vite hoists it to position 0 of the bundle, so the browser honoured it), but it serialises two round trips — the font CSS cannot start until `main.css` is fetched and parsed. Moved to `wp_enqueue_style` + two `preconnect` hints, and dropped weight **300**, which the UI Kit does not use.
- The page-specific Review Wall override moved out of `base/` into `sections/_reviews.scss`; per `dev-bem-scss`, page-scoped third-party CSS is not a base-layer concern.
- **Fixed the 80px gap above the header at its source.** Divi's dynamic CSS ships `.et_fixed_nav.et_show_nav #page-container { padding-top: 80px }` to reserve room for its own `position: fixed` header; ours is `position: sticky`, so the reserve became a visible empty band. Rather than out-specifying two classes + an id on every Divi cache regeneration, `ak_remove_divi_fixed_nav_classes()` strips both body classes and the selector stops matching. Divi's JS-written inline `margin-top: -1px` is zeroed in the new compat layer.
- [DECISION] New file `src/scss/base/_divi-compat.scss` is the **only** place Divi selectors may live. Phase 4 becomes "delete one partial and one `@use` line".
- Header CTA now supports an external URL — added `ak_cta_url`, which takes precedence over the page field, set to `https://tally.so/r/w2oW8A`. `ak_link_target_attrs()` decides target/rel by host, so it got `target="_blank" rel="noopener noreferrer"` with nothing hardcoded.
- [DECISION] **Container rule locked** (Petr): canvas 1440 · container **1376** · padding **30 desktop / 20 mobile**, band full-bleed and `&__inner` capped by `container()`. Applies to every section. The header was hand-rolling `padding-inline` without the cap; now fixed. `design.md` §6 records the rule and, in a collapsed block, the three superseded measurements (1600 from a Divi attribute, 32px from the section grid, 40px from the footer frame) so nobody re-derives them.
- [DECISION] **SCSS authoring style locked** (Petr): block opens once, elements nested as `&__element`, mandatory `// .full-class-name` mirror comment above each rule — the comment is what keeps `&__panel` greppable. Recorded in `.claude/CLAUDE.md`. All five partials converted; **0** flat `.block__el` selectors remain. Merged a duplicate `&__form` block in the footer that the conversion exposed.
- `.claude/CLAUDE.md` also documents the build commands, since `dist/` is committed and stale CSS is otherwise easy to ship.
- Verified: 34/34 Playwright assertions; `#page-container` padding 0; header and footer inner both 1376 with 30px padding; footer halves 658/658; submit cream at radius 24; Review Wall transparent on page 2133 only. Only remaining console error is the pre-existing `b.map` from `review-wall/assets/js/libs/remodal.min.js`.

### Language switcher + chrome interaction

- **Fixed a bug I introduced: the language switcher vanished on most pages.** `hide_if_no_translation => 1` hides a language when the *current* page has no translation in it; `pt` has only 2 translated objects, so the list fell to one language and the template's `count < 2` guard removed the switcher entirely. Measured before the fix: present on `/` and `/faq/`, **absent** on `/pro-mene/`, `/blog/` and every guide page. A switcher is persistent chrome. Now `0` — Polylang links an untranslated language to that language's home instead.
- **Corrected my own explanation of why `en` / `ru` don't appear.** It was never `hide_if_no_translation`. Polylang's `hide_if_empty` (default 1) drops a language with **no published content at all**, and both have zero — I set their only pages to draft myself. Verified: with `hide_if_no_translation => 0` the list is still just uk + pt.
- [DECISION] `hide_if_empty` stays at its default. Forcing all four visible makes `en` and `ru` link to `/en/golovna-english/` and `/ru/golovna-russkij/`, both of which return **404** (drafts). Two working flags beat four where half are broken. Publish real content in a language and it appears on its own — no code change, no hardcoded list. The design's third flag (GB) arrives when the English homepage is published.
- Active language now wears a **ring** around the flag (`box-shadow: 0 0 0 3px --surface-sunken`) rather than a filled pill — on the trigger and on the current item in the list.
- New `components/_page-scrim.scss`: opening a desktop dropdown dims the page at `opacity: .28`. Sits at `$z-scrim` (150) — **below** the header — so the band and the open panel stay bright and interactive; `pointer-events: none` so it never competes with the existing Escape / outside-click / trigger close paths. Driven from panel state in JS rather than toggled per call site, so it cannot drift out of sync.
- **Mobile: dim removed and the header is now clickable while the drawer is open.** The drawer's scrim spanned `inset: 0` at a higher z-index than the header, so it darkened the header band and swallowed clicks on the logo and the language switcher. The header now outranks the drawer (`$z-header-above-drawer`) and the drawer scrim is transparent — it is full-viewport-width, so a scrim behind it was never visible anyway. Hit-tested with `elementFromPoint`, not just by z-index arithmetic.
- ⚠️ `assets/flags/ru.svg` does not exist — the design has only UA / PT / GB components. If Russian is ever published, `ak_flag_svg()` falls back to Polylang's 16×11 PNG, which will look soft at 32px. Needs an asset from the designer.
- Verified: 34/34 header assertions plus 16/16 new ones covering the switcher on five URLs, the ring, scrim visibility / stacking / pointer-events, and real hit-testing of the logo and switcher with the drawer open.
- Flags switched from inlined SVG markup to `<img src="…/assets/flags/{slug}.svg">` (Petr's preference, and the better trade-off): the file caches once and is reused across every page, and the HTML stops carrying ~2 KB of flag markup per request. Costs one small cacheable request per flag. `ak_flag_svg()` renamed to `ak_flag_html()` since it no longer returns SVG. Verified: 0 inline flag SVGs left, 3 `<img>` elements resolving to `flags/uk.svg` / `flags/pt.svg`, all loaded at 24×24 intrinsic and rendered at 32px, no failed requests, ring intact.

### Footer review pass 2

All six points measured before and after, not eyeballed.

- **Form title** was rendering `rgb(42,32,17)` — ink, not cream. `.site-footer` sets `color: --canvas` and the title should inherit it, but Divi's own `h2` rule wins. Colour now explicit; size 32px → **24px** per Petr.
- **"Вгору" button** was a transparent circle with a cream outline; the design is a **filled** cream circle with a dark chevron. That is a different control visually, not a shade difference.
- **Horizontal hairline was 1440px** — full viewport — because `border-top` sat on `.site-footer__bottom`, which is outside the container. Moved to `__bottom-inner`: now **1376**, matching the container.
- **Vertical mid-rule stopped 32px short** of that hairline: `__bottom` had `margin-top: $space-4`. Removed — measured gap is now **0px**.
- **Item pitch was 64px**, design is ~44px (nav column 163×258 for 6 rows, contacts 369×352 for 8). Petr was right that `min-height: $tap-min` (40) was the cause. Set to **24px** — the WCAG 2.1 AA target-size floor (SC 2.5.8). Pitch is now 48: 4px wider than the design but compliant. Dropping to 0 would match exactly and take the target down to the ~20px line box, below that floor. The stricter 40px from `header-standard` §5 governs primary nav and controls, not a footer link list.
- **Form labels hidden visually**, placeholders kept. The `<label>` stays in the DOM — a placeholder is not an accessible label, it disappears on input. Verified in the rendered page that the consent text the user must actually read, its privacy-policy link and the checkbox all remain visible; only the redundant "Згода" title goes. An earlier revision guarded this with `:not(.gfield--type-consent)`; that class does not exist in GF 2.10, so the selector was dead weight built on an assumption and was removed.
- [DECISION] **Footer credit is now "Made by" + the Norml signature lockup** (Figma 1163:349, exported to `assets/norml-credit.svg`, 190×43, cream), on Petr's explicit instruction. This **deviates from `dev-wp-developer`'s "Site chrome" rule**, which prescribes `by {Norml Studio}` with the name as the link so the credit is identical across every Norml client site. Recorded in the template rather than silently resolved; reverting is a single block. It is an image of text, so `alt="Made by Norml Studio"` carries the credit and the whole lockup is one link.

Verified: 11/12 targeted assertions plus 34/34 header regressions. The one "failure" was my own test asserting the "Згода" label should stay visible — the code was right, the assertion was wrong, and the consent copy is confirmed visible.

### Chrome options: socials repeater + language-switcher toggle

- **The contact data was already centralised** — the drawer and the footer have read the same ACF options (`ak_phone`, `ak_email`, socials) since phase 1. They are two placements of one dataset, not two copies. What WAS duplicated is now removed at the source: socials moved from two hardcoded fields (`ak_telegram`, `ak_instagram`) to an **`ak_socials` repeater** (label + URL), so adding Facebook or YouTube is an admin action rather than a code change in three files. `ak_socials()` is the single accessor both templates call.
- One-time migration (`ak_migrate_socials`, guarded by an option) carries the two existing values into the repeater and bails if a human has already added rows. The legacy fields are still read as a fallback, so an install that has not been re-saved keeps rendering. Verified: both values migrated, drawer and footer render identically from the repeater.
- New **`ak_show_lang` toggle** in Site chrome — hides the switcher in the header and the drawer without deactivating Polylang, for the period while a language is still being translated. Guarded at both the include site and inside the template part, since `get_template_part()` is reachable from anywhere. Defaults to ON when never saved, so existing installs do not silently lose the switcher. Verified in raw HTML: OFF → 0 occurrences of `data-ak-lang`, ON → 3.
- 34/34 header regressions still pass.

### 🚀 PRODUCTION CUTOVER — phase 1 chrome is live

First deploy to `https://www.kalynyuk.com`. Single-window cutover (Petr's call): files + DB together, because a files-only push would have **broken the live header** — see below.

**Why files-only was not an option.** Probing production first showed Divi TB `override = TRUE` on all four templates, so our `header.php` / `footer.php` would not have rendered at all. But the rest of the theme *would* have applied — `ak_remove_divi_fixed_nav_classes()` strips `et_fixed_nav` / `et_show_nav`, and `_divi-compat.scss` zeroes `#page-container` padding. Those are exactly the compensation Divi's still-active fixed header depends on. We would have removed the prop without installing the replacement, and content would have slid under the live site's header.

**Order executed:**
1. Backup → `~/kalynyuk.com/_backup-20260729-101013/` (db.sql.gz 57 MB + theme.tar.gz), **outside the docroot**. Path also written to `~/kalynyuk.com/.last-backup-path`.
2. Files via tar+ssh (no rsync on the Windows side): 37 files, 91 KB. Prod theme held only the original 3 files, all overwritten — no stale files left behind.
3. DB cutover in one idempotent script: TB detach (id=0 + enabled=1, originals backed up to `_ak_orig_*` meta) → `polylang.nav_menus` uk→menu 2 → ACF chrome options → 4 `guide` posts + menu rebuilt to the 6-item design IA → CodeKit deactivated → empty `en`/`ru` stubs to draft → rewrites + WP Rocket + Divi caches flushed.
4. Cleaned the dead `#mobileMenuToggle` script from Divi → Integration → head (2308 → 358 bytes, `.go-top` kept, original in the `ak_backup_divi_integration_head` option).

**⚠️ `.claude/` was deliberately excluded from the deploy.** `docs/05-issues.md` enumerates this site's security issues; shipping it into a web-accessible directory would publish them. Verified absent on the server after extraction.

**Verified on production:** 14/14 assertions — HTTP 200, no fatals, our header and footer render, Divi's `#main-header` gone, nav is the 6-item design IA, Portugal dropdown has its 4 links, switcher shows 2 languages, CTA points at Tally, no 80px gap, container 1376, Made-by lockup present, regulatory block intact, no horizontal scroll. Five URLs smoke-tested (`/`, `/faq/`, `/blog/`, `/pro-mene/`, a guide single) — all 200, header+footer+switcher on each, **zero page errors**. Mobile drawer opens with 10 items, 4 contacts and the pinned CTA.

Rollback if needed: restore `_backup-20260729-101013/db.sql.gz`, or re-enable the TB layouts from the `_ak_orig_*` meta and re-activate CodeKit.

### Cyr-To-Lat: Ukrainian transliteration table

- **Root cause: the plugin has no Ukrainian table at all.** `CyrToLat\ConversionTables::get()` branches only on `bel`, `bg_BG`, `he_IL`, `ka_GE`, `mk_MK`, `sr_RS`, `zh_*`; everything else falls through to the RUSSIAN default. The WordPress locale here is `en_US` (Polylang handles content languages separately), so Russian rules governed a Ukrainian-default site.
- Two distinct defects, measured on production before the fix: `і ї є ґ` were **absent from the table entirely**, so they survived into slugs and got percent-encoded — `Київ` → `ki%d1%97v`; and the shared letters used Russian conventions — `ц`→`cz`, `и`→`i`, `щ`→`shh`, so `Ціни` → `czini`.
- Fixed via the plugin's own `ctl_table` filter in `inc/integrations.php` — the plugin itself is untouched. Mapping follows the Ukrainian national standard (Cabinet of Ministers resolution No. 55, 2010). Russian letters stay mapped so the configured `ru` language does not regress.
- ⚠️ One documented compromise: the standard is context-sensitive (`є ї й ю я` differ word-initially, `зг`→`zgh`), and Cyr-To-Lat's table is a flat character map. The "elsewhere" forms are used throughout — consistent, which matters more for URLs than strict compliance.
- After: `Київ` → `kyiv`, `Ціни та відгуки` → `tsiny-ta-vidhuky`, `Щастя` → `shchastia`, `Гроші` → `hroshi` (`г`→`h`, the mapping most often got wrong).
- **Forward-looking only.** Existing slugs are left alone on purpose — rewriting a published, indexed URL breaks it, and the plugin's bulk converter would do that with no redirects. Audited production: no public URL was affected. The only percent-encoded slugs were an orphaned Elementor template (#223) and a Divi library layout (#602), neither public, plus the four `guide` posts I created during the cutover — those were regenerated (no traffic, not indexed) and verified 200 on production, with the header dropdown pointing at the new URLs.

### Phase 3 begins — hero section rebuilt natively

- [DECISION] **The incremental de-Divi mechanism** (`inc/native-sections.php`). A page's content is one ~200 KB Divi shortcode blob, so rebuilding it section by section needs our version rendered AND Divi's suppressed. Three options: convert the page to Gutenberg (all-or-nothing, so it is where phase 3 *ends*, not how it proceeds); delete the section from `post_content` (irreversible, one bad regex destroys the page); or **strip the leading sections at render time**, driven by a per-page count. Took the third — one filter, instantly reversible by setting the count back to 0, nothing destroyed, and it composes for every following section. A count of leading sections rather than a list of ids, because Divi sections have no stable identifiers and are rebuilt top-down. The stripper walks bracket depth rather than using a regex, since Divi nests `et_pb_section` inside itself for specialty sections.
- Hero built from Figma `1130:3907`: green band with 24px bottom corners, full-bleed photo, gradient overlay, H1 (max 502px), the shared primary CTA + a page-level secondary, caption bottom-left and a two-tone stat bottom-right, both on one baseline 32px above the section bottom. Content is ACF fields on the page; the PRIMARY CTA deliberately comes from Site chrome, not a hero field — `header-standard` requires header/hero/footer to be one funnel, and three editable labels is how they drift.
- The Figma `shadow` layer's 64px `LAYER_BLUR` is **not** reproduced: blurring a linear gradient yields the same gradient for a repaint cost. Noted in the SCSS so nobody "restores" it.

**Two real bugs, both found by measuring the rendered page rather than trusting the code:**

- **`wpautop` was corrupting our markup.** The first version prepended the hero inside `the_content` at priority 5; `wpautop` runs on the same filter at 10 and appended a stray empty `<p>` into `.hero__foot`, which became a third flex item and pushed the stat 330px off its right-aligned position. Measured: three children, the last an empty classless `<p>`. Sections now print from `header.php` after `<div id="et-main-area">` — same position in the document, no content filter near them. The `the_content` filter keeps its one job, stripping Divi sections.
- **A systematic 30px error in the container, affecting the already-shipped header and footer too.** I had implemented Petr's rule as `max-width: 1376` + `padding-inline: 30`, which puts content at x=62. Every section in the design sits at **x=32** with content 1376 wide. The padding is now ADDED to the cap, which satisfies both halves at once: content 1376 wide, inset 30 from the container edge, landing at x=32 on a 1440 viewport. Verified: H1, header logo, footer nav and hero caption all now at x=32; below the cap the padding becomes the viewport gutter, which is its real job.
- Also fixed: `.hero__media` may be a `<picture>`, not an `<img>` — Imagify rewrites images for WebP and moves the class to the wrapper, where `object-fit` does nothing, leaving the photo unsized. Both levels are styled now.
- Verified: 10/10 targeted assertions + 34/34 header regressions. Divi sections 9 → 8, exactly one h1 on the page, hero 1440×734.
- **Not deployed.** Production still has the old container padding and no hero; that is a separate cutover step (files + `ak_native_sections=1` + the hero fields on page 11).

### Hero corrections (Petr review)

- **`.hero__overlay` deleted — it was a real defect, not just redundancy.** I had reproduced the Figma `shadow` layer as a CSS gradient element. That gradient is **already baked into the exported photograph** (confirmed by opening `2026/03/Anna-Kalynyuk.jpg` — the vignette is in the pixels), so the frame was being darkened twice. Legibility of the cream type is unaffected: the baked-in gradient is what provides it. The SCSS now carries a note not to re-add the element — a future photo shipped without a gradient should get one on `.hero__media`.
- [DECISION] **`.hero` caps at `$canvas-max` (1440) and centres** — a deliberate, documented exception to the locked "never put a max-width on the band" rule (Petr, 2026-07-30). Justified by the asset, not by taste: the photo is 2880×1468, exactly 2× the 1440×734 band, so its crop is part of the design, and full-bleed made `object-fit: cover` recrop it on a wide monitor and eat the top and bottom of the frame. **It moves no text** — `container()`'s cap is 1376 + 2×30 = 1436, under 1440, so content lands identically at every width; only the band stops stretching. Scoped in `_tokens.scss`, `design.md` §6 and `.claude/CLAUDE.md` to bands carrying a **fixed-composition image**; colour bands stay full-bleed.
- `hero__caption` now honours the line breaks typed into its ACF textarea: `nl2br( esc_html() )`, in that order. `nl2br` first would emit `<br>` tags that `esc_html` then prints as literal text; skipping the escape would make the field an XSS hole.
- Verified at 1440 and 1900: 0 overlays, band 1440×734 centred, content at x=32 / x=262 matching the header logo, caption 2 lines with one `<br>`, `.hero__foot` with exactly 2 children, caption and stat sharing the y=780 baseline, one `h1`.
- Housekeeping: Imagify and WP Rocket are currently **deactivated on both local and production** (Petr, temporary). `.hero__media` is therefore a plain `<img>`; the dual `<picture>`/`<img>` rule stays in the SCSS so re-enabling Imagify needs no change.

### Hero — mobile layout (Figma `1166:2480`, 375×576)

Mobile is **not** the desktop layout narrowed. Three things genuinely differ, all measured off the frame:

- **Order.** Desktop is H1 → buttons → (caption | stat) side by side. Mobile is H1 → **stat → caption** → button: the stat moves *above* the caption and the CTA drops to the foot of the band (measured y: H1 20, stat 365, caption 427, button 508). Implemented with `order`, keeping the DOM in desktop order (heading → action → supporting copy). That direction is the safer one for the only thing `order` can break — tab sequence — because the CTAs are the section's sole focusable elements, so nothing can be tabbed out of turn relative to anything else.
- **Kit ROLES change, not just sizes.** Caption is Body Regular (20) on desktop but Body **Small** Regular (16) on mobile; stat is H2 (32) on desktop but **H5** (20) on mobile. Two mixins each, one per breakpoint — never a hand-written `font-size`.
- **One button.** The mobile frame carries only the primary CTA, full-width (335×48). The secondary is hidden below tablet. Safe because its destination ("Калькулятор") is a top-level nav item present in the mobile drawer — this removes a shortcut, not access. Noted in the SCSS that the rule has to go if that stops being true.

Also: `object-position` corrected to `center` at every width. The 60% I had assumed the subject sits centre-right — she does not. The mobile frame's photo rect is 867 wide at x=-255, so the visible 375 window centres on 51% of the image, and her face measures ~51% across in the source. Centre is both the design's crop and the photograph's.

- [BUG] **Divi's critical INLINE CSS sets `h1, h2, h3, h4, h5, h6 { padding-bottom: 10px }`** and, being inline in `<head>`, it applies to our headings too. That is where the hero H1's extra 10px came from — 101 tall against the design's 90 on mobile, and on desktop it had been pushing the buttons to y=305 where the frame says 296. Found by measuring; the type mixin was correct throughout. **Zeroed on `.hero__heading` only, deliberately not in the reset:** a global `h1..h6 { padding: 0 }` would win on load order, but it would also retighten every heading in the eight Divi sections still rendering on this page and across the whole site. That is a site-wide visual change and not this section's to make. Repeat the line per native section; hoist it into the reset in phase 4. **Worth a decision from Petr** — a one-line global fix is available the moment Divi content stops mattering.
- Verified at 375: band 375×576, H1 x=20 y=20 w=305 h=91 (design 90), stat x=20 w=182 h=46 above the caption, caption x=20 w=293 with its bottom at 484 — *exactly* the frame's value, button x=20 w=335 h=48 at y=508 with a 20px foot gap, ghost CTA `display:none`, visual order H1 → foot → actions, foot order stat → caption, no horizontal scroll.
- Verified no regression at 768 (order restored, foot back to a row, ghost visible) and at 1440 (buttons now y=295 vs the frame's 296; caption and stat still share the y=780 baseline).

### 🚀 PRODUCTION CUTOVER #2 — the hero + the container fix are live

Second deploy to `https://www.kalynyuk.com`. Smaller and lower-risk than the phase-1 cutover: no Theme Builder surgery, no menu rebuild, no plugin state changed.

**Pre-flight.** Probed production before touching it. Every ID needed turned out identical to local — front page **11**, hero attachment **1785** (`2026/03/Anna-Kalynyuk.jpg`, 2880×1468, present), calculator page **566** — because local is a clone of prod. The field script still asserts all three at runtime rather than trusting that, and aborts on any mismatch.

**Order executed:**
1. Backup → `~/kalynyuk.com/_backup-20260730-164728/` (db.sql.gz 57 MB + theme.tar.gz 93 KB), **outside the docroot**; path written to `~/kalynyuk.com/.last-backup-path`.
2. Files: 40 via tar+ssh. Three are new (`inc/native-sections.php`, `template-parts/sections/hero.php`, `src/scss/sections/_hero.scss`), the rest overwrites. **No deletions**, so no stale-file risk. `.claude/` excluded again and verified absent on the server.
3. DB: the eight hero metas on page 11, each with its ACF `_key` reference so the admin UI resolves them as fields rather than orphaned custom fields. **`ak_native_sections` written LAST on purpose** — it is the switch that stops Divi drawing its own first section, so flipping it first and then failing halfway would have left the page with *neither* hero.
4. Caches: `wp cache flush`, `et-cache` and `cache/et` emptied. WP Rocket is deactivated at the moment, so nothing to purge there.

**Verified on production: 27/29 automated assertions.** Hero band 1440×734 and capped/centred at 1900; no overlay element; one `h1`; H1 and header logo at x=32 (the container fix is live); buttons at y=295 against the frame's 296; caption keeps its line break; caption and stat on the y=780 baseline; stat right edge 1408; photo loaded; **Divi sections 9 → 8**; header CTA on Tally; 6 nav items; no horizontal scroll. Mobile 375: band 375×576, H1 x20/y20/w305, stat above caption, CTA last and full-width 335 with a 20px foot gap, secondary hidden, drawer toggle present. `/faq/`, `/blog/`, `/pro-mene/`, `/calc/` all 200 with header + footer and no stray hero.

**The two non-passes were not defects:**

- *"footer inner at x=32"* — **my assertion was wrong**, not the footer. `.site-footer__inner` is the container BOX (x=2, w=1436); its first child sits at x=32, which is the rule. Same shape as `.hero__inner`. Corrected understanding, nothing to fix.
- *"language switcher has 2 languages" → 0* — **not a regression: the toggle is off on production.** `options_ak_show_lang = '0'`, while Polylang is fine and `ak_language_switcher()` still returns both uk and pt with correct URLs. It was on at the phase-1 cutover, so it was turned off in Site chrome since. Left alone — it is an editorial setting, not a bug. ⚠️ **Local and production now differ on this option.**
- Also caught and cleared: a `b.map is not a function` page error on every production URL. **Pre-existing and not ours** — reproduced identically on local, thrown from `plugins/review-wall/assets/js/libs/remodal.min.js` line 10. Third-party, unrelated to the deploy.

Rollback: restore `_backup-20260730-164728/db.sql.gz`, or — far cheaper — set `ak_native_sections` back to `0` on page 11, which restores Divi's original hero instantly with nothing destroyed. That reversibility is the whole point of the render-time mechanism.

### Multilingual: `/pt/` URLs, sections in every language, translated chrome

Three problems Petr raised, with three different kinds of cause.

**1. `/pt/golovna-portugues/` should be `/pt/` — a Polylang setting, not our code and not a broken translation link.** The translations were wired correctly (11 ↔ 2483 ↔ 2484 ↔ 2485) and Polylang knew each language's `page_on_front`. Read the plugin source rather than guessing — `src/links-model.php::set_language_home_url()`:

```php
if ( empty( $language['page_on_front'] ) || $this->options['redirect_lang'] ) {
    return $this->home_url( $language['slug'] );   // → /pt/
}
return $this->front_page_url( $language );          // → /pt/golovna-portugues/
```

So when a language *has* a translated front page and `redirect_lang` is off, Polylang deliberately uses that page's full permalink. Turned the option on (*Settings → Languages → URL modifications → "The front page URL contains the language code instead of the page name"*). Now `/pt/` 200s and `/pt/golovna-portugues/` **301s** to it. Applies to every language present and future.

- ⚠️ Gotcha worth keeping: the change needs the languages cache **hard-cleared in a separate request** — `delete_transient( 'pll_languages_list' )`. `clean_languages_cache()` in the same request re-populates from the already-loaded language objects, and the URLs look unchanged, which reads exactly like the setting not working.

**2. [DECISION] Native sections now inherit from the default language — `ak_section_field()`.** Section content is per-post meta and a translation is a different post, so a new language started with every field empty. That failure mode was worse than "untranslated": the rebuilt-section count read 0, so **Divi's original section rendered again** and that language silently kept the old design. Add a fourth language and it happens again, with nothing to hint at it.

Now every section field falls back to the default-language twin. A new language renders the native section on day one with Ukrainian text, and the editor replaces it field by field — the layout is never wrong, only the wording is behind. That trade is deliberate: default-language text is visible and self-correcting, a silently un-migrated section is neither. An explicit `0` does not fall back (`''` = unset, `'0'` = a decision), so a translation can still opt out. **Every future section must read through this** — recorded in `.claude/CLAUDE.md`.

Also: the hero image is now an attachment ID rather than ACF's image array (works without ACF, and `ak_translate_id()` picks up a per-language media item), and `alt` comes from the attachment instead of being frozen from one language's ACF copy.

**3. [DECISION] The nav is ONE menu translated at render time, not a menu per language.** Polylang's normal answer is per-language menus; here that would be actively harmful. Only **2 of 10** nav destinations have a Portuguese translation, so a hand-built Portuguese menu would be eight items hard-wired to Ukrainian posts — and would *stay* hard-wired after those pages are translated. `ak_translate_menu_item()` instead resolves each item at render: URL → the translation when it exists, title → the translated post's title *unless the menu label was customised* (a typed label is a decision and must survive), and `current` recomputed against the translated ID, since WordPress compares the queried object to the item's original and would never highlight anything on a translated page. Verified: on `/pt/` the FAQ item switched itself to `/pt/perguntas-frequentes/` while the untranslated items stayed Ukrainian.

**Chrome strings** filled in for `pt`, `en` and `ru` via the existing `pll_register_string()` layer — 11 strings each, editable in Polylang → Translations → Strings without a deploy. ⚠️ The Portuguese (and English/Russian) wording is **mine, for review** — Petr asked for a translation where none existed. "Отримати консультацію" → "Obter consultoria" is the one most worth a second opinion.

**Hero copy** written for pt/en/ru on the three front-page translations — *text only*. The image, the secondary CTA target and the section count were left empty on purpose so they inherit, which both avoids duplicating four values per language and exercises the fallback in production.

Verified on local: `/pt/` renders `lang="pt-PT"`, native hero with Portuguese copy, Divi sections 9 → 8 (so the strip is inheriting), image loaded, CTA "Obter consultoria", drawer "Voltar", back-to-top "Voltar ao topo". Ukrainian unchanged — hero geometry re-measured at 1440 and 375 with no regression.

**🚀 Deployed to production the same day** (backup `_backup-20260730-172634/`, 40 files, then the two data scripts). Production now matches local field for field: `/pt/` 200 with the Portuguese native hero, `/pt/golovna-portugues/` **301 → `/pt/`**, `/en/` and `/ru/` clean too, FAQ nav item self-translated, Ukrainian hero geometry unchanged (x=32, buttons y=295, baseline 780).

⚠️ **Standing item: the language switcher is still toggled OFF on production** (`options_ak_show_lang = '0'`), so nothing in the UI links to `/pt/` — the Portuguese site is now correct but unreachable by clicking. Left off because it is an editorial setting; flagged to Petr. Local has it on, so the two environments still differ here.

### Calculator: Divi removed from page 566

**What the calculator actually is.** Fully custom, hand-written, **no plugin** — 140 KB living in the database inside three Divi *Code* modules plus two Text modules across two sections. Block 1 (~35 KB) is the input UI (16 fields, each a text input paired with a range slider) plus its sync script; block 2 (~12 KB) is the results panel and the maths: the annuity payment `(L·i)/(1−(1+i)^−n)`, the DSTI ratio and gauge, LTV, TAN, indexante, spread, **Imposto do Selo** and **IMT** for mainland and islands. Sending the result is Gravity Forms form 3. **Zero dependencies** — no jQuery, no Divi API — which is why it ported almost unchanged. The 1518 `et_pb_line_break_holder` markers that bloated the source are simply Divi's newline placeholder and vanished with the export.

- [DECISION] **The section control changed from a COUNT to an ordered LIST** (`ak_sections`). The old shape stored only "how many leading Divi sections have been rebuilt" and `ak_render_native_sections()` hardcoded the hero. That was honest with one page and one section; the moment a *second* page needed a *different* section, a number no longer carried enough information — it says how many to strip but not what to draw. The strip count is now **derived** from the list, which also removes a whole class of bug: previously setting the count to 2 while rendering one section would silently delete a Divi section and put nothing in its place. Adding a section is now two steps and no edit to the renderer — drop `template-parts/sections/{slug}.php` in place and register the slug in `ak_section_registry()`. Pages 11 and 566 migrated; the legacy numeric meta is deleted so there is one source of truth.
- [DECISION] **The CSS and JS were ported VERBATIM into quarantine files** (`_calculator-legacy.scss`, `calculator.js`), Petr's call. They deliberately violate the project conventions — magic numbers, `!important`, non-BEM names — and the `-legacy` suffix is the marker. The point is that markup and rules moved together untouched, so the page can be diffed against production pixel for pixel and a visual regression can never be confused with a migration bug. Rewriting to BEM + tokens is a separate, later step.
- **The maths is byte-identical and is not to be "improved"** — IMT, Imposto do Selo and the DSTI thresholds are Portuguese regulated figures on the site of a licensed credit intermediary. Verified: local and production compute the same numbers to the cent (925,89 € · DSTI 30% · 170 000 € · LTV 85% · Selo 1 600 € · IMT 3 747,08 €).
- Two structural changes were unavoidable in the move, neither touching logic: each block is wrapped in its own IIFE (as inline `<script>` tags they shared global scope and declare several of the same names — block 2 got away with it only because its body sat inside a `DOMContentLoaded` callback), and that callback became a plain IIFE, because the module is called from `main.js` which already runs on or after DOM ready — the listener would have registered too late and left the results panel frozen at 0 €.
- The Divi row's geometry (`column_structure=1_2,1_2`, `custom_padding=24px|24px|0px|24px`, `make_equal=on`, `background_color=#FEF8EF`) was read off the shortcode attributes rather than guessed, and reproduced in the new `.calculator__panel`.
- **Not migrated on purpose:** the help popup is still the page's second Divi section. It is 6 KB of editorial copy, and moving it into the theme would hardcode client content that belongs in the editor.

**Two pre-existing defects surfaced by the port, both measured on production:**

- [BUG] **The calculator page has no `h1` at all** — the Divi text module used an `h2` and nothing sat above it. A page with no `h1` is a real SEO and screen-reader defect. The title is now an `h1`; the `h1` role is 48px, which is exactly what Divi was already rendering that `h2` at, so the page keeps its current size and gains a correct outline with nothing to reconcile.
- [BUG] **The "Довідка" help link is dead on production.** It has never had any CSS — grepped the ported blocks, our SCSS and Divi's custom CSS, nothing matches `.dovidka` — so on production it collapses to a **0×0 box at 0,0** and clicking it opens nothing. After the port the popup does open (verified: the click creates a visible panel carrying the help copy), so the link was given the minimum styling to be a real tappable control. Proper treatment belongs with a redesign.
- Verified at 1440 and 375: 16 inputs, all eight computed values matching production exactly, Gravity Form present, **0 Divi sections rendering**, bundle executing, no horizontal scroll.

**Not deployed.** Local only so far.

### 🔴 Calculator: the client's "wrong results" bug — diagnosed, NOT silently fixed

Client report: *"the coefficients don't add up, the calculator gives a wrong result — started a few months ago."* Two separate causes, and the second is the serious one.

**1. The index was hardcoded, which explains the timing.** `const INDEXANTE_CONST = 0.02143` — a Euribor snapshot frozen in the page. Euribor moves; the constant did not, so variable-rate results drifted further from reality every month and only a developer could correct them. **Fixed structurally:** it is now an editable field (Page → Calculator → *Indexante*), defaulting to the same 2.143, so **no number on the page moves today** — the value simply becomes correctable without a deploy.

**2. [BUG] The DSTI is computed from a rate that has the index SUBTRACTED from it, and the page contradicts itself.**

```js
interestRateForDSTI = fixedRateUI - INDEXANTE_CONST;   // 2.80% − 2.143% = 0.657%
```

No regulatory knowledge is needed to see this is wrong — **the two numbers on screen disagree**. With the default inputs the page displays a payment of **925,89 €** and an income of **2 500 €**, and then reports **DSTI 30%**. 925.89 / 2500 = **37.0%**. The 30% comes from a shadow payment of 756,08 € computed at 0.657%, a rate that appears nowhere and that no borrower will ever pay.

Reproduced with the calculator's own formula:

| Rate used for DSTI | Payment | DSTI |
|---|---|---|
| 2.800% — the contract rate, and what the page shows | 925,89 € | **37.0%** |
| 0.657% — 2.80% *minus the index*, **as currently coded** | 756,08 € | **30.2%** ← displayed |
| 4.300% — contract +1.5pp stress | 1 057,24 € | 42.3% |
| 5.800% — contract +3.0pp stress | 1 198,40 € | 47.9% |

The error runs in the **optimistic** direction: affordability is overstated by ~7 points before any stress test is even considered. On a licensed credit intermediary's site that is the worst direction to be wrong in. Note also that the variable branch applies no stress at all (`interestRateForDSTI = interestRate`), whereas Banco de Portugal's macroprudential recommendation expects an interest-rate shock precisely there.

⚠️ **Deliberately NOT changed.** Whether the correct figure is 37% (contract rate) or 42–48% (with the BdP stress increment) is a regulatory decision for Anna, not a refactor. Changing it alters what every visitor is told about their own affordability. The diagnosis and the numbers above are for her to confirm; the one-line fix takes minutes once she does.

**Also worth her eye:** the IMT bracket table is hardcoded for one tax year, and Imposto do Selo is only the 0.8% on the purchase price — the 0.6% on the loan itself (verba 17.1) is not included. Neither was touched.

### Calculator: page 566 is now 100% Divi-free

- The help popup ("Довідка") migrated out of the second Divi section. Its 4 KB of copy moved **verbatim** into an ACF WYSIWYG field on the page, so it stays editable where an editor expects it rather than being hardcoded in the theme.
- Replaced the Divi Popups modal with our own panel, reusing the mobile drawer's proven contract: Escape closes, scrim closes, focus moves in and returns to the trigger, body scroll locked. The old `<a href="#infobox">` became a real `<button>` with `aria-expanded` — it had been a 0×0 element on production whose click did nothing.
- [DECISION] Added `ak_strip_extra`. One native section does not always replace exactly one Divi section: the calculator template renders both the calculator *and* the help panel, so two Divi sections must come out while only one slug is listed. Stated explicitly rather than inventing a placeholder slug that renders nothing, which would be a lie in the section list.
- **Verified 10/11 → all real:** page renders **zero** Divi sections, one `h1`, index data-driven, help content intact (16 headings), panel opens/locks/focuses/closes correctly, 16 inputs + Gravity Form, no horizontal scroll. **All ten computed values byte-identical to production** (mensalidade, DSTI, montante, prazo, LTV, TAN, indexante, spread, Selo, IMT) — the port changed no arithmetic.
- Two initial "failures" were **my test's fault, not the page's**: the value comparison used a plain space where `toLocaleString('fr-FR')` emits a narrow no-break space, and the visibility check used `offsetParent`, which is always null for a `position: fixed` element. Both assertions corrected and re-run.

### Article template + menu label translations

**⚠️ FINDING FIRST: the four Guides have no content.** They are the empty placeholders I created during the phase-1 cutover so the Portugal dropdown had destinations — title only, `content_len=0`, no featured image, no topic. So "translate the Guides articles" has nothing to translate. **I have not written them**: they are about Portuguese mortgage regulation (IMT and Imposto do Selo rates, documents required of non-residents, bank criteria) on the site of a licensed credit intermediary — `Intermediário de crédito n.º 0008515` — and inventing figures there is a liability, not a draft. The copy has to come from Anna. Flagged to Petr; nothing was faked and no empty `pt` guide stubs were created either, since four more empty indexable pages would be worse than none.

- [DECISION] **Menu labels are now translatable independently of their target page.** `ak_translate_menu_item()` alone could only translate a label by following the target post's translation, and 8 of the 10 destinations are Ukrainian-only — translating a whole Divi page is a content project, so the header would have sat in Ukrainian on `/pt/` until then. Labels are now registered as Polylang strings **by value, not by menu-item id**, so they survive a menu rebuild, a reorder and a new language. Precedence: an explicit string translation → the translated post's own title → the original label. FAQ deliberately gets no string, so it keeps taking its label from the translated page, which is where the client controls it. pt/en/ru filled in; wording is mine for review (`Калькулятор` → "Simulador", the pt-PT banking term, not "Calculadora"; `Відгуки` → "Testemunhos", not "Avaliações").

**Single article template** (Figma `1139:10037`) — `single-guide.php` plus `template-parts/article/*`, `inc/article.php` and `sections/_article.scss`. Deliberately post-type agnostic because Petr's brief is that the blog will use the same layout: a future `single.php` is a copy of the same four lines. Masthead (breadcrumb, topic chip, 48px title, date + reading time, author card), the **348px sticky TOC rail beside a 680px prose column** — measured, and asymmetric on purpose, the right third stays empty — the "Порада" callout, the share row, and "Інші статті".

**Five defects, every one found by measuring or looking at the render, not by reading the code:**

- **Paragraphs rendered at 36px instead of 20.** I had hooked the lead paragraph to `.has-large-font-size`; WordPress emits its font-size presets with `!important`, so the class can only be won back with an `!important` war over a value core treats as the user's. Moved to a theme-owned `.ak-lead`.
- **Every heading was glued to the paragraph below it.** `.article__prose p { margin: 0 }` is (0,1,1) and outranks the flow rule `.article__prose > * + *` at (0,1,0), so the flow spacing never applied anywhere. The margin reset now uses `:where(...)`, which contributes zero specificity, letting source order decide. Verified per element: first child 0, paragraphs 24, H2s 40.
- **`* + &` inside a nested block does not mean what it looks like.** `&` expands to the whole compound, so `* + &` became `* + .article__prose h2` and silently never matched. Rewritten as explicit top-level rules.
- **Anchor ids were percent-encoded Cyrillic** — `#%d0%bf%d0%be%d0%bc…`. `sanitize_title()` does that to every Cyrillic heading on this site. Now falls back to the Ukrainian transliteration table the theme already owns for Cyr-To-Lat, so slugs and anchors romanise identically: `#pomylka-1-pochynaty-poshuk-zhytla-bez-rozuminnia-biudzhetu`.
- **The topic chip sat on the same line as the breadcrumb.** `display: inline-block` joins the preceding inline element's line box; the design has it on its own line. Now `display: block; width: fit-content`.
- Also: `str_word_count()` is byte-based and returns garbage on Cyrillic — the reading time uses a Unicode-aware split. And "1 хвилин" was wrong Ukrainian, so the label now carries three plural forms picked with the Slavic rule; a translator sets `few`/`many` to the same wording for languages that do not need it.

- [DECISION] The **author byline is a translated string, not the WP user**. A WordPress user has one display name and the byline must read "Анна Калинюк" in Ukrainian and "Anna Kalynyuk" in Portuguese; the actual post author here is the agency admin account (`NormlAdmin`), which would either show the wrong byline or have to be renamed and misattribute the audit log. The post author stays as the fallback.
- **Instagram is in the design's share row and is not implemented.** It has no web share endpoint — Meta does not accept a URL from a browser — so the button could only ever be decorative. Facebook, LinkedIn and copy-link ship; the omission is documented in the template rather than silent.
- **Not built, deliberately:** the "Опрацювати за допомогою ШІ" block (`1163:393` — create post / summarise / extract quotes). It is a product feature, not a layout, and needs decisions nobody has made yet.
- Verified at 1440 and 375 on a temporary fixture built from the design's own copy (created, measured, deleted — never on production): TOC x=32 w=348 and prose x=380 w=680 exactly as drawn, sticky on desktop and static on mobile, title 48/32, prose 20, lead 24, H2 32/28, callout on surface at radius 24, 3 related cards, one `h1`, no horizontal scroll, no Divi sections on the page.

**🚀 Deployed to production** (backup `_backup-20260731-132144/`, 48 files, then menu strings + article strings + guide authors, rewrites and caches flushed).

- [BUG] **A sixth defect, and only production could have found it.** The first prod check failed one assertion: `.article__column` measured **348 wide at x=32** instead of 680 at x=380. The TOC renders nothing on an article with fewer than two H2s, and CSS grid auto-placement then dropped the prose into the FIRST track — the 348px rail. Invisible locally because the render fixture had four headings; the real guides have none, so every live guide would have shown its text crammed into the sidebar column. The left rail is part of the layout rather than a consequence of the TOC existing, so `.article__column` now claims `grid-column: 2` explicitly and the rail simply stays empty. **The lesson is the general one: a fixture built to exercise a feature is the worst case for finding what happens when the feature is absent.**
- Article-chrome strings translated for pt/en/ru as well (12 each) — reading time carries the three plural forms, and for Portuguese and English `few`/`many` are set to the same plural so "1 minute" / "5 minutes" comes out right with no special case.
- Guide authors assigned on production (they were `post_author = 0`, a WP-CLI artefact from the phase-1 cutover).
- **Verified on production: 16/16.** Guide 200 with the article template, breadcrumb + 48px title, author card, prose column 680 at x=380, TOC correctly absent, share row FB/LI/copy, 3 related cards, one `h1`, no Divi sections, no horizontal scroll. Portuguese nav now reads *Sobre mim · Portugal · Simulador · Blog · Testemunhos · FAQ* with the whole dropdown translated, `/pt/` still serves the native Portuguese hero, and the Ukrainian home is untouched (hero 1440, H1 at x=32).

### ✅ Repo moved to the Norml-Studio org

- `petyasavenok-dev/kalynyuk` → **`Norml-Studio/kalynyuk`**, done via GitHub **Transfer ownership** rather than a re-push. Transfer keeps commits, branches, tags, issues and PRs and installs a permanent redirect; a fresh push would have kept only commits and left two repos that both looked canonical.
- ⚠️ The precondition, for next time: **do not pre-create the repo in the target org** — a name collision is the one thing that blocks a transfer.
- `origin` re-pointed locally. Verified after the switch: 23 commits intact, `main` tracking `origin/main` at the same SHA `71adadc`, push clean. Also confirmed the redirect is live — `git ls-remote` against the **old** URL still returns the same ref, so a teammate's stale clone keeps working instead of silently diverging.
- Repo access does not come with org membership; team permissions still need granting on the repo itself.
- Corrected two stale rows in `.claude/CLAUDE.md`: source hosting said Bitbucket (it has always been GitHub) and the repo URL was still a `[fill in]` placeholder.
