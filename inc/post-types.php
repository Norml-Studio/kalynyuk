<?php
/**
 * Custom post types.
 *
 * Architecture decided via dev-wp-architecture's pre-check (2026-07-27, confirmed
 * by Petr). Spec:
 *
 *   service  → /poslugi/{slug}/   landing page = existing page 2423 "Послуги"
 *   guide    → /portugal/{slug}/  hub page     = existing page 1453 "Portugal"
 *
 * No taxonomies. Six services and four guides need no filtering, and inventing a
 * taxonomy nobody queries is the mistake the pre-check warns about in the other
 * direction. Add one when a real filter appears.
 *
 * ⚠️ CODE-PLACEMENT TRADEOFF, stated honestly. The textbook answer is a
 * {client}-core plugin, so content survives a theme switch. These live in the
 * THEME — Norml's pragmatic default — because the theme IS this project's repo and
 * there is no site-core plugin. Consequence: deactivate the theme and every
 * service/guide becomes unreachable (the posts stay in the DB; only the post type
 * stops being registered). Acceptable for a bespoke single-purpose theme; revisit
 * if this content ever needs to outlive the theme.
 *
 * ⚠️ has_archive is FALSE on both, deliberately. Each rewrite base collides with a
 * page that already exists and already has SEO history (2423, 1453). WordPress
 * resolves /poslugi/ to the page and /poslugi/{slug}/ to the CPT single, because
 * the rule `poslugi/([^/]+)` cannot match a bare /poslugi/. Turning has_archive on
 * would shadow both pages.
 *
 * @package kalynyuk
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register the project's post types.
 */
function ak_register_post_types() {
	// ─── service ──────────────────────────────────────────────────────────────
	register_post_type(
		'service',
		array(
			'labels'       => array(
				'name'               => __( 'Services', 'kalynyuk' ),
				'singular_name'      => __( 'Service', 'kalynyuk' ),
				'add_new_item'       => __( 'Add service', 'kalynyuk' ),
				'edit_item'          => __( 'Edit service', 'kalynyuk' ),
				'new_item'           => __( 'New service', 'kalynyuk' ),
				'view_item'          => __( 'View service', 'kalynyuk' ),
				'search_items'       => __( 'Search services', 'kalynyuk' ),
				'not_found'          => __( 'No services found', 'kalynyuk' ),
				'menu_name'          => __( 'Services', 'kalynyuk' ),
			),
			'public'       => true,
			'has_archive'  => false, // see file docblock
			'rewrite'      => array(
				'slug'       => 'poslugi',
				'with_front' => false,
			),
			'menu_icon'    => 'dashicons-portfolio',
			'menu_position' => 21,
			// editor is kept so the body can hold prose; the structured parts come
			// from ACF once the field group exists.
			'supports'     => array( 'title', 'editor', 'excerpt', 'thumbnail', 'page-attributes', 'revisions' ),
			'show_in_rest' => true, // required for Gutenberg + the ACF block editor
			'hierarchical' => false,
		)
	);

	// ─── guide (the "Portugal" section) ────────────────────────────────────────
	register_post_type(
		'guide',
		array(
			'labels'       => array(
				'name'               => __( 'Guides', 'kalynyuk' ),
				'singular_name'      => __( 'Guide', 'kalynyuk' ),
				'add_new_item'       => __( 'Add guide', 'kalynyuk' ),
				'edit_item'          => __( 'Edit guide', 'kalynyuk' ),
				'new_item'           => __( 'New guide', 'kalynyuk' ),
				'view_item'          => __( 'View guide', 'kalynyuk' ),
				'search_items'       => __( 'Search guides', 'kalynyuk' ),
				'not_found'          => __( 'No guides found', 'kalynyuk' ),
				'menu_name'          => __( 'Guides (Portugal)', 'kalynyuk' ),
			),
			'public'       => true,
			'has_archive'  => false, // see file docblock
			'rewrite'      => array(
				'slug'       => 'portugal',
				'with_front' => false,
			),
			'menu_icon'    => 'dashicons-book-alt',
			'menu_position' => 22,
			'supports'     => array( 'title', 'editor', 'excerpt', 'thumbnail', 'page-attributes', 'revisions' ),
			'show_in_rest' => true,
			'hierarchical' => false,
		)
	);
}
add_action( 'init', 'ak_register_post_types' );

/**
 * Make both post types translatable by Polylang.
 *
 * Done via the filter rather than the settings screen so it is version-controlled
 * and travels with the theme — and so a future language needs no code change.
 * Nothing here hardcodes a language.
 *
 * @param string[] $types Post types Polylang manages.
 * @return string[]
 */
function ak_polylang_post_types( $types ) {
	$types['service'] = 'service';
	$types['guide']   = 'guide';

	return $types;
}
add_filter( 'pll_get_post_types', 'ak_polylang_post_types' );

/**
 * Retire Divi's `project` portfolio post type from public output.
 *
 * Divi registers it as public with two public taxonomies and 0 posts, so the site
 * serves live, crawlable, permanently empty archive URLs
 * (.claude/docs/05-issues.md). We cannot unregister a parent-theme post type
 * cleanly — Divi's templates reference it — so we strip it from the front end and
 * from search instead, which is the non-destructive equivalent.
 *
 * Approved by Petr 2026-07-27 ("project можно удалить"). If the type is ever
 * needed, delete this function rather than editing Divi.
 *
 * @param array  $args      register_post_type() arguments.
 * @param string $post_type Post type key.
 * @return array
 */
function ak_retire_divi_project_cpt( $args, $post_type ) {
	if ( 'project' !== $post_type ) {
		return $args;
	}

	$args['public']              = false;
	$args['publicly_queryable']  = false;
	$args['has_archive']         = false;
	$args['exclude_from_search'] = true;
	$args['show_in_nav_menus']   = false;
	$args['show_in_rest']        = false;
	// Left visible in wp-admin (show_ui untouched) so nothing disappears from the
	// dashboard unexpectedly — it holds 0 posts anyway.

	return $args;
}
add_filter( 'register_post_type_args', 'ak_retire_divi_project_cpt', 10, 2 );

/**
 * Same treatment for Divi's project taxonomies.
 *
 * @param array  $args     register_taxonomy() arguments.
 * @param string $taxonomy Taxonomy key.
 * @return array
 */
function ak_retire_divi_project_taxonomies( $args, $taxonomy ) {
	if ( ! in_array( $taxonomy, array( 'project_category', 'project_tag' ), true ) ) {
		return $args;
	}

	$args['public']             = false;
	$args['publicly_queryable'] = false;
	$args['show_in_nav_menus']  = false;
	$args['show_in_rest']       = false;

	return $args;
}
add_filter( 'register_taxonomy_args', 'ak_retire_divi_project_taxonomies', 10, 2 );

/**
 * Flush rewrite rules once after the post types change.
 *
 * Registering a post type does NOT create its rewrite rules until permalinks are
 * flushed — the classic "my CPT single 404s" gotcha. Flushing on every request is
 * expensive, so this runs once per version bump and records it.
 */
function ak_maybe_flush_rewrites() {
	$flushed = get_option( 'ak_rewrites_flushed_for' );

	if ( AK_VERSION !== $flushed ) {
		flush_rewrite_rules( false );
		update_option( 'ak_rewrites_flushed_for', AK_VERSION, false );
	}
}
add_action( 'init', 'ak_maybe_flush_rewrites', 20 );
