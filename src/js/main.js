/**
 * main.js — theme entry point.
 *
 * Importing the stylesheet here is what makes Vite emit dist/css/main.css.
 *
 * Deliberately dependency-free. Divi still loads jQuery on every page during the
 * migration, but new code must not use it (dev-wp-developer: "No jQuery"). No
 * Alpine either — the header needs class and attribute toggles, not a reactivity
 * layer. Add one only when a component genuinely requires it.
 */
import '../scss/main.scss';
import { initHeader } from './header.js';
import { initArticle } from './article.js';
import { initCalculator } from './calculator.js';

const boot = () => {
  initHeader();
  initArticle();
  initCalculator();
  // Marker so a Playwright run can confirm the built bundle actually executed,
  // rather than inferring it from styles Divi might also be providing.
  document.documentElement.dataset.akTheme = 'ready';
};

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', boot, { once: true });
} else {
  boot();
}
