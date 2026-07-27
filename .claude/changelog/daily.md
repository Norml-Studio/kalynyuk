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
