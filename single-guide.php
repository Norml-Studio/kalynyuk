<?php
/**
 * Single guide — the "Single article" layout, Figma 1139:10037.
 *
 * Deliberately thin. Everything here is post-type agnostic and lives in
 * `template-parts/article/*` + `inc/article.php`, because Petr's brief was that the
 * BLOG will use this same layout. A future `single.php` should be a copy of this
 * file with nothing changed but the docblock — if it ever needs more than that,
 * the difference belongs in ak_article_topic(), not in a second template.
 *
 * ⚠️ This template BYPASSES `the_content`'s Divi path on purpose. Guides are plain
 * editor content, not Divi layouts, so there is no builder shortcode to strip and
 * ak_native_sections() has nothing to do here.
 *
 * @package kalynyuk
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();

	$ak_id      = get_the_ID();
	$ak_content = apply_filters( 'the_content', get_the_content() );
	$ak_parsed  = ak_article_toc( $ak_content );
	?>
	<article class="article" id="ak-article">

		<?php
		get_template_part(
			'template-parts/article/masthead',
			null,
			array( 'id' => $ak_id )
		);
		?>

		<div class="article__body">
			<div class="article__inner">

				<?php
				get_template_part(
					'template-parts/article/toc',
					null,
					array( 'toc' => $ak_parsed['toc'] )
				);
				?>

				<div class="article__column">
					<div class="article__prose">
						<?php
						// Already run through `the_content` above; ak_article_toc()
						// only rewrote the opening <h2> tags.
						echo $ak_parsed['html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						?>
					</div>

					<?php
					get_template_part(
						'template-parts/article/share',
						null,
						array( 'id' => $ak_id )
					);
					?>
				</div>
			</div>
		</div>

		<?php
		get_template_part(
			'template-parts/article/related',
			null,
			array( 'id' => $ak_id )
		);
		?>
	</article>
	<?php
endwhile;

get_footer();
