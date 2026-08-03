/**
 * trust.js — a FALLBACK ONLY. The accordion is native <details name="…">.
 *
 * ⚠️ READ THIS BEFORE ADDING ANYTHING HERE. Grouping <details> by `name` makes them
 * mutually exclusive natively — the browser closes the open one when another is opened,
 * exactly like radio buttons. Keyboard operation, ARIA semantics, and Ctrl+F opening a
 * collapsed answer all come with it. None of that should be reimplemented.
 *
 * This module exists for browsers that render <details> but ignore `name` (Chrome < 120,
 * Safari < 17.2, Firefox < 130). There, every item can be open at once — which still
 * WORKS, it just is not the single-open behaviour that was asked for. So the fix is
 * feature-detected and does nothing on a current browser.
 *
 * Deliberately NOT animated: design.md §9 budgets two motion moments and both are spent.
 */

export function initTrust() {
  // The property only exists on HTMLDetailsElement where the grouping is implemented, so
  // this is a true capability check rather than a browser sniff.
  if ('name' in document.createElement('details')) return;

  const groups = new Map();

  document.querySelectorAll('details[name]').forEach((el) => {
    const key = el.getAttribute('name');
    if (!groups.has(key)) groups.set(key, []);
    groups.get(key).push(el);
  });

  groups.forEach((items) => {
    items.forEach((el) => {
      el.addEventListener('toggle', () => {
        // Only react to OPENING. Reacting to close as well would fight the user when they
        // collapse the last open item.
        if (!el.open) return;

        items.forEach((other) => {
          if (other !== el) other.open = false;
        });
      });
    });
  });
}
