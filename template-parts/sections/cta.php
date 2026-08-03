<?php
/**
 * CTA banner — Figma frame 1130:3996 ("Frame 37"), 1376×512 at x=32 y=2764.
 *
 * MEASURED (1440 canvas):
 *   band       hairline #d6d0c6 top AND bottom, 32 between each rule and the card
 *   card       1376×512, radius 24, photo background, dark scrim
 *   content    x=226 (centred), 925 wide, 120 from the card top and bottom
 *   heading    48/600/95%/-4%, CENTRED, cream — 925 wide, 4 lines
 *   button     224×48, cream fill / ink label, 40 below the heading
 *
 * ⚠️ THE BAND'S HAIRLINES ARE ITS OWN, not the previous section's. Figma draws them as
 * two full-bleed 1440-wide vectors at y=2732 and y=3308 — 32 above and below the card —
 * and the Divi section being replaced carries exactly the same thing as
 * `border_width_top/bottom="1px" border_color="#D6D0C6"` with 32px padding. They bracket
 * this section; the 120px above the first one belongs to `.about`.
 *
 * ⚠️ THE CARD IS CONTAINER-WIDTH, NOT FULL-BLEED. It is 1376 — the container — so unlike
 * `.hero` this is a normal `container()` section whose *inner* carries the background.
 * The design.md §6 exception for fixed-composition photo bands does not apply: this photo
 * is a wide environmental shot with nothing to recrop away.
 *
 * ⚠️ THE BUTTON IS THE SHARED SITE CTA, not a section field — see ak_cta_data().
 *
 * @package kalynyuk
 */

defined( 'ABSPATH' ) || exit;

$ak_cta = ak_cta_data();

if ( ! $ak_cta ) {
	return;
}

$ak_img = (int) $ak_cta['image'];
?>
<section class="cta">
	<div class="cta__inner">
		<div class="cta__card">
			<?php if ( $ak_img ) : ?>
				<?php
				/*
				 * Decorative: the photo carries no information the heading does not, and it
				 * is deliberately darkened until it reads as texture. An empty alt is the
				 * correct value — NOT a missing one, which would make a screen reader fall
				 * back to announcing the filename.
				 *
				 * `sizes="100vw"` because the card tracks the container, which tracks the
				 * viewport at every width below 1436.
				 */
				echo wp_get_attachment_image(
					$ak_img,
					'full',
					false,
					array(
						'class'    => 'cta__media',
						'alt'      => '',
						'sizes'    => '100vw',
						'loading'  => 'lazy',
						'decoding' => 'async',
					)
				);
				?>

				<?php
				/*
				 * ⚠️ A REAL ELEMENT, and the hero's comment explains why that is not a
				 * contradiction. There the same #0D0D0D scrim was REMOVED because it was
				 * already baked into the exported photograph and rendering it again darkened
				 * the frame twice. This photo has no such vignette — it is a bright shot of a
				 * desk, and the live Divi version puts cream text straight onto it, where it
				 * is barely legible. So here the scrim is load-bearing rather than duplicated.
				 *
				 * On its own element rather than a gradient on `.cta__media`, because the
				 * media may be a <picture> wrapper (Imagify) whose background would sit
				 * BEHIND the inner <img> instead of over it.
				 */
				?>
				<span class="cta__scrim" aria-hidden="true"></span>
			<?php endif; ?>

			<div class="cta__content">
				<h2 class="cta__heading"><?php echo esc_html( $ak_cta['heading'] ); ?></h2>

				<?php if ( $ak_cta['cta'] ) : ?>
					<a
						class="btn btn--on-accent cta__button"
						href="<?php echo esc_url( $ak_cta['cta']['url'] ); ?>"
						<?php echo ak_link_target_attrs( $ak_cta['cta']['url'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					><?php echo esc_html( $ak_cta['cta']['label'] ); ?></a>
				<?php endif; ?>
			</div>
		</div>
	</div>
</section>
