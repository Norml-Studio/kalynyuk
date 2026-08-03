<?php
/**
 * "Why us" accordion — Figma frame 1130:8979 ("Frame 9100"), 1376×938 at x=32 y=4917.
 *
 * MEASURED (1440 canvas):
 *   band      120 top / 50 bottom, 1px #d6d0c6 bottom rule (Divi's own `custom_padding`
 *             says 120/0; 50 keeps the rhythm identical to the services band above it)
 *   heading   centred, max 677, 48/600/95%/-4%, `--space-7` to the body
 *   body      two columns, SPACE_BETWEEN with a 32 gap — photo 680 wide, accordion 664
 *   photo     680×790, radius 24, `--canvas` beneath it
 *   question  24/700/116%/-1%, max 548, with a 32×32 lucide plus / minus at the far right
 *   answer    20/400/124%/-1%, max 587, 24 below the question
 *   rules     a 1px hairline BETWEEN items — 32 above it and 32 below
 *
 * ⚠️ THIS IS A NATIVE <details> ACCORDION, AND THAT IS THE WHOLE IMPLEMENTATION.
 *
 * The brief was "first one open, opening another closes it". That is exactly what the
 * `name` attribute on <details> does — grouped disclosures behave like radio buttons — so
 * the behaviour is the browser's, not ours. What that buys, none of which a hand-rolled
 * accordion gets for free:
 *
 *   - keyboard operation, Enter/Space, and focus handling
 *   - the correct ARIA semantics with no `aria-expanded` to keep in sync
 *   - in-page find (Ctrl+F) opening a collapsed answer to reveal the match
 *   - it works before, and without, JavaScript
 *
 * `open` on the first item is likewise declarative — no "activate the first one" script
 * that briefly shows everything collapsed on load.
 *
 * ⚠️ THERE IS NO EXPAND ANIMATION, deliberately. design.md §9 budgets TWO motion moments
 * and both are spent (the preloader and hover/state transitions). An accordion that
 * animates its height would be a third. Do not add one.
 *
 * `trust.js` only exists for browsers without `name` support — see the note there.
 *
 * @package kalynyuk
 */

defined( 'ABSPATH' ) || exit;

$ak_trust = ak_trust_data();

if ( ! $ak_trust ) {
	return;
}

$ak_img = (int) $ak_trust['image'];
?>
<section class="trust">
	<div class="trust__inner">
		<h2 class="trust__heading"><?php echo esc_html( $ak_trust['heading'] ); ?></h2>

		<div class="trust__body">
			<?php if ( $ak_img ) : ?>
				<?php
				/*
				 * Decorative — the accordion beside it carries the meaning. Empty alt rather
				 * than absent, so a screen reader skips it instead of reading the filename.
				 */
				?>
				<div class="trust__media">
					<?php
					echo wp_get_attachment_image(
						$ak_img,
						'large',
						false,
						array(
							'class'    => 'trust__photo',
							'alt'      => '',
							'sizes'    => '(min-width: 1025px) 680px, 100vw',
							'loading'  => 'lazy',
							'decoding' => 'async',
						)
					);
					?>
				</div>
			<?php endif; ?>

			<?php if ( $ak_trust['items'] ) : ?>
				<div class="trust__list">
					<?php foreach ( $ak_trust['items'] as $ak_i => $ak_item ) : ?>
						<?php if ( '' === trim( (string) $ak_item['question'] ) ) : ?>
							<?php continue; ?>
						<?php endif; ?>

						<?php
						/*
						 * `name` is what makes these mutually exclusive, and it must be the SAME
						 * string for every item in the group. Scoped per section rather than
						 * global so a second accordion on the same page cannot fight this one.
						 */
						?>
						<details class="trust__item" name="ak-trust"<?php echo 0 === $ak_i ? ' open' : ''; ?>>
							<summary class="trust__question">
								<h3 class="trust__question-text"><?php echo esc_html( $ak_item['question'] ); ?></h3>

								<?php
								/*
								 * ONE icon that is a plus or a minus depending on state — the vertical
								 * bar is hidden by CSS when the item is open. Two separate icons would
								 * mean shipping both and toggling display, which is the same thing with
								 * more markup and a second thing to keep in sync.
								 *
								 * aria-hidden because <summary> already announces expanded/collapsed.
								 */
								?>
								<svg class="trust__marker" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true" focusable="false">
									<path d="M5 12h14" />
									<path class="trust__marker-bar" d="M12 5v14" />
								</svg>
							</summary>

							<?php if ( $ak_item['answer'] ) : ?>
								<div class="trust__answer"><?php echo wpautop( wp_kses_post( $ak_item['answer'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- sanitised above. ?></div>
							<?php endif; ?>
						</details>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
	</div>
</section>
