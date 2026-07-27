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
