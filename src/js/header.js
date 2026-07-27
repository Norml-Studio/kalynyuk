/**
 * Header behaviour — desktop dropdowns, the language switcher, and the mobile
 * drill-down drawer.
 *
 * Implements vibe-frontend-standards/references/header-standard.md §3 and §4.
 * Dependency-free on purpose: Divi still loads jQuery on every page during the
 * migration, but new code must not use it (dev-wp-developer, "No jQuery").
 *
 * No `transition: all` anywhere — all transitions are declared per-property in
 * SCSS, and this file only toggles classes and attributes.
 */

const REDUCED = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;

/* ─── Generic click-to-toggle popover ───────────────────────────────────────
 * header-standard §3: click, not hover. One open at a time. Closes on the
 * trigger again, outside click, Escape, or opening another.
 */
function initPopovers() {
  const groups = [
    { trigger: '[data-ak-dropdown-trigger]', panel: '[data-ak-dropdown-panel]' },
    { trigger: '[data-ak-lang-trigger]', panel: '[data-ak-lang-list]' },
  ];

  const pairs = [];

  groups.forEach(({ trigger, panel }) => {
    document.querySelectorAll(trigger).forEach((btn) => {
      const id = btn.getAttribute('aria-controls');
      const pnl = id ? document.getElementById(id) : btn.parentElement.querySelector(panel);
      if (pnl) pairs.push({ btn, pnl });
    });
  });

  if (!pairs.length) return;

  const closeAll = (except) => {
    pairs.forEach(({ btn, pnl }) => {
      if (btn === except) return;
      btn.setAttribute('aria-expanded', 'false');
      pnl.hidden = true;
    });
  };

  pairs.forEach(({ btn, pnl }) => {
    btn.addEventListener('click', (e) => {
      e.stopPropagation();
      const open = btn.getAttribute('aria-expanded') === 'true';
      closeAll(open ? null : btn);
      btn.setAttribute('aria-expanded', open ? 'false' : 'true');
      pnl.hidden = open;
    });
  });

  // Outside click.
  document.addEventListener('click', (e) => {
    const inside = pairs.some(({ btn, pnl }) => btn.contains(e.target) || pnl.contains(e.target));
    if (!inside) closeAll();
  });

  // Escape returns focus to the open trigger — §5.
  document.addEventListener('keydown', (e) => {
    if (e.key !== 'Escape') return;
    const open = pairs.find(({ btn }) => btn.getAttribute('aria-expanded') === 'true');
    if (!open) return;
    closeAll();
    open.btn.focus();
  });
}

/* ─── Mobile drill-down drawer ──────────────────────────────────────────────
 * header-standard §4. Root panel + one sub-panel per parent. Back returns to
 * root. Closing resets to root. Body scroll is locked while open. Focus moves
 * into the drawer on open and back to the toggle on close.
 */
function initDrawer() {
  const drawer = document.querySelector('[data-ak-drawer]');
  const toggle = document.querySelector('[data-ak-drawer-toggle]');
  if (!drawer || !toggle) return;

  const scrim = drawer.querySelector('[data-ak-drawer-scrim]');
  const dialog = drawer.querySelector('.nav-drawer__dialog');
  const panels = [...drawer.querySelectorAll('[data-ak-drawer-panel]')];
  const root = drawer.querySelector('[data-ak-drawer-panel="root"]');

  const showPanel = (key) => {
    panels.forEach((p) => p.classList.toggle('is-active', p.dataset.akDrawerPanel === key));
    drawer.querySelectorAll('[data-ak-drawer-drill]').forEach((b) => {
      b.setAttribute('aria-expanded', String(b.dataset.akDrawerDrill === key));
    });
  };

  const firstFocusable = () => {
    const active = panels.find((p) => p.classList.contains('is-active')) || root;
    return active.querySelector('a, button') || dialog;
  };

  const open = () => {
    drawer.hidden = false;
    // Force a frame so the transition runs from the off-screen state.
    requestAnimationFrame(() => drawer.classList.add('is-open'));
    toggle.setAttribute('aria-expanded', 'true');
    document.body.style.overflow = 'hidden';
    showPanel('root');
    firstFocusable().focus({ preventScroll: true });
  };

  const close = () => {
    drawer.classList.remove('is-open');
    toggle.setAttribute('aria-expanded', 'false');
    document.body.style.overflow = '';

    const finish = () => {
      drawer.hidden = true;
      showPanel('root'); // reset to root — §4
    };

    if (REDUCED()) {
      finish();
    } else {
      // Wait for the panel transition rather than a magic number.
      dialog.addEventListener('transitionend', finish, { once: true });
      // Safety net if transitionend never fires (e.g. display:none race).
      window.setTimeout(finish, 500);
    }

    toggle.focus({ preventScroll: true });
  };

  toggle.addEventListener('click', () => {
    if (toggle.getAttribute('aria-expanded') === 'true') close();
    else open();
  });

  if (scrim) scrim.addEventListener('click', close);

  drawer.querySelectorAll('[data-ak-drawer-drill]').forEach((btn) => {
    btn.addEventListener('click', () => {
      showPanel(btn.dataset.akDrawerDrill);
      const panel = drawer.querySelector(`[data-ak-drawer-panel="${btn.dataset.akDrawerDrill}"]`);
      const back = panel && panel.querySelector('[data-ak-drawer-back]');
      if (back) back.focus({ preventScroll: true });
    });
  });

  drawer.querySelectorAll('[data-ak-drawer-back]').forEach((btn) => {
    btn.addEventListener('click', () => {
      const parentKey = btn.closest('[data-ak-drawer-panel]').dataset.akDrawerPanel;
      showPanel('root');
      const trigger = drawer.querySelector(`[data-ak-drawer-drill="${parentKey}"]`);
      if (trigger) trigger.focus({ preventScroll: true });
    });
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && toggle.getAttribute('aria-expanded') === 'true') close();
  });

  // Leaving mobile width with the drawer open would strand a hidden overlay and a
  // locked body.
  window.matchMedia('(min-width: 1025px)').addEventListener('change', (e) => {
    if (e.matches && toggle.getAttribute('aria-expanded') === 'true') close();
  });
}

export function initHeader() {
  initPopovers();
  initDrawer();
}
