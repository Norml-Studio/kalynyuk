<?php
/**
 * "Інші статті" — Figma 1144:12475. Heading 48/600/95%/-4%, then three 448-wide
 * cards: image, topic chip, author, title, date.
 *
 * Renders nothing when there is no sibling. An "Other articles" heading over an
 * empty row is a broken promise; no block at all just reads as the end of the piece.
 *
 * @package kalynyuk
 */

defined( 'ABSPATH' ) || exit;

$ak_id      = isset( $args['id'] ) ? (int) $args['id'] : get_the_ID();
$ak_related = ak_article_related( $ak_id, 3 );

if ( empty( $ak_related ) ) {
	return;
}
?>
<section class="article-related">
	<div class="article-related__inner">
		<h2 class="article-related__title"><?php echo esc_html( ak_str( 'ak_related_title', 'Інші статті' ) ); ?></h2>

		<ul class="article-related__list">
			<?php foreach ( $ak_related as $ak_post ) : ?>
				<?php
				$ak_r_topic = ak_article_topic( $ak_post->ID );
				$ak_r_thumb = (int) get_post_thumbnail_id( $ak_post->ID );
				?>
				<li class="article-related__item">
					<a class="article-related__card" href="<?php echo esc_url( get_permalink( $ak_post ) ); ?>">
						<span class="article-related__figure">
							<?php if ( $ak_r_thumb ) : ?>
								<?php
								echo wp_get_attachment_image(
									$ak_r_thumb,
									'large',
									false,
									array(
										'class' => 'article-related__image',
										'sizes' => '(min-width: 1025px) 448px, 100vw',
									)
								);
								?>
							<?php endif; ?>

							<?php if ( '' !== $ak_r_topic ) : ?>
								<span class="article-related__topic"><?php echo esc_html( $ak_r_topic ); ?></span>
							<?php endif; ?>
						</span>

						<span class="article-related__body">
							<span class="article-related__author"><?php echo esc_html( get_the_author_meta( 'display_name', (int) $ak_post->post_author ) ); ?></span>
							<span class="article-related__heading"><?php echo esc_html( get_the_title( $ak_post ) ); ?></span>
							<time class="article-related__date" datetime="<?php echo esc_attr( get_the_date( 'c', $ak_post ) ); ?>"><?php echo esc_html( get_the_date( '', $ak_post ) ); ?></time>
						</span>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>
