<?php
/**
 * Table of contents — Figma 1139:12016, the 348px left rail beside the 680 prose.
 *
 * Built from the article's H2s at render time rather than hand-maintained: a TOC that
 * an editor has to keep in sync with the headings is a TOC that goes stale on the
 * first edit. ak_article_toc() adds the ids in the same pass, so the two can never
 * disagree.
 *
 * Renders NOTHING below two entries. A one-item contents list is noise, and an empty
 * rail is worse than no rail — the prose then simply keeps its indent.
 *
 * @package kalynyuk
 */

defined( 'ABSPATH' ) || exit;

$ak_toc = isset( $args['toc'] ) ? (array) $args['toc'] : array();

if ( count( $ak_toc ) < 2 ) {
	return;
}
?>
<nav class="article__toc" aria-labelledby="ak-toc-title">
	<div class="article__toc-sticky">
		<p class="article__toc-title" id="ak-toc-title"><?php echo esc_html( ak_str( 'ak_toc_title', 'Зміст:' ) ); ?></p>

		<ol class="article__toc-list">
			<?php foreach ( $ak_toc as $ak_entry ) : ?>
				<li class="article__toc-item">
					<a class="article__toc-link" href="#<?php echo esc_attr( $ak_entry['id'] ); ?>"><?php echo esc_html( $ak_entry['title'] ); ?></a>
				</li>
			<?php endforeach; ?>
		</ol>
	</div>
</nav>
