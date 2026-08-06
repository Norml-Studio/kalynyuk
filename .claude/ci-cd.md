# CI/CD — Anna Kalynyuk

> This file is the per-project deployment contract. Every dev skill that touches deploy
> or DB reads this file FIRST. The cataloged patterns live in
> `{norml-claude-skills}/.claude/skills/dev-ci-cd/`.

## ✅ STATUS: OPERABLE — first real deploy 2026-08-03 · calculator go-live 2026-08-06

Both original blockers are closed:

1. ~~No git repository.~~ **`git@github.com:Norml-Studio/kalynyuk.git`** — the repo root is the THEME folder. Resolved 2026-07-30 (GitHub, not Bitbucket; the pattern name still says Bitbucket).
2. ~~No credentials file.~~ **`~/.config/norml-studio/credentials/projects/anna-kalynyuk.json`** and `servers/admtools-anna-kalynyuk.json` both exist, key-based SSH verified. Resolved 2026-07-28.

⚠️ **This STATUS block was stale for four days** — it still read "DEPLOYS BLOCKED" on
2026-08-03, long after both gaps closed, and every dev skill reads this file first. If you
close a blocker, update this block in the same session.

### ⚠️ THE ONE THING THAT MAKES THIS PROJECT'S DEPLOY UNUSUAL

**Code and content are two separate deploys, and the code is inert without the content.**
Which native sections render is decided by **per-page post meta**, not by the theme — see
`inc/native-sections.php`. So `git push` + file sync ships the *capability*; the section
only appears once `ak_sections` / `ak_inline_sections` is set on that environment's page.

That cuts both ways, and the second direction bites:

- **Good:** a section can be shipped dark. The calculator's template, CSS and JS have been
  on production since 2026-08-03 and render nothing, because no page lists them. Excluding
  a work-in-progress section from a release costs one omitted line, not a branch.
- **Dangerous:** production carried the LEGACY numeric `ak_native_sections = 1` and no
  `ak_sections`. Syncing the current theme alone would have made
  `ak_native_section_count()` still strip the Divi hero via the legacy fallback while
  `ak_native_section_slugs()` returned empty and rendered nothing — **a homepage with no
  hero, on both languages.** Caught by reading production's meta before syncing, not by
  testing locally, where the meta has always been current.

**Therefore: write the meta FIRST, then sync the files.** The old code ignores meta keys it
does not know, so the pre-write is a no-op until the sync lands and the flip is atomic.
See *Deploy commands*.

### ⚠️ …EXCEPT WHEN THE CODE IS ALREADY THERE — reverse the order (2026-08-06)

"Meta first" rests on one premise: **the live code does not know the key yet.** Once a
section has been shipped dark, that premise is gone — production already reads
`ak_inline_sections`, so seeding the meta activates the section *using whatever theme
version is currently on the server*.

At the calculator go-live that would have put the **2026-08-03 build** in front of
visitors for the minute between seeding and extracting the tarball — the old design, live,
with no warning. So the order was **files → meta → cache**, and the flip stayed atomic
because the section was dark until the very last step.

**Rule:** meta first for a section production has never seen; **files first for a section
already shipped dark.** Check which case you are in by grepping the live HTML for the
section's own class before you decide.

---

## Pattern

`pattern: local-bitbucket-prod`

Full pattern detail:
`{norml-claude-skills}/.claude/skills/dev-ci-cd/references/patterns/local-bitbucket-prod.md`

**Why this pattern was picked:** confirmed by Petr on 2026-07-27. It is the catalog
default and the right target shape for this project — a low-change-rate Divi site with a
~40-line child theme, one occasional developer, and no staging worth the overhead.

**Why it is a target rather than a description:** the pattern assumes local dev + a
Bitbucket repo + rsync to prod. Local dev exists (OpenServer at `kalynyuk.loc`); the repo
does not. See STATUS above.

⚠️ **Pattern-fit caveat worth re-reading before the first real deploy.** The catalog says
*don't* use `local-bitbucket-prod` for "a client-facing site that drives revenue" —
`local-bitbucket-staging-prod` is the prescribed choice there. This is a lead-generating
site for a financial-services business, so if the change rate rises or a second developer
joins, migrate. Record the migration under *Migration history* below.

---

## Environments

### Production

- **URL:** `https://www.kalynyuk.com`
- **Server:** SSH alias **`anna-kalynyuk`** (details in `servers/admtools-anna-kalynyuk.json`)
- **Site path:** `/home/nu538012/kalynyuk.com/www`
- **Theme path:** `/home/nu538012/kalynyuk.com/www/wp-content/themes/anna-kalynyuk---norml-studio-theme`
- **Deploy method:** **tar + scp + extract**, NOT rsync — `rsync` exists on the server (`/usr/bin/rsync`) but **not on this Windows workstation**, so the pattern's `rsync -av --delete` is unrunnable from here. See *Deploy commands*.
- **WP-CLI PHP:** `php8.2 /usr/local/bin/wp` — bare `wp` fatals, its `env php` shebang resolves to the host default **5.6.40**
- **Verified reachable:** yes — deployed and verified 2026-08-03.
- **No page cache.** WP Rocket and Imagify are **local-only**; production's active plugins do not include either. Only Divi's own `wp-content/et-cache/` needs clearing. Do not carry local cache-purge assumptions over.
- **Verified reachable:** yes, over HTTPS — `GET /` returned 200 (~1.01 MB) on 2026-07-27. Web-reachable ≠ shell-reachable.

### Staging

- **URL:** **none**
- **Server:** n/a
- **Site path:** n/a
- **Deploy method:** n/a
- **DB refresh frequency:** n/a

### Local

- **Path:** `f:\localsites\kalynyuk.loc` (full WordPress install, not just the theme)
- **URL:** `http://kalynyuk.loc` — note Really Simple SSL forces HTTPS, so plain HTTP fails and the local certificate is untrusted. Expected; do not "fix" by disabling the plugin.
- **Stack:** OpenServer — Apache · PHP 8.1.1 · MySQL 5.7.33
- **Setup steps:** none — there is no repo to clone, no `composer install`, no `npm install`
- **Run Vite:** **n/a — this project has no build step.** No `package.json`, no `composer.json`, no bundler. The only compile is CodeKit's server-side SCSS compiler, which runs inside WordPress on save.
- **`.env` config:** n/a
- **WP-CLI:** not on PATH. See `./wp-config.json`.

---

## Database strategy

`database: single-env`

Full strategy detail:
`{norml-claude-skills}/.claude/skills/dev-ci-cd/references/database/single-env.md`

**Project-specific notes — this matters more here than on a normal project:**

- **Production is the only source of truth for content**, and for this site "content"
  includes the entire visual design. All page layout is Divi shortcodes in `wp_posts`;
  all custom CSS is a `custom-code` post plus its compiled files in
  `wp-content/custom_codes/`; all global design tokens are the `et_divi` option.
- **A theme-only deploy carries almost nothing.** The child theme is 3 files / ~40 lines.
  Anything that changes how the site looks changes the **database**, not the repo.
  Do not assume "deployed the theme" means "deployed the change."
- **Never push a local database to production.** The local copy is a clone of unknown
  age; production has since accumulated Gravity Forms entries, Review Wall Google syncs
  (68 reviews), Simple History rows (2,999), and Rank Math analytics.
- **The database is 194 MB, of which ~153 MB is post revisions** (516 on the homepage
  alone). Any `wp db export` / import moves ~150× more data than the site contains.
  Budget for it, and see `docs/05-issues.md`.
- **`wp search-replace` is high-risk here.** Divi shortcodes store serialised data and
  URLs inside `post_content` attributes. Always `--dry-run` first, never omit
  `--skip-columns=guid`, and take a fresh backup immediately before.

---

## Backup strategy

`backup: TBD` — no `backup` block exists in
`~/.config/norml-studio/credentials/projects/anna-kalynyuk.json` (the file itself is
missing).

Full strategy detail: `{norml-claude-skills}/.claude/skills/dev-ci-cd/references/backup/{provider}.md`
— resolve once the provider is known.

**What is known to exist in-site:** UpdraftPlus 2.26.6.0 is active with a **daily file
backup** (`updraft_backup`) and a **daily database backup** (`updraft_backup_database`),
writing to `wp-content/updraft/`. A Google Drive access token is present in options, so
remote storage was at least configured.

⚠️ **Unverified.** Whether production backups actually complete, how many are retained,
and whether the Google Drive destination still authenticates were all impossible to check
without shell access. **Before the first deploy, confirm a real, recent, restorable
backup exists.** UpdraftPlus being installed is not evidence that it works — and on this
project it is currently the *only* safety net, because there is no git history to revert to.

Also note: because the design lives in the database, **a file-only backup is not a
rollback.** Any pre-deploy backup must include the database.

---

## Permissions matrix

Defaults from `local-bitbucket-prod`, with project-specific overrides applied.

| Action | Claude autonomous | Human go-ahead |
|---|---|---|
| Edit source files locally | ✅ | — |
| Edit CodeKit SCSS locally (`wp-content/custom_codes/*.scss`) | ✅ | — |
| Build step | n/a — no build step exists | — |
| Rsync to staging | n/a — no staging | — |
| **Rsync to production** | — | ✅ |
| `git add` + commit + push | ✅ *(once a repo exists)* | — |
| Open PR | ✅ | — |
| Merge PR | — | ✅ |
| Tag release | — | ✅ |
| Trigger CI deploy | n/a — no CI | — |
| Content edits via REST on staging | n/a | — |
| **Content edits via REST on production** | — | ✅ **(override — see notes)** |
| **Any Divi Builder or Divi Theme Builder edit on production** | — | ✅ **(override)** |
| **Any CodeKit custom-code save on production** | — | ✅ **(override)** |
| Plugin install / activate / deactivate on production | — | ✅ |
| Plugin *update* on production | — | ✅ |
| Theme switch | — | ✅ |
| `wp db` destructive commands on production | — | ✅ |
| `wp db export` (read-only) on production | ✅ | — |
| `wp search-replace` on production | — | ✅ (always `--dry-run` first) |
| `wp eval` / arbitrary PHP on production | — | ✅ |
| Read-only WP-CLI on production (`wp option get`, `wp post list`, `wp plugin list`) | ✅ | — |
| Trigger pre-deploy backup | ✅ (**REQUIRED**) | — |
| Purge WP Rocket cache on production | ✅ (after deploy) | — |
| Purge Divi `et-cache` on production | ✅ (after deploy) | — |
| Clear the WP Rocket RUCSS store on production | ✅ (after any CSS change) | — |

### Override notes

The pattern's default allows autonomous REST content edits on production. **That default
is revoked for this project**, for three reasons specific to it:

1. **Content and design are the same object.** A REST write to a page's `content` field
   rewrites a Divi shortcode blob — on the homepage, ~200 KB of it. There is no
   separation between "editing copy" and "editing the layout," so no content write is
   low-blast-radius.
2. **There is no git history to revert to** and no staging to preview on. The only
   rollback is an UpdraftPlus restore, which is unverified.
3. **Three known duplication traps** mean a single logical change often has to be applied
   in two or three places to stay consistent (header ×3, calculator CSS ×2, homepage
   uk+pt ×2). A partial autonomous edit leaves the site internally inconsistent. See
   `docs/05-issues.md`.

Read-only WP-CLI on production stays autonomous — inspection is safe and is what most
sessions actually need.

---

## Deploy commands

### Deploy to staging

```
n/a — no staging
```

### Deploy to production

**Runnable as of 2026-08-03.** This is the sequence that was actually used, not a template.

⚠️ **`rsync` is NOT available on this Windows workstation** (the server has it; we don't).
Piping `tar | ssh` in one command is also blocked by the Claude Code permission classifier.
So the working shape is three explicit steps: build a tarball, `scp` it, extract remotely.
Note this does **not** delete removed files — check for renames by hand, or extract into an
empty directory and swap.

```bash
# 0. PRE-DEPLOY — back up BOTH, they are separate things
ssh anna-kalynyuk 'cd ~/kalynyuk.com/www/wp-content/themes && tar czf ~/backup-theme-$(date +%Y%m%d).tgz anna-kalynyuk---norml-studio-theme'
ssh anna-kalynyuk 'cd ~/kalynyuk.com/www && php8.2 /usr/local/bin/wp db query "SELECT * FROM wp_postmeta WHERE meta_key LIKE \"ak\_%\"" --skip-column-names > ~/backup-akmeta-$(date +%Y%m%d).tsv'

# 1. META FIRST — see the STATUS block. Old code ignores keys it does not know, so this is
#    a no-op until step 2 lands, and the switch is then atomic. Upload a PHP file and use
#    eval-file; never inline PHP through the shell (quoting mangles $ and Cyrillic).
scp seed.php anna-kalynyuk:~/seed.php
ssh anna-kalynyuk 'cd ~/kalynyuk.com/www && php8.2 /usr/local/bin/wp eval-file ~/seed.php'

# 2. Ship the theme. `npm run build` FIRST — dist/ is committed and production has no build step.
npm run build
cd f:/localsites/kalynyuk.loc/wp-content/themes/anna-kalynyuk---norml-studio-theme
tar czf /f/tmp/theme-deploy.tgz \
  --exclude=node_modules --exclude=.git --exclude=.claude \
  --exclude=package.json --exclude=package-lock.json --exclude=vite.config.js .
scp /f/tmp/theme-deploy.tgz anna-kalynyuk:~/theme-deploy.tgz
ssh anna-kalynyuk 'cd ~/kalynyuk.com/www/wp-content/themes/anna-kalynyuk---norml-studio-theme && tar xzf ~/theme-deploy.tgz'

# 2. If CSS changed, sync the CodeKit output too — it lives OUTSIDE the theme
rsync -av \
  f:/localsites/kalynyuk.loc/wp-content/custom_codes/ \
  {server}:{site_path}/wp-content/custom_codes/
#    …and re-save the custom code in wp-admin → Custom Codes so the DB post matches
#    the files. The `custom-code` post is the source of truth, not the files.

# 3. Post-deploy — Divi's own cache only; there is no page-cache plugin on production
ssh anna-kalynyuk 'cd ~/kalynyuk.com/www && rm -rf wp-content/et-cache/* && php8.2 /usr/local/bin/wp cache flush'

# 4. Clean up the uploaded scratch files; KEEP the backups
ssh anna-kalynyuk 'rm -f ~/theme-deploy.tgz ~/seed.php'
```

**⚠️ NEVER hardcode an attachment ID into a production seed script from a value read
earlier in the session.** This bit on 2026-08-03: the seed carried `ak_about_portrait = 101`,
read from local before the photo was swapped for a new upload (2570). Production shipped
the old 232×232 image while local showed the new 332×332 one, and it passed every
structural assertion — only a local-vs-production computed-style diff caught it. Re-read
the live local value at deploy time, and **diff every `ak_*` key between the two
environments after seeding**, not just the ones you think you changed.

**Rollback.** Restore `~/backup-theme-{date}.tgz` over the theme directory, and clear the
meta keys added in step 1 (`ak_sections`, `ak_inline_sections`, `ak_about_*`, `ak_cta_*`).
Nothing is destructive: `post_content` is never modified, so clearing `ak_inline_sections`
alone brings every Divi original straight back.

Notes on the shape of this deploy:

- **`--exclude '.claude'`** is mandatory. The AI context layer is local-only, and
  `.claude/wp-config.json` contains machine-specific absolute paths.
- **There is no `npm run build`, no `composer install`, and no Acorn.** Skip every
  `wp acorn …` command in the pattern's generic examples — this is not a Sage project.
- **`wp-content/custom_codes/` is outside the theme** and holds the site's only
  stylesheet. A theme-only rsync silently ships no CSS. See
  `docs/03-theme-architecture.md` → The CSS layer.
- **Most changes on this project are not deployable at all** — they are Divi Builder edits
  made in wp-admin on production. For those, "deploy" means "edit live, with a backup
  taken first and a human watching." Screenshot before and after.

### Promote staging → production

```
n/a — no staging
```

---

## Pre-deploy hooks

Run BEFORE any deploy or any production edit:

1. **Verify a recent, restorable backup exists — including the database** (≤ 24h). Do not
   accept "UpdraftPlus is installed" as verification. This is the only rollback path.
2. **Screenshot the affected production page(s)** with Playwright before touching
   anything. There is no staging, so this is the only before/after comparison available.
3. Confirm the change doesn't need to be applied in more than one place — check
   `docs/05-issues.md` for the header ×3 / calculator CSS ×2 / homepage uk+pt ×2 traps.
4. If CSS is involved, confirm whether the change belongs in **CodeKit post 1907** rather
   than in a Divi module or the child theme.
5. If a design value is involved, confirm it against **`./design.md`** — it is binding.
6. No build step to verify (there isn't one). No linters configured.

## Post-deploy hooks

Run AFTER any deploy or production edit:

1. **Purge WP Rocket cache** — `wp rocket clean --confirm` (or wp-admin → WP Rocket → Clear Cache)
2. **Clear the WP Rocket RUCSS store** if any CSS changed — Remove Unused CSS is **on**, and it will strip new rules until regenerated. This is the #1 cause of "my CSS didn't apply" on this site.
3. **Purge Divi cache** — delete `wp-content/et-cache/` and `wp-content/cache/et/` contents
4. `wp cache flush && wp rewrite flush`
5. **Smoke test** — `qa-smoke-test https://www.kalynyuk.com`
6. **Screenshot and compare** against the pre-deploy capture
7. If the change touched the header, verify on **all three** contexts: a page, a single blog post, and a category archive — they use three different header layouts
8. If the change touched the homepage, verify the **Portuguese homepage** (page 2483) too
9. Verify the footer Gravity Form (form 1) still submits — it is embedded in the footer layout on every page

---

## Known gotchas

Project-specific things that will bite:

- **No git, no history, no diff, no rollback.** The single most important fact about
  deploying to this project. Creating the repo is prerequisite #1.
- **The design lives in the database, not in files.** A theme deploy carries ~40 lines of
  PHP. Everything visual is Divi shortcodes + the `et_divi` option + a `custom-code` post.
- **`wp-content/custom_codes/` is outside the theme.** Any deploy or migration that
  scopes to the theme folder loses the site's only stylesheet.
- **CodeKit's source of truth is the database post, not the file.** Editing
  `1907-scss-desktop.scss` on disk does nothing until you re-save the code in
  `wp-admin → Custom Codes`, which recompiles `1907-scss-output.css`. Never hand-edit
  `*-output.*`.
- **Remove Unused CSS is on.** New CSS will appear not to work until the RUCSS store is
  cleared. Always step 2 of post-deploy.
- **`defer_all_js` + a JS preloader.** If the site ever hangs behind the loading overlay
  after a deploy, a JS error is the cause — the preloader can only remove itself once its
  own (deferred) JS runs.
- **The header exists in triplicate** (Divi layouts 284, 2213, 2214), all three
  confusingly titled `All Category Pages`. A header change applied once will be
  inconsistent. Work by ID.
- **The homepage exists twice** (uk page 11, pt page 2483, both ~200 KB). Same problem.
- **Renaming the `Меню` menu breaks every header.** The child theme's `[vertical_menu]`
  shortcode matches the menu by its display name, `"Меню"`, and is used in all three
  header layouts.
- **Really Simple SSL forces HTTPS**, including locally. `http://kalynyuk.loc` will not
  serve, and the local cert is untrusted. Not a bug to fix.
- **Local PHP is 8.1.1 (EOL) and MySQL 5.7 (EOL).** Production's versions are unknown, so
  local is not a reliable reproduction environment. Verify production's PHP before
  assuming parity, and record it as `wp_cli_php` if the shell PHP differs from FPM.
- **WP-CLI is not on PATH on this machine.** It runs as a phar through OpenServer's PHP —
  see `./wp-config.json`. Use `wp eval-file` rather than inline `wp eval`; PowerShell
  mangles `$` and quoting when passing PHP to a native executable.
- **194 MB database, ~153 MB of it revisions.** Every export, import, and backup pays for
  it. `WP_POST_REVISIONS` is uncapped.
- **Add a `.gitattributes` with `* text=auto eol=lf` when the repo is created.** This is a
  Windows machine; without it, the first clone elsewhere produces a 100% whitespace diff.
- **Do not use the `norml-git` skill for this repo** — it is scoped to the shared
  `norml-claude-skills` content repo. Use plain git with Conventional Commits.

---

## Migration history

- **2026-07-27** — Initial `pattern:` declared as `local-bitbucket-prod` during the
  `dev-wp-init-project` Mode A run, confirmed by Petr. Declared as a **target**: the
  Bitbucket repo and the credentials JSON both still need to be created, and deploys are
  blocked until they are. Backup provider recorded as `TBD`.

---

## Prerequisites checklist

Close these to lift the deploy block:

- [ ] Create the Bitbucket repo for the child theme and push an initial commit
- [ ] Add `.gitignore` (exclude `.claude/wp-config.json`) and `.gitattributes` (`* text=auto eol=lf`)
- [ ] Create `~/.config/norml-studio/credentials/projects/anna-kalynyuk.json` — production URL, server slug, site path, WP admin URL, credential-store entry names
- [ ] Create / confirm `~/.config/norml-studio/credentials/servers/{server-slug}.json` — SSH user, host, port, key path
- [ ] Add the `backup` block to the project JSON and pick the matching `backup/{provider}.md` strategy
- [ ] Add the `ci_cd` block to the project JSON recording `pattern: local-bitbucket-prod`
- [ ] Verify production PHP version and record `wp_cli_php` if shell PHP ≠ FPM PHP
- [ ] Verify a real, recent, restorable UpdraftPlus backup **including the database**
- [ ] Fill the `TBD` rows in *Environments* above from the credentials JSON
- [ ] Re-run `dev-wp-init-project` Stages 1–2 against production so `docs/01` and `docs/02` describe the live host rather than local OpenServer

---

## Cross-references

- Pattern: `{norml-claude-skills}/.claude/skills/dev-ci-cd/references/patterns/local-bitbucket-prod.md`
- DB strategy: `{norml-claude-skills}/.claude/skills/dev-ci-cd/references/database/single-env.md`
- Backup strategy: `{norml-claude-skills}/.claude/skills/dev-ci-cd/references/backup/{provider}.md` — provider TBD
- Credentials JSON: `~/.config/norml-studio/credentials/projects/anna-kalynyuk.json` — **missing**
- Server JSON: `~/.config/norml-studio/credentials/servers/{server-slug}.json` — **unresolved**
- Design contract: `./design.md`
- WP-CLI config: `./wp-config.json`
- Architecture docs: `./docs/01-infrastructure.md` … `./docs/05-issues.md`
