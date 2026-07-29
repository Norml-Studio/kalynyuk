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
