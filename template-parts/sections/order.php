<?php
/**
 * "Book a consultation" — Figma frame 1136:9153 ("Frame 9126"), 1376×994 at x=32 y=7346.
 *
 * MEASURED (1440 canvas):
 *   band        120 top / 0 bottom, no rule — the hairline above belongs to the
 *               calculator band, and the section below owns its own 120
 *   lede        heading 506 wide left · intro 680 wide at x=696 — the SAME x=696 spine
 *               `.about` and the services rows use
 *   card        1376×763, fill --accent, radius 24, `--space-7` below the lede
 *   ├ content   inset 40 — card heading 32/600/116%/-4% · checklist · CTA
 *   ├ checklist 5 rows of 616×64, hairline between, 24 above and below it
 *   │           row = 20×20 lucide/check + (16/600 title · 16/400 body at 70%), gap 12
 *   └ photo     656×715 inset 24 on its three outer edges
 *
 * ⚠️ THE REDESIGN MOVES THE LEDE OUT OF THE CARD. In the Divi original the heading and
 * intro sit INSIDE the green panel; here they sit on the cream page above it, and the
 * card holds only the checklist, the button and the photo. That is the actual change —
 * everything else is a restyle.
 *
 * ⚠️ THIS SECTION IS ADDRESSED BY `admin_label`, NOT `module_id`. It has no CSS ID in the
 * Divi builder, and it is not a leading section, so neither existing mechanism could
 * reach it. The map line is `label:Order = order`; see ak_replace_divi_section_by_id()
 * for why that was preferred over writing a CSS ID into post_content.
 *
 * @package kalynyuk
 */

defined( 'ABSPATH' ) || exit;

$ak_order = ak_order_data();

if ( ! $ak_order ) {
	return;
}

$ak_img = (int) $ak_order['image'];
?>
<section class="order">
	<div class="order__inner">
		<div class="order__lede">
			<h2 class="order__heading"><?php echo esc_html( $ak_order['heading'] ); ?></h2>

			<?php if ( $ak_order['intro'] ) : ?>
				<p class="order__intro"><?php echo esc_html( $ak_order['intro'] ); ?></p>
			<?php endif; ?>
		</div>

		<div class="order__card">
			<div class="order__content">
				<?php if ( $ak_order['card_heading'] ) : ?>
					<h3 class="order__card-heading"><?php echo esc_html( $ak_order['card_heading'] ); ?></h3>
				<?php endif; ?>

				<?php if ( $ak_order['items'] ) : ?>
					<ul class="order__list" role="list">
						<?php foreach ( $ak_order['items'] as $ak_item ) : ?>
							<?php if ( '' === trim( (string) $ak_item['title'] ) ) : ?>
								<?php continue; ?>
							<?php endif; ?>

							<li class="order__item">
								<?php
								/*
								 * The check mark is DRAWN, not typed. The Divi original opens every
								 * title with a literal ✅ emoji, which renders as a colour glyph that
								 * ignores the palette, differs per platform, and is read aloud as
								 * "white heavy check mark" by a screen reader. An inline SVG inherits
								 * `currentColor` and is `aria-hidden`, so the list reads as a list.
								 */
								?>
								<svg class="order__check" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
									<path d="M20 6 9 17l-5-5" />
								</svg>

								<div class="order__item-text">
									<p class="order__item-title"><?php echo esc_html( $ak_item['title'] ); ?></p>

									<?php if ( $ak_item['body'] ) : ?>
										<p class="order__item-body"><?php echo esc_html( $ak_item['body'] ); ?></p>
									<?php endif; ?>
								</div>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>

				<?php if ( $ak_order['cta'] ) : ?>
					<a
						class="btn btn--on-accent order__cta"
						href="<?php echo esc_url( $ak_order['cta']['url'] ); ?>"
						<?php echo ak_link_target_attrs( $ak_order['cta']['url'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					><?php echo esc_html( $ak_order['cta']['label'] ); ?></a>
				<?php endif; ?>
			</div>

			<?php if ( $ak_img ) : ?>
				<?php
				/*
				 * Decorative — empty alt rather than absent, so a screen reader skips it
				 * instead of announcing the filename.
				 */
				?>
				<div class="order__figure">
					<?php
					echo wp_get_attachment_image(
						$ak_img,
						'large',
						false,
						array(
							'class'    => 'order__photo',
							'alt'      => '',
							'sizes'    => '(min-width: 1025px) 656px, 100vw',
							'loading'  => 'lazy',
							'decoding' => 'async',
						)
					);
					?>
				</div>
			<?php endif; ?>
		</div>
	</div>
</section>
