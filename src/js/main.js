/**
 * main.js — theme entry point.
 *
 * Importing the stylesheet here is what makes Vite emit dist/css/main.css.
 *
 * Deliberately dependency-free. Divi still loads jQuery on every page during the
 * migration, but new code must not use it (dev-wp-developer: "No jQuery"). No
 * Alpine either — nothing here needs a reactivity layer yet. Add one only when a
 * component genuinely requires it.
 */
import '../scss/main.scss';

// Marker so we can confirm from the console / Playwright that the built bundle
// actually loaded, rather than inferring it from styles that Divi might also
// be providing.
document.documentElement.dataset.akTheme = 'ready';
