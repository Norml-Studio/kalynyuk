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
