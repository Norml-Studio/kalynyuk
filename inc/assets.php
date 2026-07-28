<?php
/**
 * Asset enqueuing.
 *
 * @package kalynyuk
 */

defined( 'ABSPATH' ) || exit;

/**
 * Cache-busting version for a built asset.
 *
 * The build emits stable, unhashed filenames (see vite.config.js for why), so the
 * cache buster is the file's mtime. Falls back to AK_VERSION if the file is
 * missing, so a stale query string is never silently correct.
 *
 * @param string $relative_path Path relative to the theme root, e.g. 'dist/css/main.css'.
 * @return string
 */
function ak_asset_version( $relative_path ) {
	$absolute = AK_DIR . '/' . ltrim( $relative_path, '/' );

	return file_exists( $absolute ) ? (string) filemtime( $absolute ) : AK_VERSION;
}

/**
 * Preconnect to the Google Fonts hosts.
 *
 * Two hosts are needed: fonts.googleapis.com serves the CSS, fonts.gstatic.com
 * serves the font files. The second needs `crossorigin` because fonts are fetched
 * in CORS mode.
 */
function ak_font_preconnect() {
	echo '<link rel="preconnect" href="https://fonts.googleapis.com">' . "\n";
	echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
}
add_action( 'wp_head', 'ak_font_preconnect', 1 );

/**
 * Enqueue the theme's built CSS and JS.
 *
 * Two things here are deliberate and easy to get wrong later:
 *
 * 1. We do NOT enqueue style.css — not the parent's, not the child's.
 *    Divi's et_divi_enqueue_stylesheet() (Divi/functions.php:381-393) decides
 *    what to load by regex-testing whether the child already enqueued the parent
 *    stylesheet. Because WordPress loads the CHILD functions.php first, the old
 *    code here won that race, pushed Divi down its "child already did it" branch,
 *    and ended up with the child style.css requested twice. Enqueuing nothing
 *    lets Divi take its intended branch and load both stylesheets itself, once.
 *
 * 2. Priority 20, not the default 10. Divi registers `divi-style` inside its own
 *    priority-10 callback, which runs AFTER ours (child functions.php loads
 *    first). At priority 20 the handle exists, so we can declare a real
 *    dependency and guarantee our CSS cascades after Divi's.
 *
 * The dependency is conditional so this keeps working in phase 4, when Divi is
 * gone and `divi-style` no longer exists.
 */
function ak_enqueue_assets() {
	$css = 'dist/css/main.css';
	$js  = 'dist/js/main.js';

	/*
	 * Nunito Sans, enqueued rather than @import-ed.
	 *
	 * It arrived in _base.scss as `@import url(...)` when the CodeKit CSS was
	 * migrated into the theme. That was VALID — Vite hoists it to position 0 of the
	 * bundle, so the browser did honour it — but it serialises two round trips: the
	 * font CSS cannot start downloading until main.css has been fetched AND parsed.
	 * A <link> starts both in parallel, and lets us preconnect.
	 *
	 * Weights: 400 / 600 / 700 only. The UI Kit contains no Medium(500) and no
	 * Light(300) text style (design.md v2.0.0), and the migrated @import was still
	 * requesting 300 — a weight nothing on the site uses.
	 *
	 * This is now the site's ONLY font request: after the CodeKit plugin was
	 * deactivated there is no `fonts.googleapis.com` reference left in the HTML,
	 * which also closes the "Nunito Sans loaded twice" issue in docs/05-issues.md.
	 */
	wp_enqueue_style(
		'ak-fonts',
		'https://fonts.googleapis.com/css2?family=Nunito+Sans:opsz,wght@6..12,400;6..12,600;6..12,700&display=swap',
		array(),
		null // Google versions the URL itself; a ?ver= would break their cache.
	);

	$deps = array();
	if ( wp_style_is( 'divi-style', 'enqueued' ) || wp_style_is( 'divi-style', 'registered' ) ) {
		$deps[] = 'divi-style';
	}

	if ( file_exists( AK_DIR . '/' . $css ) ) {
		wp_enqueue_style( 'ak-main', AK_URI . '/' . $css, $deps, ak_asset_version( $css ) );
	}

	if ( file_exists( AK_DIR . '/' . $js ) ) {
		wp_enqueue_script( 'ak-main', AK_URI . '/' . $js, array(), ak_asset_version( $js ), true );
	}
}
add_action( 'wp_enqueue_scripts', 'ak_enqueue_assets', 20 );
