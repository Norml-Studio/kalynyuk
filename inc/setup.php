<?php
/**
 * Theme setup.
 *
 * @package kalynyuk
 */

defined( 'ABSPATH' ) || exit;

/**
 * Load the child theme's translations.
 *
 * This was missing entirely before the migration: style.css declared a
 * `Text Domain` header but nothing ever called load_child_theme_textdomain(),
 * so the theme was not localisable at all — on a site running Polylang across
 * four languages. See .claude/docs/05-issues.md.
 *
 * The domain was shortened from `anna-kalynyuk---norml-studio-theme` to
 * `kalynyuk` at the same time. There were zero __() calls in the theme, so this
 * was the one free moment to do it; every future string uses the short domain.
 * The style.css header was updated to match.
 */
function ak_load_textdomain() {
	load_child_theme_textdomain( 'kalynyuk', AK_DIR . '/languages' );
}
add_action( 'after_setup_theme', 'ak_load_textdomain' );

/**
 * Theme supports.
 *
 * Deliberately sparse. The Divi parent already declares title-tag, html5,
 * post-thumbnails, automatic-feed-links and the editor styles; re-declaring them
 * is noise at best and a conflict at worst. Only genuinely additive things go
 * here, and each one gets a reason.
 *
 * Anything that needs to be REMOVED from Divi's set waits until phase 4, when the
 * theme stops being a child theme — pulling support out from under Divi's own
 * templates mid-migration breaks pages.
 */
function ak_setup() {
	// Lets the editor render the front-end type scale in the admin. Additive:
	// Divi enqueues its own editor style, this appends ours.
	add_editor_style( 'dist/css/main.css' );
}
add_action( 'after_setup_theme', 'ak_setup' );

/**
 * Drop Divi's hardcoded viewport meta.
 *
 * Divi/functions.php:536 emits:
 *   <meta name="viewport" content="width=device-width, initial-scale=1.0,
 *         maximum-scale=1.0, user-scalable=0" />
 *
 * `maximum-scale=1.0, user-scalable=0` disables pinch-zoom, which is a **WCAG
 * 2.1 SC 1.4.4 (Resize Text) failure** — and it is not configurable, it is
 * hardcoded in the parent theme. It also duplicated the correct viewport tag our
 * header.php outputs, and being later in wp_head it won.
 *
 * Ours (in header.php) is `width=device-width, initial-scale=1` with no zoom
 * lock, per vibe-frontend-standards §Responsive rule 1.
 *
 * Removed on `wp_head` at priority 1 — early enough to unhook before Divi's
 * default-priority callback runs, and late enough that the parent theme's
 * functions.php has been loaded and the callback actually exists to remove.
 */
function ak_remove_divi_viewport_meta() {
	remove_action( 'wp_head', 'et_add_viewport_meta' );
}
add_action( 'wp_head', 'ak_remove_divi_viewport_meta', 1 );
