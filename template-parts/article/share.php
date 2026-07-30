<?php
/**
 * Share row — Figma 1144:12374 ("Поширити статтю").
 *
 * ⚠️ INSTAGRAM IS IN THE DESIGN AND IS NOT HERE. Instagram has no web share
 * endpoint — Meta does not accept a URL from a browser, by design — so the button
 * could only ever be decorative. A control that looks live and does nothing is worse
 * than an absent one, so the row ships Facebook, LinkedIn and copy-link. Flagged to
 * Petr rather than silently dropped. See ak_article_share_links().
 *
 * The copy-link button degrades honestly: without `navigator.clipboard` (an insecure
 * origin, e.g. plain-HTTP local dev) it stays a normal link to the article.
 *
 * @package kalynyuk
 */

defined( 'ABSPATH' ) || exit;

$ak_id    = isset( $args['id'] ) ? (int) $args['id'] : get_the_ID();
$ak_url   = get_permalink( $ak_id );
$ak_title = get_the_title( $ak_id );
$ak_links = ak_article_share_links( $ak_url, $ak_title );
?>
<div class="article__share">
	<p class="article__share-title"><?php echo esc_html( ak_str( 'ak_share_title', 'Поширити статтю' ) ); ?></p>

	<ul class="article__share-list">
		<?php foreach ( $ak_links as $ak_link ) : ?>
			<li>
				<a
					class="article__share-link article__share-link--<?php echo esc_attr( $ak_link['key'] ); ?>"
					href="<?php echo esc_url( $ak_link['url'] ); ?>"
					target="_blank"
					rel="noopener noreferrer"
				><?php echo esc_html( $ak_link['label'] ); ?></a>
			</li>
		<?php endforeach; ?>

		<li>
			<a
				class="article__share-link article__share-link--copy"
				href="<?php echo esc_url( $ak_url ); ?>"
				data-ak-copy="<?php echo esc_url( $ak_url ); ?>"
				data-ak-copy-done="<?php echo esc_attr( ak_str( 'ak_share_copied', 'Скопійовано' ) ); ?>"
			><?php echo esc_html( ak_str( 'ak_share_copy', 'Скопіювати посилання' ) ); ?></a>
		</li>
	</ul>
</div>
