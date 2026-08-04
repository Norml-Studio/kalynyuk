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
 * Read an ACF REPEATER through the same per-language fallback as ak_section_field().
 *
 * ⚠️ ak_section_field() ALONE CANNOT READ A REPEATER, and a repeater cannot be skipped
 * for this section — the `about` band is a list, and hard-coding four sets of flat fields
 * would mean a code change to add a fifth credential.
 *
 * ACF flattens a repeater into one meta row per cell: `ak_about_items` holds the ROW
 * COUNT and each cell is its own key, `ak_about_items_0_label`. That shape is what makes
 * the fallback work at all — every cell is an independent meta key, so reading each one
 * through ak_section_field() gives per-CELL fallback, which is finer-grained than
 * per-row and is exactly the behaviour the multilingual rule wants: a translation that
 * has localised two of four labels shows those two and inherits the rest, rather than
 * choosing between "all translated" and "all default".
 *
 * The count falls back too, so a brand-new language renders all four rows on day one.
 * A translation may still deliberately shorten the list by setting its own count.
 *
 * @param string   $key       Repeater meta key.
 * @param string[] $subfields Sub-field names to read per row.
 * @param int|null $post_id   Defaults to the queried object.
 * @return array<int,array<string,mixed>>
 */
function ak_section_rows( $key, array $subfields, $post_id = null ) {
	$count = (int) ak_section_field( $key, $post_id );
	$rows  = array();

	for ( $i = 0; $i < $count; $i++ ) {
		$row = array();

		foreach ( $subfields as $sub ) {
			$row[ $sub ] = ak_section_field( $key . '_' . $i . '_' . $sub, $post_id );
		}

		$rows[] = $row;
	}

	return $rows;
}

/**
 * Every section the theme can render, slug => admin label.
 *
 * The slug is also the template name: `template-parts/sections/{slug}.php`. Filter
 * `ak_section_registry` rather than editing this, so a plugin or a child could add
 * one without touching the theme.
 *
 * @return array<string,string>
 */
function ak_section_registry() {
	return (array) apply_filters(
		'ak_section_registry',
		array(
			'hero'       => __( 'Hero', 'kalynyuk' ),
			'about'      => __( 'About / credentials', 'kalynyuk' ),
			'cta'        => __( 'CTA banner', 'kalynyuk' ),
			'services'   => __( 'Services grid', 'kalynyuk' ),
			'trust'      => __( 'Why us (accordion)', 'kalynyuk' ),
			'order'      => __( 'Book a consultation', 'kalynyuk' ),
			'testimonials' => __( 'Testimonials (Google reviews)', 'kalynyuk' ),
			'faq'        => __( 'FAQ', 'kalynyuk' ),
			'seo'        => __( 'SEO copy block', 'kalynyuk' ),
			'calculator' => __( 'Mortgage calculator', 'kalynyuk' ),
		)
	);
}

/**
 * The native sections for a page, in render order.
 *
 * ⚠️ THIS REPLACED A PLAIN COUNT, and the reason is worth recording. The first
 * version stored only a NUMBER — "how many leading Divi sections have been
 * rebuilt" — and `ak_render_native_sections()` hardcoded `get_template_part(
 * 'sections/hero' )`. That was honest while exactly one page and one section
 * existed. The moment a SECOND page needed a DIFFERENT section (the calculator on
 * page 566) the number stopped carrying enough information: it says how many to
 * strip but not what to draw.
 *
 * Storing the ordered list instead fixes that and removes a whole class of bug at
 * the same time — the strip count is now DERIVED from the list (`count()`), so the
 * "how many to remove" and "what to render" can no longer drift apart. Under the old
 * shape, setting the count to 2 while rendering one section silently deleted a Divi
 * section and put nothing in its place.
 *
 * Unknown slugs are dropped rather than fatally `get_template_part`-ing into
 * nothing: a renamed template should degrade to "Divi section missing", which is
 * visible, not to a blank page.
 *
 * @param int|null $post_id Defaults to the queried object.
 * @return string[]
 */
function ak_native_section_slugs( $post_id = null ) {
	$value = ak_section_field( 'ak_sections', $post_id );

	if ( empty( $value ) ) {
		return array();
	}

	$registry = ak_section_registry();

	return array_values( array_intersect( (array) $value, array_keys( $registry ) ) );
}

/**
 * How many leading Divi sections this page has already had rebuilt natively.
 *
 * Derived from the section list — see ak_native_section_slugs() for why it is no
 * longer stored separately.
 *
 * Falls back to the legacy numeric `ak_native_sections` meta so a page saved before
 * the change keeps working until it is migrated. Reads raw meta rather than
 * get_field() so the strip still applies if ACF is ever deactivated: a page whose
 * Divi hero has been removed must not come back with NEITHER hero because a plugin
 * is off.
 *
 * @param int|null $post_id Defaults to the queried object.
 * @return int
 */
function ak_native_section_count( $post_id = null ) {
	$slugs = ak_native_section_slugs( $post_id );

	if ( $slugs ) {
		/*
		 * ONE native section does not always replace exactly ONE Divi section.
		 *
		 * The calculator is the case that forced this: its Divi layout was two
		 * sections — the calculator itself, and a second section holding the
		 * "Довідка" popup. Our single `calculator` template renders both, so two Divi
		 * sections have to come out while only one slug is listed.
		 *
		 * Rather than inventing a placeholder slug that renders nothing — which would
		 * be a lie in the section list and would confuse the next person reading it —
		 * the surplus is stated explicitly. It stays 0 for every ordinary page.
		 */
		return count( $slugs ) + max( 0, (int) ak_section_field( 'ak_strip_extra', $post_id ) );
	}

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
 * Sections replaced IN PLACE, addressed by their Divi `module_id`.
 *
 * ⚠️ WHY A SECOND MECHANISM EXISTS ALONGSIDE THE LEADING-COUNT ONE.
 *
 * The original stripper only removes LEADING sections, and the reasoning was sound:
 * Divi sections have no stable identifier in `post_content`, and rebuilding a page
 * top-down means "the first N" is the only honest addressing scheme.
 *
 * That held until the homepage. Its calculator is the SIXTH section — with four
 * untouched Divi sections above it — so replacing it by count would mean rebuilding
 * everything above it first. But that section, unlike the ones on page 566, carries
 * `module_id="calculator"`: a real, stable, editor-visible identifier. Where one
 * exists, addressing by it is strictly better, and it unlocks migrating a page's
 * sections in whatever order the design work actually happens.
 *
 * Stored as one `divi_module_id = section_slug` per line, deliberately as plain text:
 * this is a migration control that developers operate, and a line of text is easier
 * to read, diff and grep than an ACF repeater.
 *
 * A line may also address a section by its Divi ADMIN LABEL, for the sections that have
 * no CSS ID at all — see ak_replace_divi_section_by_id() for why that second form exists
 * and why `module_id` is still the one to prefer:
 *
 *     calculator  = calculator     module_id="calculator"
 *     label:Order = order          admin_label="Order"
 *
 * @param int|null $post_id Defaults to the queried object.
 * @return array<string,string> selector => section slug, where selector is a module_id
 *                              or `label:{admin_label}`
 */
function ak_inline_section_map( $post_id = null ) {
	$raw = (string) ak_section_field( 'ak_inline_sections', $post_id );

	if ( '' === trim( $raw ) ) {
		return array();
	}

	$registry = ak_section_registry();
	$map      = array();

	foreach ( preg_split( '/\R/', $raw ) as $line ) {
		if ( ! strpos( $line, '=' ) ) {
			continue;
		}

		list( $module_id, $slug ) = array_map( 'trim', explode( '=', $line, 2 ) );

		// An unknown slug is skipped rather than fatally rendering nothing: a renamed
		// template should leave the Divi original in place, which is visible, not
		// blank the section.
		if ( '' !== $module_id && isset( $registry[ $slug ] ) ) {
			$map[ $module_id ] = $slug;
		}
	}

	return $map;
}

/**
 * Replace ONE Divi section, matched by `module_id`, with an arbitrary string.
 *
 * Same bracket-depth walk as ak_strip_leading_divi_sections() and for the same
 * reason — Divi nests `et_pb_section` inside itself for specialty sections, so no
 * regex can find the matching close tag.
 *
 * @param string $content     Post content.
 * @param string $module_id   The section's module_id attribute.
 * @param string $replacement What to put in its place.
 * @return string
 */
function ak_replace_divi_section_by_id( $content, $module_id, $replacement ) {
	$offset = 0;

	while ( true ) {
		$start = strpos( $content, '[et_pb_section', $offset );

		if ( false === $start ) {
			return $content;
		}

		$tag_end = strpos( $content, ']', $start );

		if ( false === $tag_end ) {
			return $content;
		}

		/*
		 * ⚠️ TWO ADDRESSING SCHEMES, and the second one exists because the homepage ran
		 * out of the first. `module_id` is the good identifier — an editor sets it in
		 * Divi under Advanced → CSS ID and it is stable. But three sections on page 11
		 * (`Order`, `Reviews`, `FAQ`) simply do not have one, and they are not leading
		 * sections either, so NEITHER existing mechanism could reach them.
		 *
		 * The options were: add a CSS ID to each in the builder — which edits
		 * `post_content`, the exact thing this whole file is built to avoid; or match on
		 * something already in the shortcode. `admin_label` is already there, is what the
		 * builder shows in its layers panel, and costs no database write at all.
		 *
		 * So a map line may be either form:
		 *
		 *     calculator      = calculator     → matches module_id="calculator"
		 *     label:Order     = order          → matches admin_label="Order"
		 *
		 * `module_id` stays the DEFAULT and the preferred one: admin_label is a display
		 * name an editor can rename without realising it addresses anything. Prefer
		 * giving a section a CSS ID when you can; use `label:` when you cannot.
		 */
		if ( 0 === strpos( $module_id, 'label:' ) ) {
			$attribute = 'admin_label';
			$needle    = substr( $module_id, strlen( 'label:' ) );
		} else {
			$attribute = 'module_id';
			$needle    = $module_id;
		}

		$is_match = (bool) preg_match(
			'/\b' . $attribute . '="' . preg_quote( $needle, '/' ) . '"/',
			substr( $content, $start, $tag_end - $start )
		);

		// Walk to this section's matching close, counting nested sections.
		$depth = 0;
		$pos   = $start;
		$end   = false;

		while ( true ) {
			$open  = strpos( $content, '[et_pb_section', $pos + 1 );
			$close = strpos( $content, '[/et_pb_section]', $pos + 1 );

			if ( false === $close ) {
				return $content; // Malformed — leave it alone.
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

		if ( $is_match ) {
			return substr( $content, 0, $start ) . $replacement . substr( $content, $end );
		}

		$offset = $end;
	}
}

/**
 * Claim the page's `<h1>` — returns 'h1' to the first caller, 'h2' to every one after.
 *
 * ⚠️ THIS EXISTS BECAUSE A SECTION CANNOT KNOW ITS OWN RANK. The calculator was
 * given an `<h1>` when it was migrated as page 566, where it is the whole page and
 * the page genuinely had no h1 — a real defect, correctly fixed. Then the same
 * template was pointed at the homepage, where it sits below the hero, and the page
 * immediately had TWO h1s. Caught by measuring the homepage, not by reasoning about
 * the template.
 *
 * Neither "always h1" nor "always h2" is right, because the correct answer depends on
 * what else is on the page. Claiming is right: sections render in document order, so
 * the first one to ask is the one at the top, and it gets the h1. On `/calc/` that is
 * the calculator; on the homepage it is the hero. No section needs to know about any
 * other, and nothing has to be configured.
 *
 * @return string 'h1' or 'h2'
 */
function ak_claim_h1() {
	static $claimed = false;

	if ( $claimed ) {
		return 'h2';
	}

	$claimed = true;

	return 'h1';
}

/**
 * The token left in the content where an in-place section will be rendered.
 *
 * ⚠️ A TOKEN, NOT THE MARKUP ITSELF — this is the wpautop lesson again, in a second
 * place. Injecting rendered HTML at priority 5 would hand it to wpautop at 10, which
 * is exactly what appended a stray `<p>` inside the hero and broke its layout. The
 * strip filter leaves an inert comment; a LATE filter (priority 20, after wpautop has
 * finished) swaps in the real markup, which therefore never meets a content filter.
 *
 * @param string $slug Section slug.
 * @return string
 */
function ak_section_token( $slug ) {
	return '<!--ak-section:' . $slug . '-->';
}

/**
 * Swap the tokens for the actual rendered sections. Runs AFTER wpautop.
 *
 * wpautop may wrap a lone comment in a paragraph, so the pattern optionally eats a
 * surrounding `<p>` — otherwise every in-place section would ship inside a stray
 * empty paragraph.
 *
 * @param string $content Post content.
 * @return string
 */
function ak_render_inline_sections( $content ) {
	if ( false === strpos( $content, '<!--ak-section:' ) ) {
		return $content;
	}

	return preg_replace_callback(
		'#(?:<p>\s*)?<!--ak-section:([a-z0-9\-_]+)-->(?:\s*</p>)?#i',
		static function ( $m ) {
			ob_start();
			get_template_part( 'template-parts/sections/' . $m[1] );

			return (string) ob_get_clean();
		},
		$content
	);
}
add_filter( 'the_content', 'ak_render_inline_sections', 20 );

/**
 * Sections APPENDED after the content, in order.
 *
 * ⚠️ A THIRD PLACEMENT MECHANISM, and it exists because the design grew a section the
 * Divi page never had. The other two both need an existing Divi section to anchor to:
 * `ak_sections` strips the leading N and draws in their place, and `ak_inline_sections`
 * swaps one out where it sits. A section that is NEW has nothing to replace.
 *
 * The alternative was to invent a Divi section in `post_content` purely so there would be
 * something to replace — writing to the blob this whole file exists to leave alone, and
 * leaving a decoy section for the next person to puzzle over. Appending costs one filter.
 *
 * @param int|null $post_id Defaults to the queried object.
 * @return string[]
 */
function ak_append_section_slugs( $post_id = null ) {
	$value = ak_section_field( 'ak_append_sections', $post_id );

	if ( empty( $value ) ) {
		return array();
	}

	return array_values( array_intersect( (array) $value, array_keys( ak_section_registry() ) ) );
}

/**
 * Print the appended sections after the post content.
 *
 * Priority 21 — after wpautop (10) and after the token swap (20), so the markup is never
 * handed to a content filter. That is the same lesson as ak_section_token(): rendered HTML
 * injected before wpautop comes back with stray paragraphs inside it.
 *
 * @param string $content Post content.
 * @return string
 */
function ak_append_native_sections( $content ) {
	if ( is_admin() || ! is_singular() || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}

	foreach ( ak_append_section_slugs() as $slug ) {
		ob_start();
		get_template_part( 'template-parts/sections/' . $slug );
		$content .= (string) ob_get_clean();
	}

	return $content;
}
add_filter( 'the_content', 'ak_append_native_sections', 21 );

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

	if ( $strip ) {
		$content = ak_strip_leading_divi_sections( $content, $strip );
	}

	// In-place replacements run after the leading strip so the two cannot fight over
	// the same section: anything already removed simply will not be found by id.
	foreach ( ak_inline_section_map() as $module_id => $slug ) {
		$content = ak_replace_divi_section_by_id( $content, $module_id, ak_section_token( $slug ) );
	}

	return $content;
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
 * Which sections, and in what order, comes from the page itself — see
 * ak_native_section_slugs(). Adding a section to the theme is therefore two steps
 * and no edit here: drop `template-parts/sections/{slug}.php` in place and register
 * the slug in ak_section_registry().
 *
 * @return void
 */
function ak_render_native_sections() {
	if ( ! is_singular() || ! is_main_query() ) {
		return;
	}

	foreach ( ak_native_section_slugs() as $slug ) {
		get_template_part( 'template-parts/sections/' . $slug );
	}
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
					'key'           => 'field_ak_sections',
					'label'         => __( 'Rebuilt sections', 'kalynyuk' ),
					'name'          => 'ak_sections',
					'type'          => 'select',
					'choices'       => ak_section_registry(),
					'multiple'      => 1,
					'ui'            => 1,
					'return_format' => 'value',
					'instructions'  => __( 'The theme-built sections for this page, IN DOCUMENT ORDER. That many of the page\'s LEADING Divi sections stop rendering and these are drawn instead — so the order here must match the order they appear in the Divi layout. Clear the field to restore the Divi originals; nothing is ever deleted.', 'kalynyuk' ),
				),
				array(
					'key'           => 'field_ak_strip_extra',
					'label'         => __( 'Extra Divi sections to remove', 'kalynyuk' ),
					'name'          => 'ak_strip_extra',
					'type'          => 'number',
					'min'           => 0,
					'default_value' => 0,
					'instructions'  => __( 'Leave at 0 unless one theme section replaces MORE than one Divi section. The calculator is the case: its Divi layout was two sections (the calculator and the “Довідка” popup) and one template now renders both, so this is 1 there.', 'kalynyuk' ),
				),
				array(
					'key'          => 'field_ak_inline_sections',
					'label'        => __( 'Sections replaced in place', 'kalynyuk' ),
					'name'         => 'ak_inline_sections',
					'type'         => 'textarea',
					'rows'         => 3,
					'instructions' => __( 'For sections that are NOT at the top of the page. One “divi_module_id = section_slug” per line — e.g. “calculator = calculator”. The Divi section with that module ID is swapped for the theme section exactly where it sits, so a page can be migrated in any order. Find the module ID in the Divi section settings under Advanced → CSS ID.', 'kalynyuk' ),
				array(
					'key'           => 'field_ak_append_sections',
					'label'         => __( 'Sections appended at the end', 'kalynyuk' ),
					'name'          => 'ak_append_sections',
					'type'          => 'select',
					'choices'       => ak_section_registry(),
					'multiple'      => 1,
					'ui'            => 1,
					'return_format' => 'value',
					'instructions'  => __( 'For sections the Divi page never had, so there is nothing to replace. They render AFTER the content, in this order. The other two controls above both need an existing Divi section to anchor to.', 'kalynyuk' ),
				),
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

	acf_add_local_field_group(
		array(
			'key'      => 'group_ak_about',
			'title'    => __( 'About / credentials', 'kalynyuk' ),
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
					'key'          => 'field_ak_about_heading',
					'label'        => __( 'Heading', 'kalynyuk' ),
					'name'         => 'ak_about_heading',
					'type'         => 'textarea',
					'rows'         => 2,
					'instructions' => __( 'Left column. Leave empty and the section renders NOTHING — it does not fall back to the Divi original, because the in-place map is what removes that. To restore Divi, delete this section\'s line from “Sections replaced in place”.', 'kalynyuk' ),
				),
				array(
					'key'          => 'field_ak_about_intro',
					'label'        => __( 'Intro (right of the heading)', 'kalynyuk' ),
					'name'         => 'ak_about_intro',
					'type'         => 'textarea',
					'rows'         => 2,
				),
				array(
					'key'          => 'field_ak_about_items',
					'label'        => __( 'Credentials (the 01–04 rows)', 'kalynyuk' ),
					'name'         => 'ak_about_items',
					'type'         => 'repeater',
					'layout'       => 'block',
					'button_label' => __( 'Add credential', 'kalynyuk' ),
					'instructions' => __( 'The numbers are generated from the row order — never type them. Add or remove rows freely; the design shows four but nothing is hard-coded to four.', 'kalynyuk' ),
					'sub_fields'   => array(
						array(
							'key'   => 'field_ak_about_item_label',
							'label' => __( 'Label', 'kalynyuk' ),
							'name'  => 'label',
							'type'  => 'text',
						),
						array(
							'key'          => 'field_ak_about_item_body',
							'label'        => __( 'Body', 'kalynyuk' ),
							'name'         => 'body',
							'type'         => 'wysiwyg',
							'media_upload' => 0,
							'tabs'         => 'visual',
							'toolbar'      => 'basic',
							'instructions' => __( 'Bold the facts a reader scans for — the company name, the regulator, the licence number.', 'kalynyuk' ),
						),
						array(
							'key'          => 'field_ak_about_item_cta_label',
							'label'        => __( 'Button label (optional)', 'kalynyuk' ),
							'name'         => 'cta_label',
							'type'         => 'text',
							'instructions' => __( 'Only the licence row has one in the design. Leave both button fields empty for a row with no action.', 'kalynyuk' ),
						),
						array(
							'key'   => 'field_ak_about_item_cta_url',
							'label' => __( 'Button URL (optional)', 'kalynyuk' ),
							'name'  => 'cta_url',
							'type'  => 'url',
						),
					),
				),
				array(
					'key'          => 'field_ak_about_bio_heading',
					'label'        => __( 'Bio heading', 'kalynyuk' ),
					'name'         => 'ak_about_bio_heading',
					'type'         => 'textarea',
					'rows'         => 2,
					'instructions' => __( 'The second half of the section — “Досвід, якому можна довіряти”. Leave empty to render only the credentials list.', 'kalynyuk' ),
				),
				array(
					'key'   => 'field_ak_about_bio_cta_label',
					'label' => __( 'Bio button — label', 'kalynyuk' ),
					'name'  => 'ak_about_bio_cta_label',
					'type'  => 'text',
				),
				array(
					'key'           => 'field_ak_about_bio_cta_page',
					'label'         => __( 'Bio button — target', 'kalynyuk' ),
					'name'          => 'ak_about_bio_cta_page',
					'type'          => 'post_object',
					'post_type'     => array( 'page' ),
					'return_format' => 'id',
					'allow_null'    => 1,
					'ui'            => 1,
				),
				array(
					'key'           => 'field_ak_about_portrait',
					'label'         => __( 'Portrait', 'kalynyuk' ),
					'name'          => 'ak_about_portrait',
					'type'          => 'image',
					'return_format' => 'id',
					'preview_size'  => 'medium',
					'instructions'  => __( 'Square crop, bottom of the left column. Cropped tight on the face by CSS — see design.md §7 “Portrait crop”.', 'kalynyuk' ),
				),
				array(
					'key'          => 'field_ak_about_blocks',
					'label'        => __( 'Bio prose blocks', 'kalynyuk' ),
					'name'         => 'ak_about_blocks',
					'type'         => 'repeater',
					'layout'       => 'block',
					'button_label' => __( 'Add block', 'kalynyuk' ),
					'instructions' => __( 'The right column — “Освіта та досвід” and “Місія” in the design. A hairline is drawn between blocks automatically; do not add one.', 'kalynyuk' ),
					'sub_fields'   => array(
						array(
							'key'   => 'field_ak_about_block_heading',
							'label' => __( 'Heading', 'kalynyuk' ),
							'name'  => 'heading',
							'type'  => 'text',
						),
						array(
							'key'          => 'field_ak_about_block_body',
							'label'        => __( 'Body', 'kalynyuk' ),
							'name'         => 'body',
							'type'         => 'wysiwyg',
							'media_upload' => 0,
							'tabs'         => 'visual',
							'toolbar'      => 'basic',
						),
					),
				),
			),
		)
	);

	acf_add_local_field_group(
		array(
			'key'      => 'group_ak_cta',
			'title'    => __( 'CTA banner', 'kalynyuk' ),
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
					'key'          => 'field_ak_cta_heading',
					'label'        => __( 'Heading', 'kalynyuk' ),
					'name'         => 'ak_cta_heading',
					'type'         => 'textarea',
					'rows'         => 4,
					'instructions' => __( 'The whole banner. Leave empty and the section renders NOTHING — it does not fall back to Divi; remove its line from “Sections replaced in place” for that. The button is NOT edited here: it is the shared site CTA, so the header, hero and this banner always agree (header-standard). Its label lives in Polylang → Strings, its URL on the Site chrome options page.', 'kalynyuk' ),
				),
				array(
					'key'           => 'field_ak_cta_image',
					'label'         => __( 'Background image', 'kalynyuk' ),
					'name'          => 'ak_cta_image',
					'type'          => 'image',
					'return_format' => 'id',
					'preview_size'  => 'medium',
					'instructions'  => __( 'Wide, and it will be darkened — the heading sits on top of it in cream. A busy or bright photo will fight the text even with the scrim.', 'kalynyuk' ),
				),
			),
		)
	);

	acf_add_local_field_group(
		array(
			'key'      => 'group_ak_services',
			'title'    => __( 'Services grid', 'kalynyuk' ),
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
					'key'          => 'field_ak_services_heading',
					'label'        => __( 'Heading', 'kalynyuk' ),
					'name'         => 'ak_services_heading',
					'type'         => 'textarea',
					'rows'         => 2,
					'instructions' => __( 'Centred above the grid. Leave empty and the section renders NOTHING — it does not fall back to the Divi original, because the in-place map is what removes that. To restore Divi, delete this section\'s line from “Sections replaced in place”.', 'kalynyuk' ),
				),
				array(
					'key'   => 'field_ak_services_intro',
					'label' => __( 'Intro', 'kalynyuk' ),
					'name'  => 'ak_services_intro',
					'type'  => 'textarea',
					'rows'  => 3,
				),
				array(
					'key'          => 'field_ak_services_items',
					'label'        => __( 'Cards', 'kalynyuk' ),
					'name'         => 'ak_services_items',
					'type'         => 'repeater',
					'layout'       => 'block',
					'button_label' => __( 'Add card', 'kalynyuk' ),
					'instructions' => __( 'The grid is 3 across on desktop and fills in reading order, so the order here IS the layout. The design alternates service cards with illustration cards — positions 3, 4 and 8 are illustrations. Nothing enforces that; it is a rhythm, and you can change it by reordering.', 'kalynyuk' ),
					'sub_fields'   => array(
						array(
							'key'           => 'field_ak_services_item_type',
							'label'         => __( 'Card type', 'kalynyuk' ),
							'name'          => 'type',
							'type'          => 'select',
							'choices'       => array(
								'text'  => __( 'Service — icon, title, description', 'kalynyuk' ),
								'media' => __( 'Illustration only', 'kalynyuk' ),
							),
							'default_value' => 'text',
							'return_format' => 'value',
						),
						array(
							'key'               => 'field_ak_services_item_icon',
							'label'             => __( 'Icon', 'kalynyuk' ),
							'name'              => 'icon',
							'type'              => 'image',
							'return_format'     => 'id',
							'preview_size'      => 'thumbnail',
							'instructions'      => __( '48×48 SVG from the Vuesax linear set, to match the ones already in the library.', 'kalynyuk' ),
							'conditional_logic' => array(
								array(
									array(
										'field'    => 'field_ak_services_item_type',
										'operator' => '==',
										'value'    => 'text',
									),
								),
							),
						),
						array(
							'key'               => 'field_ak_services_item_title',
							'label'             => __( 'Title', 'kalynyuk' ),
							'name'              => 'title',
							'type'              => 'text',
							'conditional_logic' => array(
								array(
									array(
										'field'    => 'field_ak_services_item_type',
										'operator' => '==',
										'value'    => 'text',
									),
								),
							),
						),
						array(
							'key'               => 'field_ak_services_item_body',
							'label'             => __( 'Description', 'kalynyuk' ),
							'name'              => 'body',
							'type'              => 'textarea',
							'rows'              => 3,
							'conditional_logic' => array(
								array(
									array(
										'field'    => 'field_ak_services_item_type',
										'operator' => '==',
										'value'    => 'text',
									),
								),
							),
						),
						array(
							'key'               => 'field_ak_services_item_image',
							'label'             => __( 'Illustration', 'kalynyuk' ),
							'name'              => 'image',
							'type'              => 'image',
							'return_format'     => 'id',
							'preview_size'      => 'medium',
							'instructions'      => __( 'Drawn centred inside the card, which is transparent with a hairline rather than filled.', 'kalynyuk' ),
							'conditional_logic' => array(
								array(
									array(
										'field'    => 'field_ak_services_item_type',
										'operator' => '==',
										'value'    => 'media',
									),
								),
							),
						),
					),
				),
			),
		)
	);

	acf_add_local_field_group(
		array(
			'key'      => 'group_ak_trust',
			'title'    => __( 'Why us (accordion)', 'kalynyuk' ),
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
					'key'          => 'field_ak_trust_heading',
					'label'        => __( 'Heading', 'kalynyuk' ),
					'name'         => 'ak_trust_heading',
					'type'         => 'textarea',
					'rows'         => 2,
					'instructions' => __( 'Centred above the two columns. Leave empty and the section renders NOTHING — it does not fall back to the Divi original, because the in-place map is what removes that. To restore Divi, delete this section\'s line from “Sections replaced in place”.', 'kalynyuk' ),
				),
				array(
					'key'           => 'field_ak_trust_image',
					'label'         => __( 'Photo', 'kalynyuk' ),
					'name'          => 'ak_trust_image',
					'type'          => 'image',
					'return_format' => 'id',
					'preview_size'  => 'medium',
					'instructions'  => __( 'The left column, roughly a 680×790 portrait crop.', 'kalynyuk' ),
				),
				array(
					'key'          => 'field_ak_trust_items',
					'label'        => __( 'Questions', 'kalynyuk' ),
					'name'         => 'ak_trust_items',
					'type'         => 'repeater',
					'layout'       => 'block',
					'button_label' => __( 'Add question', 'kalynyuk' ),
					'instructions' => __( 'THE FIRST ONE IS OPEN when the page loads, and opening any other closes it — that is the browser doing it, not a setting, so it follows the order here automatically. Put the answer you most want read first.', 'kalynyuk' ),
					'sub_fields'   => array(
						array(
							'key'   => 'field_ak_trust_item_question',
							'label' => __( 'Question', 'kalynyuk' ),
							'name'  => 'question',
							'type'  => 'text',
						),
						array(
							'key'          => 'field_ak_trust_item_answer',
							'label'        => __( 'Answer', 'kalynyuk' ),
							'name'         => 'answer',
							'type'         => 'wysiwyg',
							'media_upload' => 0,
							'tabs'         => 'visual',
							'toolbar'      => 'basic',
						),
					),
				),
			),
		)
	);

	acf_add_local_field_group(
		array(
			'key'      => 'group_ak_order',
			'title'    => __( 'Book a consultation', 'kalynyuk' ),
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
					'key'          => 'field_ak_order_heading',
					'label'        => __( 'Heading', 'kalynyuk' ),
					'name'         => 'ak_order_heading',
					'type'         => 'textarea',
					'rows'         => 2,
					'instructions' => __( 'Sits on the page ABOVE the green card, not inside it. Leave empty and the section renders NOTHING — it does not fall back to the Divi original, because the in-place map is what removes that. To restore Divi, delete this section\'s line from “Sections replaced in place”.', 'kalynyuk' ),
				),
				array(
					'key'   => 'field_ak_order_intro',
					'label' => __( 'Intro', 'kalynyuk' ),
					'name'  => 'ak_order_intro',
					'type'  => 'textarea',
					'rows'  => 4,
				),
				array(
					'key'          => 'field_ak_order_card_heading',
					'label'        => __( 'Card heading', 'kalynyuk' ),
					'name'         => 'ak_order_card_heading',
					'type'         => 'text',
					'instructions' => __( 'Inside the green card, above the checklist — “Вам до мене, якщо:”.', 'kalynyuk' ),
				),
				array(
					'key'           => 'field_ak_order_image',
					'label'         => __( 'Photo', 'kalynyuk' ),
					'name'          => 'ak_order_image',
					'type'          => 'image',
					'return_format' => 'id',
					'preview_size'  => 'medium',
					'instructions'  => __( 'The right half of the green card, cropped to a tall portrait. A landscape photo works — it is centre-cropped.', 'kalynyuk' ),
				),
				array(
					'key'          => 'field_ak_order_items',
					'label'        => __( 'Checklist', 'kalynyuk' ),
					'name'         => 'ak_order_items',
					'type'         => 'repeater',
					'layout'       => 'block',
					'button_label' => __( 'Add reason', 'kalynyuk' ),
					'instructions' => __( 'The check mark is drawn by the theme — do not type ✅ into the title.', 'kalynyuk' ),
					'sub_fields'   => array(
						array(
							'key'   => 'field_ak_order_item_title',
							'label' => __( 'Title', 'kalynyuk' ),
							'name'  => 'title',
							'type'  => 'text',
						),
						array(
							'key'   => 'field_ak_order_item_body',
							'label' => __( 'Description', 'kalynyuk' ),
							'name'  => 'body',
							'type'  => 'textarea',
							'rows'  => 3,
						),
					),
				),
			),
		)
	);

	acf_add_local_field_group(
		array(
			'key'      => 'group_ak_testimonials',
			'title'    => __( 'Testimonials (Google reviews)', 'kalynyuk' ),
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
					'key'          => 'field_ak_testimonials_heading',
					'label'        => __( 'Heading', 'kalynyuk' ),
					'name'         => 'ak_testimonials_heading',
					'type'         => 'text',
					'instructions' => __( 'Leave empty and the section renders NOTHING — it does not fall back to the Divi original, because the in-place map is what removes that. To restore Divi, delete this section\'s line from “Sections replaced in place”. This is a PER-PAGE field, so every language has its own; Review Wall\'s own title was one global setting shared by all four.', 'kalynyuk' ),
				),
				array(
					'key'           => 'field_ak_testimonials_page',
					'label'         => __( '“All reviews” target', 'kalynyuk' ),
					'name'          => 'ak_testimonials_page',
					'type'          => 'post_object',
					'post_type'     => array( 'page' ),
					'return_format' => 'id',
					'allow_null'    => 1,
					'ui'            => 1,
					'instructions'  => __( 'Usually the Відгуки page. The label itself is a Polylang string (ak_reviews_all), not a field, so it is translated in Translations → Strings.', 'kalynyuk' ),
				),
				array(
					'key'           => 'field_ak_testimonials_limit',
					'label'         => __( 'How many reviews', 'kalynyuk' ),
					'name'          => 'ak_testimonials_limit',
					'type'          => 'number',
					'min'           => 1,
					'default_value' => 9,
					'instructions'  => __( 'Newest first, from Review Wall\'s own table. Three are visible at a time; the rest are reachable with the arrows.', 'kalynyuk' ),
				),
			),
		)
	);

	acf_add_local_field_group(
		array(
			'key'      => 'group_ak_faq',
			'title'    => __( 'FAQ', 'kalynyuk' ),
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
					'key'          => 'field_ak_faq_heading',
					'label'        => __( 'Heading', 'kalynyuk' ),
					'name'         => 'ak_faq_heading',
					'type'         => 'textarea',
					'rows'         => 2,
					'instructions' => __( 'Left column. Leave empty and the section renders NOTHING — it does not fall back to the Divi original, because the in-place map is what removes that. To restore Divi, delete this section\'s line from “Sections replaced in place”.', 'kalynyuk' ),
				),
				array(
					'key'   => 'field_ak_faq_intro',
					'label' => __( 'Intro', 'kalynyuk' ),
					'name'  => 'ak_faq_intro',
					'type'  => 'textarea',
					'rows'  => 3,
				),
				array(
					'key'           => 'field_ak_faq_page',
					'label'         => __( '“All questions” target', 'kalynyuk' ),
					'name'          => 'ak_faq_page',
					'type'          => 'post_object',
					'post_type'     => array( 'page' ),
					'return_format' => 'id',
					'allow_null'    => 1,
					'ui'            => 1,
					'instructions'  => __( 'The FAQ page. The label is a Polylang string (ak_faq_all), translated in Translations → Strings.', 'kalynyuk' ),
				),
				array(
					'key'          => 'field_ak_faq_items',
					'label'        => __( 'Questions', 'kalynyuk' ),
					'name'         => 'ak_faq_items',
					'type'         => 'repeater',
					'layout'       => 'block',
					'button_label' => __( 'Add question', 'kalynyuk' ),
					'instructions' => __( 'THE FIRST ONE IS OPEN on load, and opening any other closes it — the browser does that, not a setting, so it follows this order automatically.', 'kalynyuk' ),
					'sub_fields'   => array(
						array(
							'key'   => 'field_ak_faq_item_question',
							'label' => __( 'Question', 'kalynyuk' ),
							'name'  => 'question',
							'type'  => 'text',
						),
						array(
							'key'          => 'field_ak_faq_item_answer',
							'label'        => __( 'Answer', 'kalynyuk' ),
							'name'         => 'answer',
							'type'         => 'wysiwyg',
							'media_upload' => 0,
							'tabs'         => 'visual',
							'toolbar'      => 'basic',
						),
					),
				),
			),
		)
	);

	acf_add_local_field_group(
		array(
			'key'      => 'group_ak_seo',
			'title'    => __( 'SEO copy block', 'kalynyuk' ),
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
					'key'          => 'field_ak_seo_heading',
					'label'        => __( 'Heading', 'kalynyuk' ),
					'name'         => 'ak_seo_heading',
					'type'         => 'textarea',
					'rows'         => 2,
					'instructions' => __( 'Left column. Leave empty and the section renders NOTHING. Unlike the other sections this one has no Divi original at all — it is appended via “Sections appended at the end”.', 'kalynyuk' ),
				),
				array(
					'key'          => 'field_ak_seo_items',
					'label'        => __( 'Blocks', 'kalynyuk' ),
					'name'         => 'ak_seo_items',
					'type'         => 'repeater',
					'layout'       => 'block',
					'button_label' => __( 'Add block', 'kalynyuk' ),
					'instructions' => __( 'The right column — a sub-heading and a paragraph or two each. This is the page\'s long-form SEO copy, so write for a reader first; the headings become H3s under the section H2.', 'kalynyuk' ),
					'sub_fields'   => array(
						array(
							'key'   => 'field_ak_seo_item_heading',
							'label' => __( 'Sub-heading', 'kalynyuk' ),
							'name'  => 'heading',
							'type'  => 'text',
						),
						array(
							'key'          => 'field_ak_seo_item_body',
							'label'        => __( 'Body', 'kalynyuk' ),
							'name'         => 'body',
							'type'         => 'wysiwyg',
							'media_upload' => 0,
							'tabs'         => 'visual',
							'toolbar'      => 'basic',
						),
					),
				),
			),
		)
	);

	acf_add_local_field_group(
		array(
			'key'      => 'group_ak_calculator',
			'title'    => __( 'Calculator', 'kalynyuk' ),
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
					'key'          => 'field_ak_calc_heading',
					'label'        => __( 'Heading', 'kalynyuk' ),
					'name'         => 'ak_calc_heading',
					'type'         => 'text',
					'instructions' => __( 'Rendered above the calculator. Leave empty to hide the whole heading block.', 'kalynyuk' ),
				),
				array(
					'key'   => 'field_ak_calc_intro',
					'label' => __( 'Intro', 'kalynyuk' ),
					'name'  => 'ak_calc_intro',
					'type'  => 'textarea',
					'rows'  => 3,
				),
				array(
					// Named *_content so it cannot be confused with the Polylang string
					// `ak_calc_help`, which is the LINK LABEL, not the panel body.
					'key'          => 'field_ak_calc_help_content',
					'label'        => __( 'Help panel ("Довідка")', 'kalynyuk' ),
					'name'         => 'ak_calc_help_content',
					'type'         => 'wysiwyg',
					'media_upload' => 0,
					'tabs'         => 'visual',
					'instructions' => __( 'The explanations shown when a visitor opens “Довідка”. Moved out of the Divi popup so it stays editable here rather than in a builder module.', 'kalynyuk' ),
				),
				array(
					'key'           => 'field_ak_calc_indexante',
					'label'         => __( 'Indexante (Euribor), % — used for the variable rate', 'kalynyuk' ),
					'name'          => 'ak_calc_indexante',
					'type'          => 'number',
					'step'          => '0.001',
					'min'           => 0,
					'default_value' => 2.143,
					'instructions'  => __( 'The index the variable rate is built on: rate = indexante + spread. It used to be HARDCODED in the JavaScript at 2.143%, which is why the results drifted as Euribor moved — the number could only be changed by a developer. Update it here whenever the reference index changes. Note it is also involved in the DSTI figure, which is under review.', 'kalynyuk' ),
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

/**
 * About / credentials data for the current page, or null when there is nothing to render.
 *
 * ⚠️ THIS SECTION REPLACES ONE DIVI SECTION THAT HELD TWO VISUAL BLOCKS, and the template
 * keeps them as two so either can be empty:
 *
 *   1. the lede + the 01–04 credential rows      (Figma 1130:8820)
 *   2. the bio — heading, CTA, portrait, prose   (Figma 1173:998 + 1136:9157 + 1130:4797)
 *
 * They are ONE section rather than two because the de-Divi mechanism addresses whole
 * `[et_pb_section]` blocks: `module_id="about"` covers both blocks, so splitting them into
 * two registry slugs would mean one slug renders and the other has no Divi section left to
 * replace. See ak_inline_section_map().
 *
 * The heading is the gate for the whole section, mirroring ak_hero_data() — an empty
 * heading means "not migrated yet", and the Divi original must come back rather than a
 * bare cream band appearing.
 *
 * @return array|null
 */
function ak_about_data() {
	$id = (int) get_queried_object_id();

	if ( ! $id ) {
		return null;
	}

	$heading = (string) ak_section_field( 'ak_about_heading', $id );

	if ( '' === trim( $heading ) ) {
		return null;
	}

	$bio_cta_id  = (int) ak_section_field( 'ak_about_bio_cta_page', $id );
	$bio_cta_lbl = (string) ak_section_field( 'ak_about_bio_cta_label', $id );

	return array(
		'heading'      => $heading,
		'intro'        => (string) ak_section_field( 'ak_about_intro', $id ),
		'items'        => ak_section_rows( 'ak_about_items', array( 'label', 'body', 'cta_label', 'cta_url' ), $id ),
		'bio_heading'  => (string) ak_section_field( 'ak_about_bio_heading', $id ),
		'bio_cta'      => ( $bio_cta_id && $bio_cta_lbl )
			? array(
				'label' => $bio_cta_lbl,
				'url'   => get_permalink( ak_translate_id( $bio_cta_id ) ),
			)
			: null,
		// An attachment ID run through ak_translate_id(), same as the hero image — picks up
		// a per-language media item where Polylang has one, returns the same ID where not.
		'portrait'     => ak_translate_id( (int) ak_section_field( 'ak_about_portrait', $id ) ),
		'blocks'       => ak_section_rows( 'ak_about_blocks', array( 'heading', 'body' ), $id ),
	);
}

/**
 * CTA banner data for the current page, or null when there is nothing to render.
 *
 * ⚠️ THE BUTTON IS NOT A FIELD. It is ak_primary_cta(), the shared site CTA — the same
 * one the header, the hero and the footer use. header-standard requires those to be one
 * funnel, and three separately-editable labels is exactly how they drift apart. It is
 * also why this section needs no translation work for the button: the label is a Polylang
 * string and the URL is chrome config, both already localised.
 *
 * @return array|null
 */
function ak_cta_data() {
	$id = (int) get_queried_object_id();

	if ( ! $id ) {
		return null;
	}

	$heading = (string) ak_section_field( 'ak_cta_heading', $id );

	// The banner IS the heading — without it there is nothing to show but a dark box, so
	// render nothing and let the Divi original stand.
	if ( '' === trim( $heading ) ) {
		return null;
	}

	return array(
		'heading' => $heading,
		'image'   => ak_translate_id( (int) ak_section_field( 'ak_cta_image', $id ) ),
		'cta'     => ak_primary_cta(),
	);
}

/**
 * Services-grid data for the current page, or null when there is nothing to render.
 *
 * ⚠️ THE GRID MIXES TWO CARD SHAPES and they are ONE repeater, not two. The design lays
 * out six service cards and three illustration cards in a single 3×3 grid, and their
 * ORDER is the composition — illustrations sit at positions 3, 4 and 8 so the grid reads
 * as a checkerboard rather than as "cards, then pictures". Two separate repeaters could
 * not express that interleaving without a third field to merge them.
 *
 * The `type` sub-field therefore decides which shape a row renders as, and ACF's
 * conditional logic hides the fields that do not apply. Rows with no usable content are
 * dropped in the template rather than here, so a half-filled row is visibly missing in
 * the grid instead of silently shifting every card after it.
 *
 * @return array|null
 */
function ak_services_data() {
	$id = (int) get_queried_object_id();

	if ( ! $id ) {
		return null;
	}

	$heading = (string) ak_section_field( 'ak_services_heading', $id );

	if ( '' === trim( $heading ) ) {
		return null;
	}

	$items = ak_section_rows( 'ak_services_items', array( 'type', 'icon', 'title', 'body', 'image' ), $id );

	foreach ( $items as $i => $item ) {
		// Media rows carry an attachment ID that Polylang may have a per-language twin for;
		// icons go through the same call so a localised icon set would work too.
		$items[ $i ]['icon']  = ak_translate_id( (int) $item['icon'] );
		$items[ $i ]['image'] = ak_translate_id( (int) $item['image'] );
	}

	return array(
		'heading' => $heading,
		'intro'   => (string) ak_section_field( 'ak_services_intro', $id ),
		'items'   => $items,
	);
}

/**
 * "Why us" accordion data for the current page, or null when there is nothing to render.
 *
 * @return array|null
 */
function ak_trust_data() {
	$id = (int) get_queried_object_id();

	if ( ! $id ) {
		return null;
	}

	$heading = (string) ak_section_field( 'ak_trust_heading', $id );

	if ( '' === trim( $heading ) ) {
		return null;
	}

	return array(
		'heading' => $heading,
		'image'   => ak_translate_id( (int) ak_section_field( 'ak_trust_image', $id ) ),
		'items'   => ak_section_rows( 'ak_trust_items', array( 'question', 'answer' ), $id ),
	);
}

/**
 * "Book a consultation" data for the current page, or null when there is nothing to render.
 *
 * The button is ak_primary_cta() — the shared site CTA, as everywhere else. See
 * ak_cta_data() for why that is not a per-section field.
 *
 * @return array|null
 */
function ak_order_data() {
	$id = (int) get_queried_object_id();

	if ( ! $id ) {
		return null;
	}

	$heading = (string) ak_section_field( 'ak_order_heading', $id );

	if ( '' === trim( $heading ) ) {
		return null;
	}

	return array(
		'heading'      => $heading,
		'intro'        => (string) ak_section_field( 'ak_order_intro', $id ),
		'card_heading' => (string) ak_section_field( 'ak_order_card_heading', $id ),
		'image'        => ak_translate_id( (int) ak_section_field( 'ak_order_image', $id ) ),
		'items'        => ak_section_rows( 'ak_order_items', array( 'title', 'body' ), $id ),
		'cta'          => ak_primary_cta(),
	);
}

/**
 * Google reviews, read from the Review Wall plugin's own table.
 *
 * ⚠️ WHY THIS SECTION WAS REBUILT AT ALL, since the plugin already renders one: Review
 * Wall stores its heading and its button label as `get_option()` — ONE global string for
 * the whole site. On a four-language Polylang install that is not a limitation to work
 * around, it is a defect with no setting to fix: the shipped values are
 * "Подивіться, що кажуть наші клієнти" and "Review us on Google", and every language gets
 * both regardless. Its shortcode takes no arguments either.
 *
 * So the split is: the plugin keeps owning the DATA (it syncs from Google and refreshes
 * on its own schedule — duplicating that into ACF would mean reviews going stale the day
 * someone forgets), and the theme owns every string around it. Heading is a per-page
 * field, the button label is a Polylang string, and both are therefore per-language.
 *
 * ⚠️ THE COUPLING IS DELIBERATE AND NARROW. We touch exactly one public method,
 * `get_google_reviews()`, and read four columns off it. If the plugin is deactivated the
 * class is simply gone and the section renders its chrome with no cards rather than
 * fatally — which is why the class_exists() guard is not optional.
 *
 * @return array|null
 */
function ak_testimonials_data() {
	$id = (int) get_queried_object_id();

	if ( ! $id ) {
		return null;
	}

	$heading = (string) ak_section_field( 'ak_testimonials_heading', $id );

	if ( '' === trim( $heading ) ) {
		return null;
	}

	$reviews = array();

	if ( class_exists( 'Review_Wall_Helper' ) ) {
		$limit = (int) ak_section_field( 'ak_testimonials_limit', $id );
		$limit = $limit > 0 ? $limit : 9;
		$rows  = (array) Review_Wall_Helper::get_db_instance()->get_google_reviews();

		foreach ( array_slice( $rows, 0, $limit ) as $row ) {
			$reviews[] = array(
				'author' => (string) $row->review_author,
				'text'   => (string) $row->review_content,
				'rating' => (float) $row->review_rating,
				/*
				 * The column holds an attachment ID, and "0" for the reviewers Google has no
				 * photo for — 2 of the 20 currently. Cast and let the template fall back to
				 * an initial rather than rendering a broken image.
				 */
				'photo'  => (int) $row->review_author_photo,
			);
		}
	}

	$page_id = (int) ak_section_field( 'ak_testimonials_page', $id );

	return array(
		'heading' => $heading,
		'reviews' => $reviews,
		'all_url' => $page_id ? get_permalink( ak_translate_id( $page_id ) ) : '',
	);
}

/**
 * FAQ data for the current page, or null when there is nothing to render.
 *
 * Same native-<details> accordion as `trust`; see that template for why the behaviour is
 * the browser's rather than ours.
 *
 * @return array|null
 */
function ak_faq_data() {
	$id = (int) get_queried_object_id();

	if ( ! $id ) {
		return null;
	}

	$heading = (string) ak_section_field( 'ak_faq_heading', $id );

	if ( '' === trim( $heading ) ) {
		return null;
	}

	$page_id = (int) ak_section_field( 'ak_faq_page', $id );

	return array(
		'heading' => $heading,
		'intro'   => (string) ak_section_field( 'ak_faq_intro', $id ),
		'items'   => ak_section_rows( 'ak_faq_items', array( 'question', 'answer' ), $id ),
		'all_url' => $page_id ? get_permalink( ak_translate_id( $page_id ) ) : '',
	);
}

/**
 * SEO copy-block data for the current page, or null when there is nothing to render.
 *
 * ⚠️ THE ONE SECTION WITH NO DIVI ORIGINAL. It is new in the redesign, so it is placed by
 * `ak_append_sections` rather than by replacing anything — see ak_append_section_slugs().
 *
 * @return array|null
 */
function ak_seo_data() {
	$id = (int) get_queried_object_id();

	if ( ! $id ) {
		return null;
	}

	$heading = (string) ak_section_field( 'ak_seo_heading', $id );

	if ( '' === trim( $heading ) ) {
		return null;
	}

	return array(
		'heading' => $heading,
		'items'   => ak_section_rows( 'ak_seo_items', array( 'heading', 'body' ), $id ),
	);
}
