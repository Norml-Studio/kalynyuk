# Anna Kalynyuk — WordPress Development Context

> This `.claude/` folder is the master context layer for this WordPress
> theme. Everything Claude needs to know about the codebase lives here:
> the architecture docs (`docs/`), the design contract (`design.md`), the
> deployment contract (`ci-cd.md`), a self-compressing changelog, and
> project-specific skills.

## Project overview
- **Project:** Anna Kalynyuk
- **Theme slug:** `anna-kalynyuk---norml-studio-theme`
- **Parent theme:** Divi 4.27.7 (stock, vendored — never edit)
- **Source hosting:** GitHub — the repo root is the THEME folder, not the WP install
- **Production:** https://www.kalynyuk.com
- **Staging:** [fill in URL — none known]
- **Repo:** `git@github.com:Norml-Studio/kalynyuk.git` — ✅ transferred to the org 2026-07-30 (from the personal `petyasavenok-dev/kalynyuk`, via GitHub **Transfer ownership**, so history / issues / PRs survived). GitHub keeps a permanent redirect from the old URL: `git@github.com:petyasavenok-dev/kalynyuk.git` still resolves to the same refs, so a teammate's stale clone keeps working and does not silently diverge. **Do not rely on that** — set the remote properly on any existing clone:

  ```bash
  git remote set-url origin git@github.com:Norml-Studio/kalynyuk.git
  ```
- **Local environment:** OpenServer (Apache + PHP 8.1 + MySQL 5.7) at `f:\localsites\kalynyuk.loc`, served on `http://kalynyuk.loc`
- **Drive folder:** [fill in Norml Drive path]
- **Project slug:** `anna-kalynyuk`
- **Content languages:** Ukrainian (default) · Portuguese · English · Russian, via Polylang

## What this project actually is

A **Divi Builder site**. The child theme is 3 files and ~40 lines of PHP; it holds
two shortcodes and an enqueue. **All layout and content lives in the database as
Divi shortcodes**, assembled through the Divi Theme Builder (global header / footer
/ post templates) and per-page Divi layouts. Custom CSS lives in the database too,
in a `custom-code` post edited through the CodeKit plugin — not in the theme.

Practical consequence: *do not go looking for page layout in PHP files.* Read
`docs/04-content-structure.md` to find which Divi Theme Builder template or page
layout owns the thing you're changing.

## Production access

Server connection info, SSH details, WP admin reference, and credential-store entry
names live at:

```
~/.config/norml-studio/credentials/
```

When you need to connect to production / staging:
1. Read `~/.config/norml-studio/credentials/projects/anna-kalynyuk.json` for project-level pointers (URL, server reference, WP admin URL, credential entry names).
2. From that file, read `~/.config/norml-studio/credentials/servers/{server-slug}.json` for SSH details.
3. For password-style secrets (WP REST API app passwords, DB), read them from **Windows Credential Manager** at runtime.

✅ **Resolved 2026-07-28.** `projects/anna-kalynyuk.json` and
`servers/admtools-anna-kalynyuk.json` both exist, and key-based SSH is verified
working. Connect with the config alias:

```
ssh anna-kalynyuk          # nu538012@nu538012.ftp.tools, key ~/.ssh/anna-kalynyuk-admtools
```

Production docroot: `/home/nu538012/kalynyuk.com/www`.

⚠️ Two standing constraints on this host, both recorded in the credentials files:
- **The SSH account hosts three sites** — `hresko.com.ua`, `kalynyuk.com`,
  `s1904672.bolila.pt`. Scope every command to `~/kalynyuk.com/www`; never run
  recursive operations from `~`.
- **Access is delegated from the site owner** and the panel marks it *temporary* —
  it is not a Norml-owned account, and it can lapse. Confirm scope with Petr before
  anything destructive.

**Never paste credentials into this CLAUDE.md or any committed file.**

## WP-CLI access

Config lives at `wp-config.json` (sibling of this file). WP-CLI is **not on PATH**
on this machine — it runs as a phar through OpenServer's PHP:

```
"D:\OpenServer\modules\php\PHP_8.1\php.exe" "C:\Users\Petr\wp-cli.phar" --path=f:/localsites/kalynyuk.loc <command>
```

Use `eval-file <script.php>` rather than inline `eval` — PowerShell mangles `$` and
quoting when passing PHP to a native executable.

**On production**, WP-CLI *is* installed (`/usr/local/bin/wp`) but its `env php`
shebang resolves to the host default PHP **5.6.40**, so bare `wp` fatals on a
Composer platform check. Always pass a modern PHP explicitly:

```
ssh anna-kalynyuk 'cd ~/kalynyuk.com/www && php8.2 /usr/local/bin/wp <command>'
```

`/usr/bin/php5.2` … `/usr/bin/php8.5` are all present (CloudLinux alt-php). Don't
change `~/.cl.selector/defaults.cfg` — it's account-wide and would affect the two
other sites.

## CI/CD pipeline

This project's deployment pattern, environments, database strategy, backup strategy,
and permissions matrix live in `./ci-cd.md` (sibling of this file). **Every dev skill
that touches deploy or DB reads `ci-cd.md` first** — `vibe-wp-developer`,
`dev-wp-developer`, `vibe-wp-manager`, `dev-wp-migration`. The catalog of patterns
lives in `{norml-claude-skills}/.claude/skills/dev-ci-cd/`.

✅ **`ci-cd.md` is OPERABLE** — both original blockers (no repo, no credentials) closed
2026-07-28/30, first real deploy 2026-08-03, calculator go-live 2026-08-06.

⚠️ This line read "Deploys are blocked" until 2026-08-06 — three days after deploys
started working, and `ci-cd.md` had already logged its own STATUS block going stale the
same way. **Two files now state deploy readiness; when it changes, change both.**

## Design contract — REQUIRED READING

`./design.md` (sibling of this file) is this project's **binding design contract**:
palette tokens, type scale, spacing / radius / elevation, the surface + canvas rule,
container and layout, per-component specs, imagery, motion, do/don't, and a CSS export.

**Every skill that writes or edits HTML / CSS / Blade / a block / a page MUST read
`design.md` first and conform to it.** It ranks alongside `ci-cd.md` (how it deploys):
`design.md` is **how it looks**, and it is not optional.

Rules:
- **Never invent a value that isn't in `design.md`.** Derive from the scales there. If the design genuinely needs a new token, add it to `design.md` first, then use it.
- **Never introduce a second accent hue, a new radius step, or a per-section background** — `design.md` §5 holds the one-canvas rule.
- `design.md` sits UNDER the universal floor in `design-anti-slop` and the structural contract in `vibe-frontend-standards`; where it deliberately overrides a house default, it says so explicitly and wins.

If `design.md` is missing or stale, run `dev-wp-init-project` Stage 6 (*"rescan design"*).

## Architecture docs

`docs/` contains the full technical architecture of this project, produced and
maintained by `dev-wp-init-project`'s scrape pipeline. **Read these before doing any
implementation work.**

- `docs/01-infrastructure.md` — server, PHP, database, caching, cron
- `docs/02-application.md` — themes, plugins (grouped by function), page builder, plugin interaction map
- `docs/03-theme-architecture.md` — every file in the theme, the Divi Theme Builder layer, shortcodes, CPTs, the CodeKit CSS layer
- `docs/04-content-structure.md` — pages, menus, Polylang wiring, how each page is built
- `docs/05-issues.md` — problems and inconsistencies found

To refresh just one layer, say *"rescan theme architecture"* (Stage 3 only), etc.

## Changelog

Three-tier rolling changelog that compresses itself so history never becomes junk:

- `changelog/daily.md` — raw entries from today's session
- `changelog/weekly.md` — compressed summary of the current week
- `changelog/changelog.md` — long-term history

See `changelog/README.md` for the rollover protocol. Use the `dev-changelog` skill.
**Write to the changelog at the end of any session where something changed, was
decided, or was discovered that future sessions need to know.**

## Project-specific skills

`skills/` is where you add skills that only make sense for this project. Starts
empty. Add skills as real patterns emerge — don't pre-design them.

For everything else, use the global Norml skills:
- `dev-wp-init-project` — refresh `docs/` / `design.md`, or re-scaffold `.claude/`
- `dev-wp-developer` — Norml's WordPress architecture and the deep-dev pipeline
- `vibe-wp-developer` — AI-led closed-loop builds for small / templated sites
- `vibe-wp-manager` — manage content / media / users on a live site
- `dev-wp-migration` · `dev-wp-pentest` · `dev-changelog` · `dev-code-review` · `norml-git`

## Norml Studio standards

Read `Norml Drive/.claude/docs/standards.md` before doing any work — naming,
changelog, code review, WP standard, and skill versioning rules.

## SCSS conventions — LOCKED (Petr, 2026-07-27)

The project follows `dev-bem-scss`, with two project-specific rules that override or
sharpen it. Both were set by Petr and are not up for re-litigation.

### 1. Nest elements with `&__`, one level, mirror-commented

```scss
// .site-nav
.site-nav {
  display: none;

  // .site-nav__list
  &__list {
    display: flex;
  }

  // .site-nav__item--has-children
  &__item--has-children {
  }

  // .site-nav__panel
  &__panel {
    // media queries stay INSIDE the element they affect
    @include m.bp(desktop) {
      padding: t.$space-3;
    }
  }
}
```

- The block opens once; every element and modifier is `&__…` inside it.
- **The `// .full-class-name` mirror comment above each rule is mandatory** — it is
  what makes `grep .site-nav__panel` find the SCSS, since `&__panel` alone is
  ungreppable. `dev-bem-scss` requires this and the nested style makes it
  load-bearing rather than decorative.
- One level of `&__` only. No `&__a &__b` chains — re-block instead.
- Media queries live inside the element, never in a trailing block.
- Compiled output is identical to the flat form; this is purely how it is authored.

### 2. Divi selectors live in exactly one file

`src/scss/base/_divi-compat.scss` is the **only** file allowed to contain
`#page-container`, `#et-main-area` or `.et_*`. Everything else styles our own BEM
classes. Phase 4 then deletes one partial and one `@use` line instead of hunting Divi
bleed across every section.

Page-specific third-party overrides (e.g. the Review Wall on page 2133) go in a
`sections/` partial, not in `base/`.

### 3. The container rule

Canvas 1440 · container **1376** · padding **30 desktop / 20 mobile**. The band is
full-bleed and owns the background; `&__inner` gets `@include m.container`. Never put
a `max-width` on the band. Full rationale and the superseded measurements are in
`design.md` §6.

**One exception, added 2026-07-30:** a band whose background is a *fixed-composition
photograph* — today `.hero` alone — also caps at `$canvas-max` (1440) and centres, so
a wide viewport does not recrop the photo. Colour bands never do. See `design.md` §6
→ "The one exception".

## Multilingual — LOCKED (Petr, 2026-07-30)

Three rules. They exist so that **adding a language is an admin action with no code
change**, which is the standing requirement on this project.

### 1. Section fields go through `ak_section_field()`, always

```php
$heading = ak_section_field( 'ak_hero_heading', $id );   // ✅
$heading = get_field( 'ak_hero_heading', $id );          // ❌ breaks on translations
```

Section content is per-post meta and a translation is a *different post*, so a new
language starts with every field empty. Without the fallback that is not merely
"untranslated" — the rebuilt-section count reads 0, **Divi's original section renders
again**, and that language silently keeps the old design. With it, a new language
gets the native section on day one carrying the default language's text, and the
editor replaces the text field by field. An explicit `0` still opts out (`''` means
unset, `'0'` is a decision). Full reasoning is in the docblock in
`inc/native-sections.php`.

### 2. The nav is ONE menu, resolved per language at render time

`ak_translate_menu_item()` swaps each item's URL — and its title, unless the menu
label was customised — to the current language's translation, and recomputes the
"current" flag against the translated ID. Do **not** build per-language menus: only
2 of 10 destinations have a Portuguese translation, so a hand-built menu would be
eight items hard-wired to Ukrainian posts *and would stay hard-wired* after those
pages are translated. Assigning a real menu in Polylang still overrides this if
genuinely different labels are ever wanted.

### 3. UI strings go through `ak_str()` / `pll_register_string()`

Not ACF options — an ACF options page is global, one value for all languages. Strings
are editable per language in **Polylang → Translations → Strings**, so a new language
appears there on its own. `pt` / `en` / `ru` values are already filled in.

### The front-page URL setting

`polylang.redirect_lang` **must stay ON**. Polylang's `set_language_home_url()` uses
the translated front page's full permalink as that language's home URL unless this is
set — which is what produced `/pt/golovna-portugues/` instead of `/pt/`. In wp-admin
it is *Settings → Languages → URL modifications → "The front page URL contains the
language code instead of the page name"*. It also 301s the old slug URLs.

⚠️ Changing it needs the languages cache **hard-cleared** — `delete_transient(
'pll_languages_list' )` in a separate request. `clean_languages_cache()` inside the
same request re-populates from the already-loaded objects and looks like a no-op.

## Build

Vite. From the theme folder:

```bash
npm run dev      # watch — rebuilds on save
npm run build    # one-off production build
npm run clean    # wipe dist/
```

`dist/` is committed (production has no build step), so **run `npm run build` before
committing** or the repo ships stale CSS against new SCSS. Editing `src/` alone changes
nothing on the site — the browser loads `dist/`. PHP edits need no build. Cache-busting
is `filemtime()`, so a rebuild is picked up on the next load with no purge.

## Key rules
- **Never edit the Divi parent theme, `wp-includes/`, `wp-admin/`, or any plugin folder.** Updates overwrite the changes.
- **New CSS goes into CodeKit** (`wp-admin → Custom Codes → Public Side SCSS`), not into the child `style.css`. See `docs/03-theme-architecture.md` for how that compiles.
- **New PHP hooks / shortcodes** go into the child theme `functions.php`.
- Always read `docs/03-theme-architecture.md` before touching theme code and `docs/04-content-structure.md` before touching page content.
- Local `WP_DEBUG` is off and WP Rocket is on. When diagnosing, flip `WP_DEBUG` in `wp-config.php`, then turn it back off and purge `wp-content/cache/` + `wp-content/et-cache/`.
- **Polylang is active.** Anything content-shaped has a language. Never create a page/post without deciding its language and its translation links.
- Log meaningful changes to `changelog/daily.md` at end of session.
- **`.claude/` is for AI, top-level is for humans.** AI-shaped artifacts go inside `.claude/`; source files a teammate opens during routine editorial / design work stay at the top level. See `norml-claude-skills/CLAUDE.md` → Project Folder — `.claude/` vs Top-Level.
