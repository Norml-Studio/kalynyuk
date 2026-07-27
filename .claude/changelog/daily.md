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
