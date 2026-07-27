# Project-specific skills — Anna Kalynyuk

This folder holds skills that only make sense for this project. Starts empty.

## When to add a skill here

Add a skill here when the same multi-step workflow has been done 2+ times
in this project and is likely to be done again. Plausible candidates for
*this* project (don't pre-build them — wait until the pattern is real):

- "Add a new translated page" (Polylang: 4 languages, translation links, menu wiring)
- "Rebuild the mortgage calculator" (Gravity Forms #2/#3 + the Divi layout on page 566)
- "Publish a blog post to the Divi post template" (the `All Posts` Theme Builder template + Rank Math schema)

## When NOT to add a skill here

If the workflow is generic enough that other Norml WordPress projects could
use it, add it to `norml-claude-skills` instead (probably as a `dev-wp-*`
skill). This folder is for workflows that are **truly specific to this one
codebase**.

## Format

Each skill is a folder: `skills/{skill-name}/SKILL.md`. Follow the same
frontmatter + changelog format as skills in `norml-claude-skills`.
