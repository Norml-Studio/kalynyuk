<?php
/**
 * Navigation — menu locations, the header nav tree, and the language switcher.
 *
 * @package kalynyuk
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register our own menu locations.
 *
 * We deliberately reuse Divi's location KEYS (`primary-menu`, `footer-menu`)
 * rather than inventing `ak-primary`. Two reasons:
 *
 *   1. `primary-menu` already has the "Меню" menu assigned to it, so the header
 *      works the moment this theme's header.php goes live — no admin step, no
 *      window where the nav is empty.
 *   2. Re-registering a location only overwrites its admin LABEL; the location
 *      key and its saved assignment survive. So when Divi is removed in phase 4,
 *      the location is still ours and nothing breaks.
 */
function ak_register_menus() {
	register_nav_menus(
		array(
			'primary-menu' => __( 'Primary menu (header)', 'kalynyuk' ),
			'footer-menu'  => __( 'Footer menu', 'kalynyuk' ),
		)
	);
}
add_action( 'after_setup_theme', 'ak_register_menus' );

/**
 * Fall back to the default language's menu when a language has no assignment.
 *
 * ⚠️ THIS IS THE FIX FOR A LIVE PRODUCTION BUG, and the cause was mis-diagnosed
 * in docs/05-issues.md before this. Polylang's front-end filter
 * (polylang/src/frontend/frontend-nav-menu.php:244) does:
 *
 *     $menus[$loc] = empty($options['nav_menus'][$theme][$loc][$curlang]) ? 0 : …
 *
 * i.e. it OVERWRITES every menu location with 0 unless that location has an
 * explicit per-language assignment. `polylang.nav_menus` was an empty array, so
 * on the front end `primary-menu` resolved to 0 in every language, `wp_nav_menu`
 * found nothing, and Divi's menu module fell back to `wp_page_menu()` — which is
 * why production showed an auto-list of 12 pages instead of the curated 8-item
 * menu. The Divi module was never the problem.
 *
 * The data side is fixed (uk → menu 2). This filter fixes the STRUCTURE: any
 * language without its own assignment inherits the default language's menu
 * instead of rendering an empty header. So a third language added tomorrow gets a
 * working nav on day one, and only needs its own menu when the labels should
 * differ. Nothing here hardcodes a language.
 *
 * Runs at priority 30 — after Polylang's 20, so it sees the zeroed values.
 *
 * @param array $menus location => menu_id.
 * @return array
 */
function ak_nav_menu_location_fallback( $menus ) {
	if ( ! is_array( $menus ) || ! function_exists( 'pll_default_language' ) ) {
		return $menus;
	}

	$options = get_option( 'polylang' );
	$theme   = get_option( 'stylesheet' );
	$default = pll_default_language( 'slug' );

	if ( empty( $options['nav_menus'][ $theme ] ) || ! $default ) {
		return $menus;
	}

	foreach ( $menus as $location => $menu_id ) {
		if ( ! empty( $menu_id ) ) {
			continue;
		}

		$fallback = $options['nav_menus'][ $theme ][ $location ][ $default ] ?? 0;

		if ( $fallback ) {
			$menus[ $location ] = (int) $fallback;
		}
	}

	return $menus;
}
add_filter( 'theme_mod_nav_menu_locations', 'ak_nav_menu_location_fallback', 30 );

/**
 * Build a two-level tree of menu items for a location.
 *
 * Returns a plain array instead of using a Walker subclass: the header needs
 * markup a Walker makes awkward — a parent with children must render as a
 * `<button>` (header-standard §1/§4), not an `<a>` wrapped in extra `<div>`s.
 * Building the tree here keeps the template readable and the markup exactly what
 * the standard asks for.
 *
 * Polylang filters `wp_get_nav_menu_object` / the location→menu map per language,
 * so this returns the current language's menu automatically. Nothing here knows
 * how many languages exist.
 *
 * @param string $location Theme location slug.
 * @return array<int, array{title:string,url:string,target:string,classes:string,children:array}>
 */
function ak_nav_tree( $location = 'primary-menu' ) {
	$locations = get_nav_menu_locations();

	if ( empty( $locations[ $location ] ) ) {
		return array();
	}

	$menu = wp_get_nav_menu_object( $locations[ $location ] );

	if ( ! $menu ) {
		return array();
	}

	$items = wp_get_nav_menu_items( $menu->term_id );

	if ( empty( $items ) ) {
		return array();
	}

	_wp_menu_item_classes_by_context( $items );

	$by_parent = array();

	foreach ( $items as $item ) {
		$by_parent[ (int) $item->menu_item_parent ][] = $item;
	}

	$build = static function ( $parent_id ) use ( &$build, $by_parent ) {
		$out = array();

		foreach ( $by_parent[ $parent_id ] ?? array() as $item ) {
			$out[] = array(
				'id'       => (int) $item->ID,
				'title'    => $item->title,
				'url'      => $item->url,
				'current'  => in_array( 'current-menu-item', (array) $item->classes, true )
					|| in_array( 'current-menu-ancestor', (array) $item->classes, true )
					|| in_array( 'current-menu-parent', (array) $item->classes, true ),
				'children' => $build( (int) $item->ID ),
			);
		}

		return $out;
	};

	return $build( 0 );
}

/**
 * Language switcher data.
 *
 * `hide_if_no_translation` is the whole trick for "leave room for a future
 * language": a language only appears once it actually has a published translation
 * of the current object. So `ru` is absent today (its only page is a draft) and
 * will appear by itself when it is built — with no code change and no hardcoded
 * language list anywhere.
 *
 * @return array<int, array{slug:string,name:string,url:string,flag:string,current:bool}>
 */
function ak_language_switcher() {
	if ( ! function_exists( 'pll_the_languages' ) ) {
		return array();
	}

	$langs = pll_the_languages(
		array(
			'raw'                    => 1,
			'hide_if_no_translation' => 1,
			'hide_current'           => 0,
			'display_names_as'       => 'slug',
		)
	);

	if ( empty( $langs ) || ! is_array( $langs ) ) {
		return array();
	}

	$out = array();

	foreach ( $langs as $lang ) {
		$out[] = array(
			'slug'    => $lang['slug'] ?? '',
			'name'    => $lang['name'] ?? ( $lang['slug'] ?? '' ),
			'url'     => $lang['url'] ?? '',
			'flag'    => $lang['flag'] ?? '',
			'current' => ! empty( $lang['current_lang'] ),
		);
	}

	// Put the current language first — the design shows it as the collapsed
	// trigger with the rest dropping below it.
	usort(
		$out,
		static function ( $a, $b ) {
			return ( $b['current'] ? 1 : 0 ) <=> ( $a['current'] ? 1 : 0 );
		}
	);

	return $out;
}

/**
 * Translate a page ID into the current language.
 *
 * Used for the chrome CTA: one ACF field holds the target page, and this resolves
 * it to that page's translation at render time. That is why the CTA needs no
 * per-language ACF options page.
 *
 * @param int $post_id Post ID in any language.
 * @return int Post ID in the current language, or the original if untranslated.
 */
function ak_translate_id( $post_id ) {
	$post_id = (int) $post_id;

	if ( $post_id && function_exists( 'pll_get_post' ) ) {
		$translated = pll_get_post( $post_id );

		if ( $translated ) {
			return (int) $translated;
		}
	}

	return $post_id;
}
