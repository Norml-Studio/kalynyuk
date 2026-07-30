<?php
/**
 * Article masthead — Figma 1139:11967.
 *
 * Measured at a 1440 canvas, offsets inside the 1376 container:
 *   breadcrumb   y=0,  16 SemiBold 128% -1%, with a back chevron
 *   topic chip   y=40, 72×32, radius 12, cream fill + 1px ink stroke, 16 SemiBold
 *   title row    y=92, 1376 wide, SPACE_BETWEEN, items bottom-aligned
 *     left       title 48/600/95%/-4% capped at 791, then meta 32 below
 *     right      author card 358 wide, surface fill, radius 24, pad 16 (24 left)
 *   image        1376×600, directly under the row
 *
 * @package kalynyuk
 */

defined( 'ABSPATH' ) || exit;

$ak_id     = isset( $args['id'] ) ? (int) $args['id'] : get_the_ID();
$ak_topic  = ak_article_topic( $ak_id );
$ak_author = ak_article_author( $ak_id );
$ak_mins   = ak_reading_time( get_post_field( 'post_content', $ak_id ) );
$ak_thumb  = (int) get_post_thumbnail_id( $ak_id );

/*
 * The breadcrumb points at the archive this article belongs to. `guide` has
 * has_archive => false (there is no designed guides index), so it goes to the blog
 * page — which is where the design's "Блог" link points too. get_permalink() on the
 * posts page ID is language-aware through Polylang.
 */
$ak_parent_id  = (int) get_option( 'page_for_posts' );
$ak_parent_url = $ak_parent_id ? get_permalink( ak_translate_id( $ak_parent_id ) ) : home_url( '/' );
$ak_parent_ttl = $ak_parent_id ? get_the_title( ak_translate_id( $ak_parent_id ) ) : get_bloginfo( 'name' );
?>
<header class="article__masthead">
	<div class="article__masthead-inner">

		<a class="article__back" href="<?php echo esc_url( $ak_parent_url ); ?>">
			<svg width="16" height="16" viewBox="0 0 16 16" aria-hidden="true" focusable="false">
				<path d="M10 3.5 5.5 8l4.5 4.5" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
			</svg>
			<span><?php echo esc_html( $ak_parent_ttl ); ?></span>
		</a>

		<?php if ( '' !== $ak_topic ) : ?>
			<p class="article__topic"><?php echo esc_html( $ak_topic ); ?></p>
		<?php endif; ?>

		<div class="article__head">
			<div class="article__headings">
				<h1 class="article__title"><?php echo esc_html( get_the_title( $ak_id ) ); ?></h1>

				<p class="article__meta">
					<time datetime="<?php echo esc_attr( get_the_date( 'c', $ak_id ) ); ?>"><?php echo esc_html( get_the_date( '', $ak_id ) ); ?></time>
					<span class="article__meta-dot" aria-hidden="true"></span>
					<span><?php echo esc_html( ak_reading_time_label( $ak_mins ) ); ?></span>
				</p>
			</div>

			<div class="article__author">
				<?php if ( $ak_author['photo'] ) : ?>
					<?php
					echo wp_get_attachment_image(
						$ak_author['photo'],
						'thumbnail',
						false,
						array( 'class' => 'article__author-photo' )
					);
					?>
				<?php endif; ?>

				<div class="article__author-text">
					<?php
					/*
					 * The name is skipped rather than printed empty. Posts created
					 * through WP-CLI can carry post_author = 0, which yields no
					 * display name — an empty <p> would leave a phantom line above
					 * the role and look like a CSS bug rather than missing data.
					 */
					?>
					<?php if ( '' !== $ak_author['name'] ) : ?>
						<p class="article__author-name"><?php echo esc_html( $ak_author['name'] ); ?></p>
					<?php endif; ?>
					<p class="article__author-role"><?php echo esc_html( $ak_author['role'] ); ?></p>
				</div>
			</div>
		</div>
	</div>

	<?php if ( $ak_thumb ) : ?>
		<div class="article__figure">
			<?php
			/*
			 * The LCP element on this page — eager, high priority, and NOT lazy.
			 * sizes matches the 1376 container rather than 100vw: the image is
			 * inside the container, so 100vw would make the browser pick a file
			 * wider than it can ever display.
			 */
			echo wp_get_attachment_image(
				$ak_thumb,
				'full',
				false,
				array(
					'class'         => 'article__image',
					'sizes'         => '(min-width: 1436px) 1376px, 100vw',
					'loading'       => 'eager',
					'fetchpriority' => 'high',
					'decoding'      => 'sync',
				)
			);
			?>
		</div>
	<?php endif; ?>
</header>
