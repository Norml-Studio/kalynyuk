<?php
/**
 * Site footer — Figma frame 1163:303 (1440×599).
 *
 * ⚠️ OVERRIDES Divi/footer.php AND MUST CLOSE ITS WRAPPERS. Divi opens
 * `#page-container` and `#et-main-area` in header.php and closes them here, and
 * fires `et_after_main_content`. All three are at the bottom of this file. Do not
 * remove them until phase 4, when no page renders Divi shortcodes.
 *
 * LAYOUT (measured): 40px gutters — note that page SECTIONS use 32px, so the
 * footer is 8px wider on each side. That inconsistency is in the design file; it
 * is recorded in design.md §7 rather than silently normalised.
 *
 *   logo            40,32   216×32
 *   nav column      40,124  163×258   6 items
 *   contacts column 264,124 369×352   8 items (contacts + regulatory)
 *   form            752,124 648×303
 *   "Вгору" + btn   1319,32 81×24
 *   hairline        y=508   40→1400
 *   bottom row      y=540
 *
 * ⚠️ REGULATORY BLOCK. "Intermediário de crédito n.º …", "Livro de Reclamações",
 * CNIACC and CACCL are a LEGAL disclosure requirement for a credit intermediary
 * operating in Portugal — Banco de Portugal registration plus the alternative
 * dispute-resolution entities. Do not hide them, do not drop below the `body-xs`
 * type role, and do not remove an entry because its URL happens to be empty.
 *
 * dev-wp-developer hard rules honoured:
 *   · "by Norml Studio" — "by " plain text, "Norml Studio" IS the link.
 *     ⚠️ The design says "Made by" + the Norml wordmark as an image. That is a
 *     deliberate deviation FROM the design and TOWARDS the house rule, which
 *     exists so this credit is identical across every Norml client site. Raised
 *     with Petr rather than silently resolved either way.
 *   · The footer sitemap mirrors the primary nav — same menu, one source of truth.
 *   · Legal/Privacy sits in the separate bottom row, not inside the sitemap nav.
 *
 * @package kalynyuk
 */

defined( 'ABSPATH' ) || exit;

/** Divi's post-content hook — Theme Builder body layouts and plugins use it. */
do_action( 'et_after_main_content' );

$ak_nav       = ak_nav_tree( 'primary-menu' );
$ak_phone     = ak_chrome( 'ak_phone' );
$ak_email     = ak_chrome( 'ak_email' );
// One source, shared with the mobile drawer — see ak_socials().
$ak_socials   = ak_socials();
$ak_privacy   = get_option( 'wp_page_for_privacy_policy' );

// Regulatory disclosure.
$ak_reg_no    = ak_chrome( 'ak_intermediary_no' );
$ak_reg_url   = ak_chrome( 'ak_intermediary_url' );
$ak_complaint = ak_chrome( 'ak_complaints_url' );
$ak_cniacc    = ak_chrome( 'ak_cniacc_url' );
$ak_caccl     = ak_chrome( 'ak_caccl_url' );

/**
 * Gravity Forms id for the footer contact form.
 *
 * Defaults to 1 — "Контактна форма у футері" (4 fields: email, WhatsApp, message,
 * consent), which matches the design's form exactly. Filterable rather than an ACF
 * field because form IDs differ between environments.
 *
 * @param int $form_id
 */
$ak_form_id = (int) apply_filters( 'ak_footer_form_id', 1 );

/**
 * Render one footer link, or plain text when there is no URL.
 *
 * Used by the regulatory rows: the label is legally required, the link is not, so
 * an empty URL must never make the entry disappear.
 *
 * @param string $label Visible text.
 * @param string $url   Optional URL.
 * @return void
 */
$ak_row = static function ( $label, $url = '' ) {
	if ( $url ) {
		printf(
			'<a class="site-footer__link" href="%s"%s>%s</a>',
			esc_url( $url ),
			ak_link_target_attrs( $url ), // Already escaped.
			esc_html( $label )
		);
	} else {
		printf( '<span class="site-footer__text">%s</span>', esc_html( $label ) );
	}
};
?>

		<footer class="site-footer">
			<div class="site-footer__inner">

				<div class="site-footer__brand">
					<a class="site-footer__logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
						<?php echo ak_logo_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</a>
				</div>

				<div class="site-footer__cols">
					<?php if ( $ak_nav ) : ?>
						<?php // ak_str(), not __() — see the note on the skip link in header.php. ?>
						<nav class="site-footer__nav" aria-label="<?php echo esc_attr( ak_str( 'ak_footer_nav', 'Навігація у футері' ) ); ?>">
							<ul class="site-footer__list">
								<?php foreach ( $ak_nav as $ak_item ) : ?>
									<li>
										<?php if ( empty( $ak_item['children'] ) ) : ?>
											<a class="site-footer__link" href="<?php echo esc_url( $ak_item['url'] ); ?>"
												<?php echo ak_link_target_attrs( $ak_item['url'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
											><?php echo esc_html( $ak_item['title'] ); ?></a>
										<?php else : ?>
											<?php
											/*
											 * A parent with children is not a destination — the header
											 * renders it as a <button>. In the footer the design shows
											 * it as a single row ("Portugal"), so it stays a plain
											 * label and its children are NOT expanded here; they are
											 * reachable from the header dropdown and the drawer.
											 */
											?>
											<span class="site-footer__text"><?php echo esc_html( $ak_item['title'] ); ?></span>
										<?php endif; ?>
									</li>
								<?php endforeach; ?>
							</ul>
						</nav>
					<?php endif; ?>

					<ul class="site-footer__list site-footer__list--contacts">
						<?php foreach ( $ak_socials as $ak_social ) : ?>
							<li><?php $ak_row( $ak_social['label'], $ak_social['url'] ); ?></li>
						<?php endforeach; ?>
						<?php if ( $ak_phone ) : ?>
							<li><a class="site-footer__link" href="tel:<?php echo esc_attr( preg_replace( '/[^\d+]/', '', $ak_phone ) ); ?>"><?php echo esc_html( $ak_phone ); ?></a></li>
						<?php endif; ?>
						<?php if ( $ak_email ) : ?>
							<li><a class="site-footer__link" href="mailto:<?php echo esc_attr( $ak_email ); ?>"><?php echo esc_html( $ak_email ); ?></a></li>
						<?php endif; ?>

						<?php // ── Regulatory disclosure — legally required, see the file docblock. ?>
						<?php if ( $ak_reg_no ) : ?>
							<li>
								<?php
								$ak_row(
									sprintf(
										/* translators: %s: Banco de Portugal credit-intermediary registration number. */
										__( 'Intermediário de crédito n.º %s', 'kalynyuk' ),
										$ak_reg_no
									),
									$ak_reg_url
								);
								?>
							</li>
						<?php endif; ?>
						<li><?php $ak_row( __( 'Livro de Reclamações', 'kalynyuk' ), $ak_complaint ); ?></li>
						<li><?php $ak_row( 'CNIACC', $ak_cniacc ); ?></li>
						<li><?php $ak_row( 'CACCL', $ak_caccl ); ?></li>
					</ul>
				</div>

				<div class="site-footer__form">
					<div class="site-footer__form-head">
						<h2 class="site-footer__form-title"><?php echo esc_html( ak_str( 'ak_form_title', 'Форма для зворотнього зв’язку' ) ); ?></h2>

						<button class="site-footer__top" type="button" data-ak-top>
							<span><?php echo esc_html( ak_str( 'ak_to_top', 'Вгору' ) ); ?></span>
							<span class="site-footer__top-icon" aria-hidden="true">
								<svg width="16" height="16" viewBox="0 0 16 16" focusable="false">
									<path d="M4 9.5 8 5.5l4 4" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
								</svg>
							</span>
						</button>
					</div>

					<?php if ( $ak_form_id && class_exists( 'GFAPI' ) && GFAPI::get_form( $ak_form_id ) ) : ?>
						<?php
						// The child theme forces ajax=true on every form (inc/integrations.php),
						// so the attribute here is belt-and-braces rather than load-bearing.
						echo do_shortcode( sprintf( '[gravityform id="%d" title="false" description="false" ajax="true"]', $ak_form_id ) );
						?>
					<?php endif; ?>
				</div>
			</div>

			<div class="site-footer__bottom">
				<div class="site-footer__bottom-inner">
					<p class="site-footer__copyright">
						<?php
						printf(
							'© %1$s %2$s',
							esc_html( date_i18n( 'Y' ) ),
							// ⚠️ Ukrainian, not Portuguese — ak_str() resolves by source string, so the
							// default here IS the Ukrainian copy. See the note in acf-options.php.
							esc_html( ak_str( 'ak_copyright', 'Усі права захищено.' ) )
						);
						?>
					</p>

					<?php if ( $ak_privacy ) : ?>
						<a class="site-footer__link" href="<?php echo esc_url( get_permalink( ak_translate_id( $ak_privacy ) ) ); ?>">
							<?php echo esc_html( ak_str( 'ak_privacy', 'Конфіденційність' ) ); ?>
						</a>
					<?php endif; ?>

					<p class="site-footer__credit">
						<?php
						/*
						 * ⚠️ DELIBERATE DEVIATION from dev-wp-developer's "Site chrome"
						 * rule, on Petr's explicit instruction (2026-07-28).
						 *
						 * That rule prescribes `by {Norml Studio}` — "by " as plain text
						 * with the NAME as the link — so the credit is byte-identical on
						 * every Norml client site. This footer instead uses the
						 * "Made by" + signature lockup drawn in the design (Figma
						 * 1163:349, 190×43, cream #F7F2E9), because Petr wants the
						 * mark rather than the text form here.
						 *
						 * Recorded rather than silently resolved: if the house rule is
						 * meant to win, revert this block — nothing else depends on it.
						 *
						 * Accessibility: it is an image of text, so the alt carries the
						 * full credit. The whole lockup is the link, so there is exactly
						 * one target, as the rule intends.
						 */
						?>
						<a
							class="site-footer__credit-link"
							href="https://norml.studio"
							target="_blank"
							rel="noopener"
						>
							<img
								src="<?php echo esc_url( AK_URI . '/assets/norml-credit.svg' ); ?>"
								alt="<?php esc_attr_e( 'Made by Norml Studio', 'kalynyuk' ); ?>"
								width="190"
								height="43"
								decoding="async"
							/>
						</a>
					</p>
				</div>
			</div>
		</footer>

	</div><!-- #et-main-area -->
</div><!-- #page-container -->

<?php wp_footer(); ?>
</body>
</html>
