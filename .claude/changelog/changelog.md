# Changelog — Anna Kalynyuk

<!-- One entry per completed week, compressed from weekly.md. Decisions and
integrations are never compressed away. See README.md for the protocol. -->

## Week 2026-W31

**Scope: project set-up, the whole chrome layer, and the first native sections.** Started
from a Divi site whose child theme was 3 files and ~40 lines of PHP, with all layout,
content and CSS living in the database.

### Foundations

- Ran `dev-wp-init-project`: created `.claude/` with `CLAUDE.md`, `ci-cd.md`, `design.md`, the three-tier changelog and `docs/01`–`docs/05` scraped from the live site.
- Brought the theme under version control (the repo root **is** the theme folder). Later moved to `Norml-Studio/kalynyuk` via GitHub **Transfer ownership** rather than a re-push, so commits, issues, PRs and a permanent redirect all survived. ⚠️ Precondition for next time: do **not** pre-create the repo in the target org — a name collision is the one thing that blocks a transfer.
- Split `functions.php` into `inc/{setup,helpers,assets,shortcodes,integrations}.php`, prefixed globals `ak_*`, and made `load_child_theme_textdomain()` actually run — it never had, on a four-language site.
- Fixed the enqueue chain and **corrected a wrong entry in `docs/05-issues.md`**: the duplicated stylesheet was the *child's*, not the parent's.
- Production SSH resolved, recorded in `~/.config/norml-studio/credentials/`. Two standing constraints: the account hosts three sites (scope every command to `~/kalynyuk.com/www`), and the access is delegated from the site owner and marked temporary.

### Decisions that still govern the project

- **[DECISION] Vite as the build step** (Petr). `dev-bem-scss` needs `src/scss/` partials with `@use`/`@forward`; CodeKit compiles one blob from the database and cannot do partials. `dist/` is committed because production has no build step.
- **[DECISION] Mobile-first**, resolving a conflict between two hard rules. The competing `max-width` requirement is justified by Playwright rendering *mockups* to PDF, and that rationale does not transfer to a production theme. The three structural breakpoints are preserved exactly; only the direction is inverted.
- **[DECISION] Container rule locked** (Petr): canvas 1440 · container **1376** · padding **30 desktop / 20 mobile**; the band is full-bleed and `&__inner` is capped. One exception followed: `.hero` caps at `$canvas-max` because its background is a fixed-composition photograph.
- **[DECISION] SCSS authoring style locked** (Petr): the block opens once, elements nest as `&__element`, and the `// .full-class-name` mirror comment above each rule is mandatory — it is what makes `grep` work at all.
- **[DECISION] `src/scss/base/_divi-compat.scss` is the ONLY place Divi selectors may live**, so phase 4 becomes "delete one partial and one `@use` line".
- **[DECISION] Chrome text vs config split.** Translatable text → `pll_register_string()`; non-translatable config → one global ACF options page. A per-language ACF options page was rejected — too much blast radius for a phone number.
- **[DECISION] Removed Divi's hardcoded viewport meta** (`maximum-scale=1.0, user-scalable=0`) — a WCAG 2.1 failure.
- **[DECISION] The incremental de-Divi mechanism** (`inc/native-sections.php`): leave `post_content` untouched and remove sections at RENDER time. Instantly reversible, destroys nothing. Later gained **in-place replacement addressed by `module_id`** and `ak_strip_extra`, for when one native section replaces more than one Divi section.
- **[DECISION] The section control is an ordered LIST, not a count** — the strip count is derived from it, so "how many to remove" and "what to draw" can no longer drift apart.
- **[DECISION] Native sections inherit from the default language** via `ak_section_field()`. Without it a new language does not merely go untranslated: the section count reads 0 and **Divi's original silently renders**, so that language keeps the old design invisibly.
- **[DECISION] The nav is ONE menu translated at render time.** Only 2 of 10 destinations had a pt translation, so per-language menus would have hard-wired eight items to Ukrainian posts *and stayed hard-wired* after translation. Menu labels are translatable independently of their target page.
- **[DECISION] The author byline is a translated string, not the WP user** — one user has one display name, and the byline must read correctly in four languages.
- **[DECISION] Calculator CSS and JS were ported VERBATIM into quarantine files** (Petr), deliberately outside the token system, to be rewritten later.
- **[INTEGRATION]** The CSS layer was the CodeKit plugin (`custom-code` post 1907 → `wp-content/custom_codes/`), **outside** the theme folder — so a theme-only deploy shipped no CSS at all. Retired this week.

### Shipped

Custom `header.php` / `footer.php` (keeping Divi's `#page-container` / `#et-main-area` wrapper contract until phase 4), mobile drawer, language switcher, chrome ACF options, the **hero** (desktop + mobile), the **calculator** de-Divi'd on page 566 and unified with the homepage copy, the article template, and Cyr-To-Lat transliteration. Two production cutovers landed: the phase-1 chrome, then the hero plus the container fix.

**Two Critical findings on production**, both fixed: the visible primary nav was a Divi menu module with *no menu assigned*, so Divi fell back to `wp_page_menu()` — 12 items including orphaned pages; and **no language switcher rendered at all**, making the Portuguese homepage unreachable from the UI.

🔴 **The calculator existed twice** — page 566 and the homepage, with ~95 KB of byte-identical CSS. Now one template. 🔴 The client's "wrong results" complaint was **diagnosed and deliberately not silently fixed**: the indexante was hardcoded in the JavaScript at 2.143%, which is why results drifted as Euribor moved. It is now an editable field.
