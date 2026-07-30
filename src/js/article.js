/**
 * Article behaviour — the copy-link control in the share row.
 *
 * Progressive by construction: the control is a REAL <a href> to the article, so
 * with JS off, or on an insecure origin where `navigator.clipboard` does not exist
 * (plain-HTTP local dev is exactly that case), clicking it still does something
 * sensible instead of nothing. The handler only takes over when it can actually
 * deliver, and bails back to default navigation when the write fails.
 */

export function initArticle() {
  const buttons = document.querySelectorAll('[data-ak-copy]');

  if (!buttons.length || !navigator.clipboard) return;

  buttons.forEach((el) => {
    const original = el.textContent;
    const done = el.dataset.akCopyDone || original;
    let timer = 0;

    el.addEventListener('click', (e) => {
      e.preventDefault();

      navigator.clipboard.writeText(el.dataset.akCopy).then(
        () => {
          el.textContent = done;
          el.classList.add('is-copied');
          window.clearTimeout(timer);
          timer = window.setTimeout(() => {
            el.textContent = original;
            el.classList.remove('is-copied');
          }, 2000);
        },
        () => {
          // Permission denied or a non-focused document — fall back to the link's
          // own behaviour rather than leaving a dead control.
          window.location.href = el.href;
        }
      );
    });
  });
}
