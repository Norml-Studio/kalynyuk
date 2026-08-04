<?php
/**
 * Testimonials — Figma frame 1130:9143 ("Frame 9119"), 1376×584 at x=32 y=8460.
 *
 * MEASURED (1440 canvas):
 *   header   1376×48, space-between — heading 48/600/95%/-4% left · button + arrows right
 *   track    1376×480 at y=104, cards 448×480, gap 16 (448·3 + 16·2 = 1376)
 *   card     --surface fill, radius 24, no border, inset 24
 *   ├ quote  24×24 mark at the top-left
 *   ├ text   400 wide, 20/400/116%/-1%
 *   ├ rule   400 wide --border, 24 above the footer
 *   └ footer 56 avatar (circle) · name 24/600 · rating 16/400 + five 24px stars
 *
 * ⚠️ THE DATA IS THE PLUGIN'S; EVERY STRING IS OURS. See ak_testimonials_data() for the
 * reasoning — Review Wall keeps its heading and button label in `get_option()`, one global
 * value for all four languages, and its shortcode takes no arguments. That is what this
 * rebuild fixes; it is not a restyle.
 *
 * ⚠️ THE DESIGN SHOWS A LOCATION UNDER EACH NAME ("Португалія") AND IT IS NOT RENDERED.
 * Review Wall's table has four columns — author, photo, content, rating — and no location
 * anywhere. Printing a constant "Португалія" under every reviewer would be inventing an
 * attribution for a real named person, which is not a layout decision to make quietly.
 * Recorded in design.md §13; it needs either a data source or a design change.
 *
 * @package kalynyuk
 */

defined( 'ABSPATH' ) || exit;

$ak_t = ak_testimonials_data();

if ( ! $ak_t ) {
	return;
}
?>
<section class="testimonials">
	<div class="testimonials__inner">
		<div class="testimonials__head">
			<h2 class="testimonials__heading"><?php echo esc_html( $ak_t['heading'] ); ?></h2>

			<div class="testimonials__actions">
				<?php if ( $ak_t['all_url'] ) : ?>
					<a class="btn btn--primary testimonials__all" href="<?php echo esc_url( $ak_t['all_url'] ); ?>"><?php echo esc_html( ak_str( 'ak_reviews_all', 'Усі відгуки' ) ); ?></a>
				<?php endif; ?>

				<?php if ( count( $ak_t['reviews'] ) > 3 ) : ?>
					<?php
					/*
					 * Rendered only when there is something to scroll TO. Three cards fit at
					 * once, so with three or fewer the arrows would be permanently dead
					 * controls — and a disabled button that is never enabled is worse than an
					 * absent one.
					 *
					 * Real <button>s, not divs: they are focusable and Enter/Space-operable for
					 * free. The track is a scroll container, so a keyboard user can also just
					 * tab into it and arrow along without these.
					 */
					?>
					<div class="testimonials__nav">
						<button class="testimonials__arrow" type="button" data-ak-scroll="prev" aria-label="<?php echo esc_attr( ak_str( 'ak_reviews_prev', 'Попередні відгуки' ) ); ?>">
							<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
								<path d="m15 18-6-6 6-6" />
							</svg>
						</button>
						<button class="testimonials__arrow" type="button" data-ak-scroll="next" aria-label="<?php echo esc_attr( ak_str( 'ak_reviews_next', 'Наступні відгуки' ) ); ?>">
							<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
								<path d="m9 18 6-6-6-6" />
							</svg>
						</button>
					</div>
				<?php endif; ?>
			</div>
		</div>

		<?php if ( $ak_t['reviews'] ) : ?>
			<?php
			/*
			 * `tabindex="0"` plus a role and a label because this is a SCROLLABLE REGION: a
			 * keyboard user must be able to focus it and scroll with the arrow keys, and
			 * without the tabindex the overflow is reachable by mouse only. The role is what
			 * makes screen readers announce it as a navigable group rather than a stray div.
			 */
			?>
			<ul
				class="testimonials__track"
				role="group"
				tabindex="0"
				aria-label="<?php echo esc_attr( $ak_t['heading'] ); ?>"
				data-ak-testimonials
			>
				<?php foreach ( $ak_t['reviews'] as $ak_r ) : ?>
					<li class="testimonials__card">
						<svg class="testimonials__quote" width="24" height="24" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false">
							<path d="M9.6 4.8A6.6 6.6 0 0 0 3 11.4v7.8h7.8v-7.8H6.6a3 3 0 0 1 3-3zm10.8 0a6.6 6.6 0 0 0-6.6 6.6v7.8h7.8v-7.8h-4.2a3 3 0 0 1 3-3z" />
						</svg>

						<p class="testimonials__text"><?php echo esc_html( $ak_r['text'] ); ?></p>

						<div class="testimonials__foot">
							<?php if ( $ak_r['photo'] ) : ?>
								<?php
								echo wp_get_attachment_image(
									$ak_r['photo'],
									'thumbnail',
									false,
									array(
										'class'    => 'testimonials__avatar',
										'alt'      => '',
										'loading'  => 'lazy',
										'decoding' => 'async',
									)
								);
								?>
							<?php else : ?>
								<?php
								/*
								 * Google gives no photo for some reviewers — 2 of the 20 currently.
								 * An initial keeps the footer's grid intact; an <img> with no src
								 * would collapse the row and shift the name.
								 */
								?>
								<span class="testimonials__avatar testimonials__avatar--initial" aria-hidden="true"><?php echo esc_html( mb_substr( $ak_r['author'], 0, 1 ) ); ?></span>
							<?php endif; ?>

							<p class="testimonials__author"><?php echo esc_html( $ak_r['author'] ); ?></p>

							<p class="testimonials__rating">
								<?php
								/*
								 * A FORMAT STRING, not a concatenation — the same reason
								 * ak_read_minutes_* is one. Word order around a number differs by
								 * language: Portuguese wants "Avaliação 5,0" where Ukrainian wants
								 * "5,0 Оцінка", and gluing the two halves together in PHP would make
								 * that untranslatable no matter how well the label itself is.
								 *
								 * number_format_i18n() gives the locale's own decimal mark, so
								 * Ukrainian reads 5,0 rather than 5.0.
								 *
								 * The stars are aria-hidden because this line already states the
								 * value; announcing both would read the rating twice.
								 */
								echo esc_html( sprintf( ak_str( 'ak_reviews_rating', '%s Оцінка' ), number_format_i18n( $ak_r['rating'], 1 ) ) );
								?>
							</p>

							<span class="testimonials__stars" aria-hidden="true">
								<?php for ( $ak_s = 1; $ak_s <= 5; $ak_s++ ) : ?>
									<svg class="testimonials__star<?php echo $ak_s <= round( $ak_r['rating'] ) ? '' : ' testimonials__star--empty'; ?>" width="24" height="24" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false">
										<path d="m12 2.5 2.9 5.9 6.5.9-4.7 4.6 1.1 6.5-5.8-3-5.8 3 1.1-6.5L2.6 9.3l6.5-.9z" />
									</svg>
								<?php endfor; ?>
							</span>
						</div>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</div>
</section>
