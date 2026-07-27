<?php
/**
 * Anna Kalynyuk — Norml Studio child theme.
 *
 * Loader only. Real code lives in inc/ — see .claude/docs/03-theme-architecture.md.
 *
 * Context for anyone (or any AI) reading this file: almost none of this site is
 * PHP. Page layout is Divi shortcodes in the database, and the site's CSS is a
 * `custom-code` post compiled by CodeKit. The de-Divi migration replaces that
 * piece by piece; this file is where the replacement is wired up.
 *
 * @package kalynyuk
 */

defined( 'ABSPATH' ) || exit;

/**
 * Theme version — used to bust asset caches for files we do not fingerprint.
 * Keep in sync with style.css and package.json.
 */
define( 'AK_VERSION', '1.1.0' );

/** Absolute path to the child theme, no trailing slash. */
define( 'AK_DIR', get_stylesheet_directory() );

/** URI of the child theme, no trailing slash. */
define( 'AK_URI', get_stylesheet_directory_uri() );

require_once AK_DIR . '/inc/setup.php';
require_once AK_DIR . '/inc/helpers.php';
require_once AK_DIR . '/inc/assets.php';
require_once AK_DIR . '/inc/post-types.php';
require_once AK_DIR . '/inc/shortcodes.php';
require_once AK_DIR . '/inc/integrations.php';
