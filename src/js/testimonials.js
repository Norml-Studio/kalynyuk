/**
 * testimonials.js — the two arrows beside the testimonials heading.
 *
 * ⚠️ THIS IS NOT A CAROUSEL, and the distinction is the whole design. The track is a
 * scroll-snapping overflow row: it already works with a trackpad, a touch swipe, and the
 * keyboard before a single line of this runs. There is no index to keep, no transform, no
 * autoplay and no library — the arrows just call scrollBy(), and if this file fails to
 * load the section degrades to a scrollable row rather than to a frozen slider.
 *
 * That is also why the arrows are progressive enhancement rather than the only way in:
 * they are pointer affordances for people who would not think to swipe a row on a desktop.
 */

export function initTestimonials() {
  document.querySelectorAll('[data-ak-testimonials]').forEach((track) => {
    const nav = track.closest('section')?.querySelectorAll('[data-ak-scroll]');

    if (!nav || !nav.length) return;

    // Scroll by exactly one card, gap included, measured from the DOM rather than from a
    // constant — the card width changes at every breakpoint and is a percentage, so any
    // number hardcoded here would be wrong at two of the three sizes.
    const step = () => {
      const card = track.firstElementChild;
      if (!card) return track.clientWidth;
      const gap = parseFloat(getComputedStyle(track).columnGap) || 0;
      return card.getBoundingClientRect().width + gap;
    };

    const sync = () => {
      // 1px of slack: scrollLeft is fractional on zoomed or hi-dpi displays, so an exact
      // comparison leaves the end arrow enabled forever on some machines.
      const atStart = track.scrollLeft <= 1;
      const atEnd = track.scrollLeft >= track.scrollWidth - track.clientWidth - 1;

      nav.forEach((btn) => {
        const isPrev = btn.dataset.akScroll === 'prev';
        btn.setAttribute('aria-disabled', String(isPrev ? atStart : atEnd));
      });
    };

    nav.forEach((btn) => {
      btn.addEventListener('click', () => {
        const dir = btn.dataset.akScroll === 'prev' ? -1 : 1;
        // `smooth` here is the browser's own scroll behaviour, not a new motion moment —
        // and it already respects prefers-reduced-motion without us asking.
        track.scrollBy({ left: dir * step(), behavior: 'smooth' });
      });
    });

    track.addEventListener('scroll', sync, { passive: true });
    window.addEventListener('resize', sync, { passive: true });
    sync();
  });
}
