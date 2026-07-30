<?php
/**
 * The incremental de-Divi mechanism.
 *
 * THE PROBLEM. A page's content is one ~200 KB blob of Divi shortcodes. Rebuilding
 * it section by section means, for each section: render OUR version, and stop Divi
 * rendering ITS version — otherwise the page ships both.
 *
 * THREE WAYS TO DO THAT, and why this is the one:
 *
 *   1. Convert the page to Gutenberg + ACF blocks. Correct end state, but it is
 *      all-or-nothing: you cannot convert one section of a Divi page. Rejected for
 *      the migration period; it is where phase 3 ends, not how it proceeds.
 *   2. Delete the section from `post_content` in the database. Surgical, and
 *      irreversible without a revision restore. On a 200 KB blob, one bad regex
 *      destroys the page.
 *   3. THIS: leave `post_content` untouched and strip the leading sections at
 *      RENDER time, driven by a per-page count.
 *
 * Option 3 costs one filter and is instantly reversible — set the count back to 0
 * and the Divi section returns. Nothing is destroyed, so a mistake is visible and
 * costs nothing. It also composes: rebuild the second section, set the count to 2.
 *
 * The count is deliberately a COUNT OF LEADING SECTIONS, not a list of ids: Divi
 * sections have no stable identifiers in `post_content`, and they are rebuilt
 * top-down, so "the first N" is the only honest addressing scheme.
 *
 * @package kalynyuk
 */

defined( 'ABSPATH' ) || exit;

/**
 * The default-language twin of a post, or 0 when there isn't one.
 *
 * @param int $post_id Post ID in any language.
 * @return int
 */
function ak_default_language_post( $post_id ) {
	$post_id = (int) $post_id;

	if ( ! $post_id || ! function_exists( 'pll_get_post' ) || ! function_exists( 'pll_default_language' ) ) {
		return 0;
	}

	$default = pll_default_language( 'slug' );

	if ( ! $default || pll_get_post_language( $post_id ) === $default ) {
		return 0;
	}

	$source = pll_get_post( $post_id, $default );

	return $source ? (int) $source : 0;
}

/**
 * Read a section field, falling back to the DEFAULT LANGUAGE's copy of the page.
 *
 * ⚠️ EVERY NATIVE SECTION MUST READ ITS FIELDS THROUGH THIS, not through get_field()
 * or get_post_meta() directly. It is the whole answer to "what happens when a new
 * language is added".
 *
 * THE PROBLEM IT SOLVES. Section content is per-post meta, and a translation is a
 * different post. So a Portuguese page starts with every field empty, and without a
 * fallback that produces the WORST possible state — not "untranslated", but broken:
 * the rebuilt-section count reads 0, so Divi's original section renders again, and
 * that page silently keeps the old design while the default language has the new
 * one. Add a fourth language and it happens again. Whoever adds the language has no
 * reason to suspect any of this.
 *
 * With the fallback, a new language renders the native section on day one, carrying
 * the default language's text, and the editor replaces the text field by field. The
 * layout is never wrong; only the wording is behind. That is the honest trade —
 * default-language text on a translated page is visible and self-correcting, whereas
 * a silently un-migrated section is neither.
 *
 * An EXPLICIT 0 does not fall back: get_post_meta() returns '0' for it and '' for
 * unset, so a translation can deliberately opt out of a section and keep Divi's.
 *
 * @param string   $key     Meta key.
 * @param int|null $post_id Defaults to the queried object.
 * @return mixed Empty string when neither the post nor its source has a value.
 */
function ak_section_field( $key, $post_id = null ) {
	$post_id = $post_id ? (int) $post_id : (int) get_queried_object_id();

	if ( ! $post_id ) {
		return '';
	}

	$value = get_post_meta( $post_id, $key, true );

	// '' means "never set". '0' / 0 / '[]' are deliberate values and must NOT fall back.
	if ( '' !== $value && null !== $value ) {
		return $value;
	}

	$source = ak_default_language_post( $post_id );

	return $source ? get_post_meta( $source, $key, true ) : '';
}

/**
 * How many leading Divi sections this page has already had rebuilt natively.
 *
 * Reads raw meta rather than get_field() so the count still works if ACF is ever
 * deactivated — a page whose Divi hero has been stripped must not come back with
 * neither hero just because a plugin is off.
 *
 * @param int|null $post_id Defaults to the queried object.
 * @return int
 */
function ak_native_section_count( $post_id = null ) {
	return max( 0, (int) ak_section_field( 'ak_native_sections', $post_id ) );
}

/**
 * Strip the first N top-level `[et_pb_section]…[/et_pb_section]` blocks.
 *
 * Counts bracket depth rather than using a regex: Divi nests `et_pb_section` inside
 * itself for specialty sections, so a greedy or lazy regex either eats the whole
 * page or stops at the first inner closing tag. Walking the shortcode boundaries is
 * the only reliable way.
 *
 * @param string $content Post content.
 * @param int    $strip   How many leading sections to drop.
 * @return string
 */
function ak_strip_leading_divi_sections( $content, $strip ) {
	if ( $strip < 1 || false === strpos( $content, '[et_pb_section' ) ) {
		return $content;
	}

	$offset  = 0;
	$removed = 0;

	while ( $removed < $strip ) {
		$start = strpos( $content, '[et_pb_section', $offset );

		if ( false === $start ) {
			break;
		}

		// Walk forward tracking open/close depth of et_pb_section only.
		$depth = 0;
		$pos   = $start;
		$end   = false;

		while ( true ) {
			$open  = strpos( $content, '[et_pb_section', $pos + 1 );
			$close = strpos( $content, '[/et_pb_section]', $pos + 1 );

			if ( false === $close ) {
				break; // Malformed — leave the content alone.
			}

			if ( false !== $open && $open < $close ) {
				++$depth;
				$pos = $open;
				continue;
			}

			if ( 0 === $depth ) {
				$end = $close + strlen( '[/et_pb_section]' );
				break;
			}

			--$depth;
			$pos = $close;
		}

		if ( false === $end ) {
			break;
		}

		$content = substr( $content, 0, $start ) . substr( $content, $end );
		$offset  = $start;
		++$removed;
	}

	return $content;
}

/**
 * Apply the strip on the front end.
 *
 * Priority 5 — before Divi's own `the_content` processing at 10, so it never sees
 * the sections we removed. Restricted to the main query on a singular view so it
 * cannot touch excerpts, feeds, or a secondary loop.
 *
 * @param string $content Post content.
 * @return string
 */
function ak_filter_native_sections( $content ) {
	if ( is_admin() || ! is_singular() || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}

	$strip = ak_native_section_count();

	return $strip ? ak_strip_leading_divi_sections( $content, $strip ) : $content;
}
add_filter( 'the_content', 'ak_filter_native_sections', 5 );

/**
 * Print the native (theme-built) sections for the current page, in order.
 *
 * ⚠️ CALLED FROM header.php, NOT from a `the_content` filter — and that matters.
 *
 * The first implementation prepended this markup inside `the_content` at priority 5.
 * `wpautop` runs on the same filter at priority 10, so it auto-paragraphed our
 * output: it appended a stray empty `<p>` inside `.hero__foot`, which became a third
 * flex item and pushed the stat 330px off its right-aligned position. Measured, not
 * guessed — `.hero__foot` had three children, the last an empty classless `<p>`.
 * `wpautop` can do worse than that to nested markup.
 *
 * Printing from header.php, straight after `<div id="et-main-area">`, puts the
 * sections in exactly the same place in the document with no content filter
 * anywhere near them. The `the_content` filter keeps its one job: removing the Divi
 * sections these replace, which is a string operation on shortcode text.
 *
 * Only the hero exists today. As phase 3 rebuilds more sections, add them here in
 * document order and bump the page's "Rebuilt sections" count to match.
 *
 * @return void
 */
function ak_render_native_sections() {
	if ( ! is_singular() || ! is_main_query() ) {
		return;
	}

	get_template_part( 'template-parts/sections/hero' );
}

/**
 * Register the per-page control and the hero fields.
 */
function ak_acf_page_sections_fields() {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	acf_add_local_field_group(
		array(
			'key'      => 'group_ak_page_sections',
			'title'    => __( 'Native sections (de-Divi migration)', 'kalynyuk' ),
			'position' => 'side',
			'menu_order' => 5,
			'location' => array(
				array(
					array(
						'param'    => 'post_type',
						'operator' => '==',
						'value'    => 'page',
					),
				),
			),
			'fields'   => array(
				array(
					'key'           => 'field_ak_native_sections',
					'label'         => __( 'Rebuilt sections', 'kalynyuk' ),
					'name'          => 'ak_native_sections',
					'type'          => 'number',
					'min'           => 0,
					'default_value' => 0,
					'instructions'  => __( 'How many of this page\'s LEADING Divi sections have been rebuilt in the theme. Those sections stop rendering from the Divi content and the theme draws them instead. Set back to 0 to restore the Divi originals — nothing is deleted.', 'kalynyuk' ),
				),
			),
		)
	);

	acf_add_local_field_group(
		array
		(
			'key'      => 'group_ak_hero',
			'title'    => __( 'Hero', 'kalynyuk' ),
			'location' => array(
				array(
					array(
						'param'    => 'post_type',
						'operator' => '==',
						'value'    => 'page',
					),
				),
			),
			'fields'   => array(
				array(
					'key'   => 'field_ak_hero_heading',
					'label' => __( 'Heading', 'kalynyuk' ),
					'name'  => 'ak_hero_heading',
					'type'  => 'textarea',
					'rows'  => 3,
					'instructions' => __( 'Rendered as the page H1.', 'kalynyuk' ),
				),
				array(
					'key'   => 'field_ak_hero_image',
					'label' => __( 'Background image', 'kalynyuk' ),
					'name'  => 'ak_hero_image',
					'type'  => 'image',
					'return_format' => 'array',
					'preview_size'  => 'medium',
				),
				array(
					'key'   => 'field_ak_hero_caption',
					'label' => __( 'Caption (bottom left)', 'kalynyuk' ),
					'name'  => 'ak_hero_caption',
					'type'  => 'textarea',
					'rows'  => 2,
				),
				array(
					'key'   => 'field_ak_hero_stat_strong',
					'label' => __( 'Stat — emphasised line', 'kalynyuk' ),
					'name'  => 'ak_hero_stat_strong',
					'type'  => 'text',
					'instructions' => __( 'e.g. “+100 успішно”. Rendered in full-strength cream.', 'kalynyuk' ),
				),
				array(
					'key'   => 'field_ak_hero_stat_muted',
					'label' => __( 'Stat — muted line', 'kalynyuk' ),
					'name'  => 'ak_hero_stat_muted',
					'type'  => 'text',
					'instructions' => __( 'e.g. “реалізованих кейсів”. Rendered dimmer, as in the design.', 'kalynyuk' ),
				),
				array(
					'key'   => 'field_ak_hero_cta2_label',
					'label' => __( 'Secondary CTA — label', 'kalynyuk' ),
					'name'  => 'ak_hero_cta2_label',
					'type'  => 'text',
					'instructions' => __( 'The PRIMARY CTA is the shared one from Site chrome — header, hero and footer must agree (header-standard). This is the second, outlined button.', 'kalynyuk' ),
				),
				array(
					'key'           => 'field_ak_hero_cta2_page',
					'label'         => __( 'Secondary CTA — target', 'kalynyuk' ),
					'name'          => 'ak_hero_cta2_page',
					'type'          => 'post_object',
					'post_type'     => array( 'page' ),
					'return_format' => 'id',
					'allow_null'    => 1,
					'ui'            => 1,
				),
			),
		)
	);
}
add_action( 'acf/init', 'ak_acf_page_sections_fields' );

/**
 * Hero data for the current page, or null when there is nothing to render.
 *
 * @return array|null
 */
function ak_hero_data() {
	$id = (int) get_queried_object_id();

	if ( ! $id ) {
		return null;
	}

	$heading = (string) ak_section_field( 'ak_hero_heading', $id );

	// No heading means the page has no native hero — render nothing rather than an
	// empty green band.
	if ( '' === trim( $heading ) ) {
		return null;
	}

	$cta2_id  = (int) ak_section_field( 'ak_hero_cta2_page', $id );
	$cta2_lbl = (string) ak_section_field( 'ak_hero_cta2_label', $id );

	return array(
		'heading' => $heading,
		/*
		 * An attachment ID, not ACF's image array. Two reasons: the array shape only
		 * exists when ACF is active and ak_section_field() returns raw meta either
		 * way, and running it through ak_translate_id() picks up a per-language
		 * media item when one exists (Polylang's media translation is on) while
		 * returning the same ID when it does not.
		 */
		'image'       => ak_translate_id( (int) ak_section_field( 'ak_hero_image', $id ) ),
		'caption'     => (string) ak_section_field( 'ak_hero_caption', $id ),
		'stat_strong' => (string) ak_section_field( 'ak_hero_stat_strong', $id ),
		'stat_muted'  => (string) ak_section_field( 'ak_hero_stat_muted', $id ),
		'cta'         => ak_primary_cta(),
		'cta2'        => ( $cta2_id && $cta2_lbl )
			? array(
				'label' => $cta2_lbl,
				'url'   => get_permalink( ak_translate_id( $cta2_id ) ),
			)
			: null,
	);
}
