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
