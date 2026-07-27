# Changelog Protocol — Anna Kalynyuk

This project uses a **three-tier rolling changelog** that compresses itself
over time. The goal: never lose load-bearing information, never store junk.

## The three tiers

| File | Scope | Written by |
|------|-------|------------|
| `daily.md` | Today's raw entries | Current session |
| `weekly.md` | Compressed summary of the current ISO week | Rollover from daily |
| `changelog.md` | Long-term history (one entry per completed week) | Rollover from weekly |

## Rollover protocol

**There is no scheduler.** Rollover happens lazily, triggered whenever
`dev-changelog` (or any skill) is about to write to `daily.md`.

### Before writing to `daily.md`:

1. **Read `daily.md`.** Look at the `## YYYY-MM-DD` header at the top.
2. **If that date is today (ISO `YYYY-MM-DD`):** append the new entries under
   today's section. Merge related items — don't create a second section for
   the same day.
3. **If that date is older than today:**
   - Compress all content currently in `daily.md` into a short summary (see
     "Compression rules" below).
   - Append that summary to `weekly.md` under the correct ISO week header
     (`## Week YYYY-Www` — ISO year + ISO week number).
   - **Empty `daily.md`**, keeping only the file header, and start fresh
     with today's entry.
4. **Before appending to `weekly.md`, check if the current ISO week has
   advanced** beyond what's already in `weekly.md`:
   - If `weekly.md` contains entries from a past ISO week, compress those
     past-week entries into one paragraph each and append them to
     `changelog.md` under a `## Week YYYY-Www` header.
   - **Empty `weekly.md`**, keeping only the file header, and start fresh
     with the current week.

### Compression rules

When compressing `daily.md` → `weekly.md` or `weekly.md` → `changelog.md`:

- **Blend related items.** Three entries about "fixed the calculator form" become
  one entry: "Rebuilt the mortgage calculator form (fields, validation, AJAX)."
- **Drop chatter.** Debugging steps, reverted changes, and intermediate
  states do not survive compression. Only the final outcome matters.
- **Never compress away:**
  - Decisions (marked `[DECISION]`)
  - Integrations (marked `[INTEGRATION]`)
  - Security fixes
  - Breaking changes
- **Past tense, one line per distinct change.** "Added X," "Fixed Y,"
  "Removed Z."
- **Keep the "why" for non-obvious changes.** A fix's reason is often more
  important than the fix itself.

### Decisions and integrations

When a session produces a decision or new integration, tag the entry in
`daily.md`:

```
- [DECISION] All new custom CSS goes through CodeKit's "Public Side SCSS"
  custom code, not the child theme style.css. Reason: matches the existing
  workflow and survives theme redeploys.
- [INTEGRATION] Review Wall now pulls Google reviews via its own API key
  stored in wp_options (review_wall_api_key).
```

These survive every level of compression intact. They also get mirrored
into `../docs/` by `dev-changelog` (into `02-application.md`,
`03-theme-architecture.md`, etc. as appropriate) so architecture docs stay
current without re-running the full scrape pipeline.

## File headers

Each file starts with a fixed header so the rollover logic can find it:

- `daily.md`: `# Daily Changelog — Anna Kalynyuk`
- `weekly.md`: `# Weekly Changelog — Anna Kalynyuk`
- `changelog.md`: `# Changelog — Anna Kalynyuk`

Below the header, entries are grouped under date/week sections.

## Who runs this

The `dev-changelog` skill in `norml-claude-skills` is the intended writer.
It reads this README and follows the rollover protocol. You can also invoke
the rollover logic manually — the rules are deterministic, so any Claude
session can follow them.
