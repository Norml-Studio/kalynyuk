<?php
/**
 * Services grid — Figma frame 1136:9156 ("Frame 9128"), 1376×1305 at x=32 y=3428.
 *
 * MEASURED (1440 canvas):
 *   band       120 top / 50 bottom, 1px #d6d0c6 bottom rule only (the rule ABOVE this
 *              section is the CTA banner's own bottom border)
 *   lede       540 wide, centred — heading 48/600/95%/-4% + intro 20/400/124%/-1%,
 *              both centred, 24 apart
 *   grid       1376×1058, 3 × 3 of 448×342 cards, 16 gap (448·3 + 16·2 = 1376)
 *   service    surface fill, radius 24, NO border, padding 24 — icon 48×48 top-left,
 *              text block pinned to the BOTTOM (24 above the card edge)
 *   title      24/700/116%/-1% ink        body  20/400/124%/-1% ink at 80% opacity
 *   illustr.   TRANSPARENT card with a 1px #d6d0c6 hairline, artwork centred
 *
 * ⚠️ THE TWO CARD SHAPES ARE ONE REPEATER — see ak_services_data() for why the order is
 * the composition and cannot be split into two lists.
 *
 * ⚠️ THE ILLUSTRATION CARDS INVERT THE PANEL RULE, and that is the redesign's whole move
 * here. Everywhere else a panel is FILLED and borderless (design.md §4); these are the
 * reverse — transparent with a hairline — so the artwork sits on the page rather than on
 * a card. The current Divi version fills them like every other card, which is what makes
 * the grid read as nine identical boxes.
 *
 * @package kalynyuk
 */

defined( 'ABSPATH' ) || exit;

$ak_services = ak_services_data();

if ( ! $ak_services ) {
	return;
}
?>
<section class="services">
	<div class="services__inner">
		<div class="services__lede">
			<h2 class="services__heading"><?php echo esc_html( $ak_services['heading'] ); ?></h2>

			<?php if ( $ak_services['intro'] ) : ?>
				<p class="services__intro"><?php echo esc_html( $ak_services['intro'] ); ?></p>
			<?php endif; ?>
		</div>

		<?php if ( $ak_services['items'] ) : ?>
			<ul class="services__grid" role="list">
				<?php foreach ( $ak_services['items'] as $ak_item ) : ?>
					<?php
					$ak_is_media = 'media' === $ak_item['type'];
					$ak_image    = (int) $ak_item['image'];
					$ak_icon     = (int) $ak_item['icon'];

					// Drop a row that would render as an empty box. Done HERE rather than in the
					// data layer so the grid simply has one card fewer, which is visible, instead
					// of an empty cell that looks like a layout bug.
					if ( $ak_is_media ? ! $ak_image : ( '' === trim( (string) $ak_item['title'] ) ) ) {
						continue;
					}
					?>
					<li class="services__card<?php echo $ak_is_media ? ' services__card--media' : ''; ?>">
						<?php if ( $ak_is_media ) : ?>
							<?php
							/*
							 * Decorative — the illustrations carry no information the service cards
							 * do not already state, so the alt is deliberately EMPTY rather than
							 * missing. A missing alt makes a screen reader fall back to the filename.
							 *
							 * `full` because these are SVGs: WordPress generates no intermediate
							 * sizes for them, so any other keyword silently resolves to the original
							 * anyway.
							 */
							echo wp_get_attachment_image(
								$ak_image,
								'full',
								false,
								array(
									'class'    => 'services__art',
									'alt'      => '',
									'loading'  => 'lazy',
									'decoding' => 'async',
								)
							);
							?>
						<?php else : ?>
							<?php if ( $ak_icon ) : ?>
								<?php
								echo wp_get_attachment_image(
									$ak_icon,
									'full',
									false,
									array(
										'class'    => 'services__icon',
										'alt'      => '',
										'loading'  => 'lazy',
										'decoding' => 'async',
									)
								);
								?>
							<?php endif; ?>

							<div class="services__body">
								<h3 class="services__title"><?php echo esc_html( $ak_item['title'] ); ?></h3>

								<?php if ( $ak_item['body'] ) : ?>
									<p class="services__text"><?php echo esc_html( $ak_item['body'] ); ?></p>
								<?php endif; ?>
							</div>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</div>
</section>
