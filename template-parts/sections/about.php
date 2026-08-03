<?php
/**
 * About / credentials section — Figma frame 1130:3906 ("Main"), band y=932…2732.
 *
 * The band is assembled from FIVE sibling nodes rather than one frame, because the design
 * file positions them absolutely on the page root. Measured (1440 canvas, page-absolute):
 *
 *   lede heading    x=32   y=932   w=518   48/600/95%/-4%   ink
 *   lede intro      x=728  y=938   w=299   20/400/124%/-1%  ink
 *   credential list x=32   y=1080  1376×744 — 1130:8820, 4 rows of 1376×174, gap 16
 *   bio heading     x=32   y=1944  w=355   48/600/95%/-4%   ink   (1173:998)
 *   bio CTA         x=32   y=2068  169×48  accent, radius 24
 *   bio prose       x=728  y=2068  w=680   two blocks + hairline (1136:9157)
 *   portrait        x=32   y=2280  332×332 radius 24        (1130:4797)
 *
 * ⚠️ THE RIGHT-HAND COLUMN STARTS AT x=728 IN BOTH BLOCKS — the lede intro and every row's
 * body share it, and so does the bio prose. That is one grid, not three coincidences, and
 * it is why the row's internal columns are stated as absolute x (64 / 296 / 728) in
 * design.md §7 rather than as offsets inside the row.
 *
 * ⚠️ WHY THE BIO HEADING IS ITS OWN ROW rather than the top of a left column. The bio's
 * prose column starts at y=2068, level with the CTA, NOT with the heading at 1944. Modelled
 * as "heading row, then a two-column grid" that falls out with no offset anywhere; modelled
 * as a two-column grid containing the heading it needs a 124px push on the right column
 * that silently breaks the moment the heading wraps to three lines.
 *
 * The portrait is BOTTOM-aligned with the prose column — both end at y=2612. That is what
 * produces the odd 164px gap under the CTA; it is a consequence, not a spacing decision.
 *
 * @package kalynyuk
 */

defined( 'ABSPATH' ) || exit;

$ak_about = ak_about_data();

if ( ! $ak_about ) {
	return;
}

$ak_h        = ak_claim_h1();
$ak_portrait = (int) $ak_about['portrait'];
?>
<section class="about">
	<div class="about__inner">
		<div class="about__lede">
			<<?php echo $ak_h; ?> class="about__heading"><?php echo esc_html( $ak_about['heading'] ); ?></<?php echo $ak_h; ?>>

			<?php if ( $ak_about['intro'] ) : ?>
				<p class="about__intro"><?php echo esc_html( $ak_about['intro'] ); ?></p>
			<?php endif; ?>
		</div>

		<?php if ( $ak_about['items'] ) : ?>
			<?php
			/*
			 * An <ol> because the rows are an ordered set — the design numbers them 01–04 and
			 * the numbering carries meaning ("four reasons"), so it belongs in the markup, not
			 * only in the paint.
			 *
			 * `role="list"` is NOT redundant: `list-style: none` makes Safari/VoiceOver drop
			 * list semantics entirely, so the role is what keeps "list, 4 items" announced.
			 */
			?>
			<ol class="about__list" role="list">
				<?php foreach ( $ak_about['items'] as $ak_i => $ak_item ) : ?>
					<li class="about__item">
						<?php
						/*
						 * GENERATED from the row order, never stored — an editor cannot get the
						 * sequence wrong by reordering rows, and there is no field to forget to
						 * renumber. aria-hidden because the <ol> already conveys position to a
						 * screen reader; reading "01" aloud would duplicate it.
						 */
						?>
						<span class="about__item-number" aria-hidden="true"><?php echo esc_html( sprintf( '%02d', $ak_i + 1 ) ); ?></span>

						<?php
						/*
						 * ⚠️ THIS WRAPPER EXISTS TO KEEP THE ROW AT THREE GRID ITEMS ON ONE ROW.
						 *
						 * Column 2 holds the label AND the button, stacked. If they were separate
						 * grid items the row would need two rows, and the body in column 3 would
						 * have to SPAN both to stay level with them — which makes the row's height
						 * depend on how grid distributes a spanning item's excess across two
						 * tracks. Since the list also runs `grid-auto-rows: 1fr` to hold all four
						 * rows at one height, that is two height negotiations feeding each other.
						 * One nested box in column 2 removes the spanning item entirely.
						 *
						 * On mobile the wrapper becomes `display: contents` so the button can sit
						 * below the body. See _about.scss.
						 */
						?>
						<div class="about__item-main">
							<?php if ( $ak_item['label'] ) : ?>
								<h3 class="about__item-label"><?php echo esc_html( $ak_item['label'] ); ?></h3>
							<?php endif; ?>

							<?php if ( $ak_item['cta_label'] && $ak_item['cta_url'] ) : ?>
								<a
									class="btn btn--primary about__item-cta"
									href="<?php echo esc_url( $ak_item['cta_url'] ); ?>"
									<?php echo ak_link_target_attrs( $ak_item['cta_url'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								><?php echo esc_html( $ak_item['cta_label'] ); ?></a>
							<?php endif; ?>
						</div>

						<?php if ( $ak_item['body'] ) : ?>
							<?php
							/*
							 * wpautop() ON TOP OF wp_kses_post(), which the calculator's help panel
							 * does not do — and this is the more robust order, not a divergence for
							 * its own sake. The value is seeded with explicit <p> tags, but the
							 * moment an editor opens it in TinyMCE and saves, WordPress stores
							 * blank-line-separated text with the tags stripped. Without wpautop
							 * that silently collapses several paragraphs into one blob. wpautop is
							 * a no-op on content that already has block tags, so it is safe both
							 * ways. kses first because it permits <p> either way.
							 */
							?>
							<div class="about__item-body"><?php echo wpautop( wp_kses_post( $ak_item['body'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- sanitised above. ?></div>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ol>
		<?php endif; ?>

		<?php if ( $ak_about['bio_heading'] || $ak_about['blocks'] || $ak_portrait ) : ?>
			<div class="about__bio">
				<?php if ( $ak_about['bio_heading'] ) : ?>
					<h2 class="about__bio-heading"><?php echo esc_html( $ak_about['bio_heading'] ); ?></h2>
				<?php endif; ?>

				<div class="about__bio-aside">
					<?php if ( $ak_about['bio_cta'] ) : ?>
						<a
							class="btn btn--primary about__bio-cta"
							href="<?php echo esc_url( $ak_about['bio_cta']['url'] ); ?>"
						><?php echo esc_html( $ak_about['bio_cta']['label'] ); ?></a>
					<?php endif; ?>

					<?php if ( $ak_portrait ) : ?>
						<?php
						/*
						 * No explicit `alt` — wp_get_attachment_image() reads the attachment's own
						 * alt text, which Polylang translates with the media. Same reasoning as the
						 * hero: passing a field's copy would freeze one language's alt onto all of
						 * them.
						 *
						 * `large` rather than `full`: the rendered box is 332px wide but the CSS
						 * crop scales the image to ~253% of it, so ~842 CSS px — `large` (1024)
						 * covers that at 1x and the source is a 2x-safe margin above it.
						 */
						?>
						<div class="about__portrait">
							<?php
							echo wp_get_attachment_image(
								$ak_portrait,
								'large',
								false,
								array(
									'class'    => 'about__portrait-img',
									'sizes'    => '(min-width: 1025px) 842px, 100vw',
									'loading'  => 'lazy',
									'decoding' => 'async',
								)
							);
							?>
						</div>
					<?php endif; ?>
				</div>

				<?php if ( $ak_about['blocks'] ) : ?>
					<div class="about__bio-prose">
						<?php foreach ( $ak_about['blocks'] as $ak_block ) : ?>
							<div class="about__block">
								<?php if ( $ak_block['heading'] ) : ?>
									<?php
									/*
									 * TWO-PART HEADING. These read "Label: descriptive tail" — the label
									 * carries the meaning and the tail is a gloss, so the tail drops to
									 * $color-ink-soft while the label stays full strength (Petr,
									 * 2026-08-03).
									 *
									 * Split in PHP rather than asking the editor to mark it up: the split
									 * point is a colon they are already typing, so the effect is automatic
									 * and cannot be applied inconsistently across languages. A heading with
									 * no colon simply renders whole — explode() with a limit of 2 returns a
									 * single element and the span is never emitted.
									 *
									 * The colon stays with the LABEL. The tail keeps its leading space, so
									 * the two spans join into normal text for selection and screen readers.
									 */
									$ak_head = explode( ':', $ak_block['heading'], 2 );
									?>
									<h3 class="about__block-heading">
										<?php echo esc_html( $ak_head[0] ); ?><?php if ( isset( $ak_head[1] ) ) : ?>:<span class="about__block-heading-rest"><?php echo esc_html( $ak_head[1] ); ?></span><?php endif; ?>
									</h3>
								<?php endif; ?>

								<?php if ( $ak_block['body'] ) : ?>
									<div class="about__block-body"><?php echo wpautop( wp_kses_post( $ak_block['body'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- sanitised above. ?></div>
								<?php endif; ?>
							</div>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	</div>
</section>
