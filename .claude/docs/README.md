# Architecture Docs — Anna Kalynyuk

This folder contains the technical architecture of this WordPress project,
produced by `dev-wp-init-project`'s scrape pipeline (in `norml-claude-skills`).

**These files are not written by hand.** The pipeline generates them through
a staged exhaustive scan so they stay consistent across projects and
comprehensive enough that an AI reading them can work in the codebase
without guessing.

## Files

| File | Contents | Stage |
|------|----------|-------|
| `01-infrastructure.md` | WordPress core, PHP, database, caching, cron, wp-config constants | Stage 1 |
| `02-application.md` | Themes, plugins (grouped by function), page builder, plugin interaction map | Stage 2 |
| `03-theme-architecture.md` | Every file in the theme, the Divi Theme Builder layer, shortcodes, CPTs, the CodeKit CSS layer | Stage 3 |
| `04-content-structure.md` | Page map (template + builder per page), menus, Polylang wiring, forms, revisions | Stage 4 |
| `05-issues.md` | Problems and inconsistencies found | Stage 5 |

**Plus one contract, one level up:**

| File | Contents | Stage |
|------|----------|-------|
| `../design.md` | The binding design contract — palette, type scale, spacing/shape, surfaces, layout, components, imagery, motion, do/don't, agent prompt guide, CSS export | Stage 6 |

## Scope of this snapshot

Generated **2026-07-27** against the **local** install at `f:\localsites\kalynyuk.loc`
(WordPress 7.0.2, PHP 8.1.1, MySQL 5.7.33 under OpenServer).

- **Stages 1–5** were scraped locally via WP-CLI. Production was **not** reachable — no
  `~/.config/norml-studio/credentials/projects/anna-kalynyuk.json` exists. Findings marked
  **[LOCAL]** in `05-issues.md` describe the OpenServer stack, not the live host.
- **Stage 6** (`../design.md`) was measured against **production**
  (`https://www.kalynyuk.com/`) with Playwright at 1440×1000 — homepage only.

Re-run Stages 1–2 once production credentials exist, and re-run Stage 6 at mobile widths
and against `/calc/` before any responsive or calculator work.

## Refresh

To rescan a single stage: ask Claude *"rescan theme architecture"* (etc.).
To rescan everything: *"rescan everything"* or just *"scrape this WordPress site"*.

| Say this | Runs |
|---|---|
| *"refresh infrastructure"* | Stage 1 |
| *"re-run the plugin scan"* | Stage 2 |
| *"rescan theme architecture"* | Stage 3 |
| *"redo content map"* | Stage 4 |
| *"redo issues"* | Stage 5 |
| *"rescan design"* | Stage 6 → `../design.md` |

Single-stage rescans are the right tool for ongoing maintenance — e.g. after adding a
Divi Theme Builder template, re-run Stage 3 and Stage 4.

The skill is `dev-wp-init-project`. The pipeline detail lives in
`{norml-claude-skills}/.claude/skills/dev-wp-init-project/references/scrape-pipeline.md`.

## Start here

If you are new to this project, read in this order:

1. `../CLAUDE.md` — the 60-second orientation
2. **`03-theme-architecture.md`** — because the answer to "where is the code" is surprising
3. `04-content-structure.md` — because the answer to "where is the page" is also surprising
4. `../design.md` — before writing any markup or CSS
5. `05-issues.md` — before promising anything

## Why these files exist

Architecture docs are the AI / dev onboarding shortcut. Without them, every
session re-discovers the codebase from scratch and the work drifts. With
them, a new engineer (or Claude) reads `03-theme-architecture.md` once and
knows that this is a Divi site with a 40-line child theme, that CSS lives in
the database, and that the header exists in triplicate — instead of spending
an hour looking for `header.php`.

Do **not** edit these files by hand for routine tweaks. If the pipeline's
output is wrong, re-run the relevant stage — don't patch it manually,
because the next scrape will overwrite your patch.

The `dev-changelog` skill is allowed to append `[DECISION]` and
`[INTEGRATION]` items into these files when sessions produce something
architecturally load-bearing (a new plugin → `02-application.md`, a new CPT
→ `03-theme-architecture.md`). It dates the addition at the top of the
section so the scrape and the additions don't get confused.
