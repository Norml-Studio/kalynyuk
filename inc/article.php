<?php
/**
 * Article layer — the shared machinery behind the "Single article" design
 * (Figma 1139:10037).
 *
 * Deliberately post-type agnostic. Petr's note when commissioning it was that the
 * BLOG will use this same layout, so nothing here says `guide`: `single-guide.php`
 * is a four-line file that calls these parts, and a future `single.php` will be the
 * same four lines. Anything that must differ per type goes in ONE place —
 * ak_article_topic() — rather than forking the template.
 *
 * @package kalynyuk
 */

defined( 'ABSPATH' ) || exit;

/**
 * Reading time in whole minutes.
 *
 * 200 wpm is the usual desk figure for adult silent reading of non-technical prose.
 * Rounded UP and floored at 1, because "0 хвилин" is nonsense and a stub article
 * should still say something.
 *
 * ⚠️ Counts words with a Unicode-aware split. `str_word_count()` is byte-based and
 * returns garbage on Cyrillic — it would have reported ~0 for every article on this
 * site, which is exactly the sort of bug that survives to production because the
 * number looks plausible.
 *
 * @param string $content Raw post content.
 * @return int
 */
function ak_reading_time( $content ) {
	$text  = wp_strip_all_tags( strip_shortcodes( (string) $content ) );
	$words = preg_split( '/[\s\p{Z}]+/u', trim( $text ), -1, PREG_SPLIT_NO_EMPTY );

	return max( 1, (int) ceil( count( (array) $words ) / 200 ) );
}

/**
 * Localised "N minutes" for the meta line.
 *
 * ⚠️ THREE FORMS, NOT ONE. The first version had a single "%d хвилин" and rendered
 * "1 хвилин" on a short article — wrong Ukrainian, and the kind of error that reads
 * as machine-generated. Ukrainian and Russian both take one/few/many, so the string
 * is registered three times and picked with the Slavic rule.
 *
 * Languages with a simpler rule still work: a translator sets `few` and `many` to the
 * same plural wording, and `one` to the singular — English "1 minute" / "5 minutes"
 * comes out correct with no special case. Polylang has no plural support for
 * registered strings, so doing it here is the only way to keep it editable in
 * wp-admin rather than locked in a .po file.
 *
 * @param int $minutes Minutes.
 * @return string
 */
function ak_reading_time_label( $minutes ) {
	$minutes = (int) $minutes;

	$mod10  = $minutes % 10;
	$mod100 = $minutes % 100;

	if ( 1 === $mod10 && 11 !== $mod100 ) {
		$form = ak_str( 'ak_read_minutes_one', '%d хвилина' );
	} elseif ( $mod10 >= 2 && $mod10 <= 4 && ( $mod100 < 12 || $mod100 > 14 ) ) {
		$form = ak_str( 'ak_read_minutes_few', '%d хвилини' );
	} else {
		$form = ak_str( 'ak_read_minutes_many', '%d хвилин' );
	}

	/* translators: %d: reading time in minutes. */
	return sprintf( $form, $minutes );
}

/**
 * The category chip above the title, or '' when the post has none.
 *
 * THE ONE PLACE that knows about post types. Guides carry a free-text ACF field
 * because there are four of them and a taxonomy with an archive nobody designed
 * would be scaffolding for its own sake (dev-wp-architecture: pick the smallest
 * primitive that fits). Posts will use the real `category` taxonomy they already
 * have. Add a branch here rather than forking the template.
 *
 * @param int $post_id Post ID.
 * @return string
 */
function ak_article_topic( $post_id ) {
	$post_id = (int) $post_id;

	if ( 'post' === get_post_type( $post_id ) ) {
		$terms = get_the_terms( $post_id, 'category' );

		return ( $terms && ! is_wp_error( $terms ) ) ? $terms[0]->name : '';
	}

	return (string) ak_section_field( 'ak_topic', $post_id );
}

/**
 * Add stable ids to the content's H2s and return both the patched HTML and a
 * table of contents built from them.
 *
 * WHY REGEX AND NOT DOMDocument. The content is a fragment, and DOMDocument on a
 * fragment either wraps it in <html><body> or mangles the encoding of Cyrillic
 * unless coaxed with a meta hint — and it re-serialises the WHOLE body, so a single
 * unusual construct anywhere in a long article can come back changed. Here the
 * target is narrow and well-formed (`<h2 …>` emitted by the editor), the replacement
 * touches only the opening tag, and everything else is passed through untouched.
 * That is the safer trade for a fragment we do not own.
 *
 * An H2 that already carries an id keeps it — hand-written anchors are somebody's
 * decision and must not be renumbered.
 *
 * @param string $html Post content, already filtered.
 * @return array{html:string,toc:array<int,array{id:string,title:string}>}
 */
function ak_article_toc( $html ) {
	$html = (string) $html;
	$toc  = array();
	$seen = array();

	$html = preg_replace_callback(
		'#<h2\b([^>]*)>(.*?)</h2>#is',
		static function ( $m ) use ( &$toc, &$seen ) {
			$attrs = $m[1];
			$title = trim( wp_strip_all_tags( $m[2] ) );

			if ( '' === $title ) {
				return $m[0];
			}

			if ( preg_match( '#\bid=(["\'])(.*?)\1#i', $attrs, $has ) ) {
				$id = $has[2];
			} else {
				$base = sanitize_title( $title );

				/*
				 * ⚠️ sanitize_title() PERCENT-ENCODES Cyrillic. On this site every
				 * heading is Cyrillic, so the first version produced anchors like
				 * `#%d0%bf%d0%be%d0%bc...` — functional, but unreadable and useless
				 * if anyone shares a deep link. Caught by rendering, not by reading
				 * the code: the TOC worked perfectly and the ids looked fine in an
				 * `id` attribute until you actually looked at one.
				 *
				 * Reuse the Ukrainian transliteration table the theme already owns
				 * for Cyr-To-Lat rather than adding a second romanisation, so slugs
				 * and anchors romanise identically. `ctl_table` also picks up the
				 * plugin's own map when it is active.
				 */
				if ( false !== strpos( $base, '%' ) ) {
					$table = (array) apply_filters( 'ctl_table', array() );

					if ( $table ) {
						$base = sanitize_title( strtr( $title, $table ) );
					}
				}

				$base = ( '' !== $base && false === strpos( $base, '%' ) ) ? $base : 'section';
				$id   = $base;
				$n    = 2;

				// Two headings can legitimately share a title; ids may not.
				while ( isset( $seen[ $id ] ) ) {
					$id = $base . '-' . $n;
					++$n;
				}

				$attrs .= ' id="' . esc_attr( $id ) . '"';
			}

			$seen[ $id ] = true;
			$toc[]       = array(
				'id'    => $id,
				'title' => $title,
			);

			return '<h2' . $attrs . '>' . $m[2] . '</h2>';
		},
		$html
	);

	return array(
		'html' => $html,
		'toc'  => $toc,
	);
}

/**
 * Author card data.
 *
 * BOTH the name and the role are translated STRINGS, with the WordPress post author
 * only as a fallback. That looks backwards until you try it the other way round:
 *
 *   · The byline has to be spelled differently per language — "Анна Калинюк" in
 *     Ukrainian and Russian, "Anna Kalynyuk" in Portuguese and English. A WordPress
 *     user has exactly one display name, so it physically cannot do this.
 *   · The WP author here is an agency admin account ("NormlAdmin"). Renaming it to
 *     the client would misattribute every action in the audit log; leaving it shows
 *     the wrong byline. A string sidesteps both.
 *
 * The post author remains the fallback, so if a second contributor is ever added the
 * byline follows them the moment the string is cleared.
 *
 * @param int $post_id Post ID.
 * @return array{name:string,role:string,photo:int}
 */
function ak_article_author( $post_id ) {
	$author_id = (int) get_post_field( 'post_author', $post_id );
	$name      = ak_str( 'ak_author_name', 'Анна Калинюк' );

	return array(
		'name'  => '' !== $name ? $name : (string) get_the_author_meta( 'display_name', $author_id ),
		'role'  => ak_str( 'ak_author_role', 'Засновниця Anna Kalynyuk Mortgage' ),
		'photo' => (int) ak_chrome( 'ak_author_photo', 0 ),
	);
}

/**
 * Sharing targets for the "Поширити статтю" row.
 *
 * ⚠️ Instagram is in the design but has NO web share endpoint — it cannot receive a
 * URL from a browser, by Meta's design. Rendering a dead "Інстаграм" button would be
 * worse than omitting it, so the row ships Facebook, LinkedIn and copy-link, and
 * this comment exists so the omission reads as a decision rather than an oversight.
 *
 * @param string $url   Permalink.
 * @param string $title Post title.
 * @return array<int, array{key:string,label:string,url:string}>
 */
function ak_article_share_links( $url, $title ) {
	return array(
		array(
			'key'   => 'facebook',
			'label' => ak_str( 'ak_share_facebook', 'Фейсбук' ),
			'url'   => 'https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode( $url ),
		),
		array(
			'key'   => 'linkedin',
			'label' => ak_str( 'ak_share_linkedin', 'Лінкедин' ),
			'url'   => 'https://www.linkedin.com/sharing/share-offsite/?url=' . rawurlencode( $url ),
		),
	);
}

/**
 * Sibling articles for the "Інші статті" block — same post type, same language.
 *
 * `lang` is left to Polylang, which scopes WP_Query to the current language on its
 * own; forcing it here would break the moment a language is added.
 *
 * @param int $post_id Current post.
 * @param int $limit   How many.
 * @return WP_Post[]
 */
function ak_article_related( $post_id, $limit = 3 ) {
	return get_posts(
		array(
			'post_type'           => get_post_type( $post_id ),
			'post__not_in'        => array( (int) $post_id ),
			'numberposts'         => (int) $limit,
			'ignore_sticky_posts' => true,
			'orderby'             => 'date',
			'order'               => 'DESC',
		)
	);
}

/**
 * Register the article-side ACF fields.
 */
function ak_acf_article_fields() {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	acf_add_local_field_group(
		array(
			'key'      => 'group_ak_article',
			'title'    => __( 'Article', 'kalynyuk' ),
			'position' => 'side',
			'location' => array(
				array(
					array(
						'param'    => 'post_type',
						'operator' => '==',
						'value'    => 'guide',
					),
				),
			),
			'fields'   => array(
				array(
					'key'          => 'field_ak_topic',
					'label'        => __( 'Topic', 'kalynyuk' ),
					'name'         => 'ak_topic',
					'type'         => 'text',
					'instructions' => __( 'The chip above the title, e.g. “Іпотека”. Leave empty to hide it. Falls back to the default language when a translation leaves it blank.', 'kalynyuk' ),
				),
			),
		)
	);
}
add_action( 'acf/init', 'ak_acf_article_fields' );
