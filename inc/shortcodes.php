<?php
/**
 * Shortcodes.
 *
 * @package kalynyuk
 */

defined( 'ABSPATH' ) || exit;

/**
 * [vertical_menu menu="Меню"] — renders a nav menu as a vertical list.
 *
 * ⚠️ LOAD-BEARING DURING THE MIGRATION. This shortcode is used inside all three
 * Divi header layouts (284, 2213, 2214). It must keep working, unchanged, until
 * phase 1 replaces the header with real markup — at which point it becomes dead
 * and can be deleted.
 *
 * Two things were fixed here without changing behaviour:
 *
 * 1. The callback was an unprefixed global (`vertical_menu_shortcode`), one
 *    collision away from a fatal redeclare. Renamed to ak_*. The SHORTCODE TAG is
 *    unchanged, so the three Divi layouts are unaffected — they reference the tag,
 *    not the function.
 *
 * 2. The `menu` argument matches a menu by its DISPLAY NAME, defaulting to the
 *    Cyrillic "Меню". Renaming that menu in wp-admin silently breaks navigation
 *    site-wide (documented in .claude/docs/05-issues.md). We now fall back to the
 *    menu assigned to the `primary-menu` location if the named lookup fails, so a
 *    rename degrades instead of blanking the header.
 *
 * @param array|string $atts Shortcode attributes.
 * @return string
 */
function ak_vertical_menu_shortcode( $atts ) {
	$atts = shortcode_atts(
		array(
			'menu' => 'Меню',
		),
		$atts,
		'vertical_menu'
	);

	$args = array(
		'container'       => 'div',
		'container_class' => 'custom-vertical-menu',
		'menu_class'      => 'vertical-menu',
		'echo'            => false,
		'fallback_cb'     => false,
	);

	// Prefer the named menu (existing behaviour); fall back to the theme location
	// so a rename in wp-admin cannot blank the navigation.
	if ( ! empty( $atts['menu'] ) && wp_get_nav_menu_object( $atts['menu'] ) ) {
		$args['menu'] = $atts['menu'];
	} else {
		$args['theme_location'] = 'primary-menu';
	}

	$menu = wp_nav_menu( $args );

	return is_string( $menu ) ? $menu : '';
}
add_shortcode( 'vertical_menu', 'ak_vertical_menu_shortcode' );
