<?php
/**
 * Site footer.
 *
 * ⚠️ OVERRIDES Divi/footer.php AND MUST CLOSE ITS WRAPPERS. Divi opens
 * `#page-container` and `#et-main-area` in header.php and closes them here, and
 * fires `et_after_main_content`. Both closes are at the bottom of this file. Do not
 * remove them until phase 4, when no page renders Divi shortcodes.
 *
 * STRUCTURE — from Figma `Frame 152` (1163:1512): a feedback form, the logotype,
 * two link columns, then a bottom row with copyright and a privacy link.
 *
 * ⚠️ Exact paddings/type sizes are NOT yet measured — the Figma bridge disconnected
 * before the footer frame could be read. Layout here uses the design.md scale and
 * is structurally faithful; re-measure `1163:303` and refine before calling the
 * footer done.
 *
 * dev-wp-developer hard rules honoured here:
 *   · "by Norml Studio" — "by " is plain text, "Norml Studio" is the link.
 *   · ONE primary CTA, the same destination and label as the header's.
 *   · The footer sitemap mirrors the primary nav — same menu, single source of
 *     truth. Legal/Privacy lives in the separate bottom row, not in the sitemap.
 *
 * @package kalynyuk
 */

defined( 'ABSPATH' ) || exit;

/** Divi's post-content hook — Theme Builder body layouts and plugins use it. */
do_action( 'et_after_main_content' );

$ak_nav       = ak_nav_tree( 'primary-menu' );
$ak_cta       = ak_primary_cta();
$ak_phone     = ak_chrome( 'ak_phone' );
$ak_email     = ak_chrome( 'ak_email' );
$ak_telegram  = ak_chrome( 'ak_telegram' );
$ak_instagram = ak_chrome( 'ak_instagram' );
$ak_privacy   = get_option( 'wp_page_for_privacy_policy' );

/**
 * Gravity Forms id for the footer contact form.
 *
 * Defaults to 1 — "Контактна форма у футері", the form currently embedded in Divi
 * footer layout 365. Filterable rather than an ACF field because form IDs differ
 * between environments and this is a developer concern, not an editorial one.
 *
 * @param int $form_id
 */
$ak_form_id = (int) apply_filters( 'ak_footer_form_id', 1 );
?>

		<footer class="site-footer">
			<div class="site-footer__inner">

				<div class="site-footer__brand">
					<a class="site-footer__logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
						<?php echo ak_logo_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</a>

					<?php if ( $ak_cta ) : ?>
						<a
							class="btn btn--primary site-footer__cta"
							href="<?php echo esc_url( $ak_cta['url'] ); ?>"
							<?php echo ak_link_target_attrs( $ak_cta['url'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						><?php echo esc_html( $ak_cta['label'] ); ?></a>
					<?php endif; ?>
				</div>

				<?php if ( $ak_nav ) : ?>
					<nav class="site-footer__nav" aria-label="<?php esc_attr_e( 'Footer navigation', 'kalynyuk' ); ?>">
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
										 * A parent with children is not a destination (the header
										 * renders it as a button). In the footer we expose its
										 * CHILDREN instead of a dead label, so every footer entry
										 * navigates somewhere.
										 */
										?>
										<span class="site-footer__group"><?php echo esc_html( $ak_item['title'] ); ?></span>
										<ul class="site-footer__sublist">
											<?php foreach ( $ak_item['children'] as $ak_child ) : ?>
												<li>
													<a class="site-footer__link" href="<?php echo esc_url( $ak_child['url'] ); ?>"
														<?php echo ak_link_target_attrs( $ak_child['url'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
													><?php echo esc_html( $ak_child['title'] ); ?></a>
												</li>
											<?php endforeach; ?>
										</ul>
									<?php endif; ?>
								</li>
							<?php endforeach; ?>
						</ul>
					</nav>
				<?php endif; ?>

				<?php if ( $ak_phone || $ak_email || $ak_telegram || $ak_instagram ) : ?>
					<ul class="site-footer__contacts">
						<?php if ( $ak_phone ) : ?>
							<li><a class="site-footer__link" href="tel:<?php echo esc_attr( preg_replace( '/[^\d+]/', '', $ak_phone ) ); ?>"><?php echo esc_html( $ak_phone ); ?></a></li>
						<?php endif; ?>
						<?php if ( $ak_email ) : ?>
							<li><a class="site-footer__link" href="mailto:<?php echo esc_attr( $ak_email ); ?>"><?php echo esc_html( $ak_email ); ?></a></li>
						<?php endif; ?>
						<?php if ( $ak_telegram ) : ?>
							<li><a class="site-footer__link" href="<?php echo esc_url( $ak_telegram ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( ak_str( 'ak_telegram', 'Телеграм' ) ); ?></a></li>
						<?php endif; ?>
						<?php if ( $ak_instagram ) : ?>
							<li><a class="site-footer__link" href="<?php echo esc_url( $ak_instagram ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( ak_str( 'ak_instagram', 'Інстаграм' ) ); ?></a></li>
						<?php endif; ?>
					</ul>
				<?php endif; ?>

				<?php if ( $ak_form_id && class_exists( 'GFAPI' ) && GFAPI::get_form( $ak_form_id ) ) : ?>
					<div class="site-footer__form">
						<h2 class="site-footer__form-title"><?php esc_html_e( 'Contact form', 'kalynyuk' ); ?></h2>
						<?php
						// The child theme forces ajax=true on every form (inc/integrations.php),
						// so the attribute here is belt-and-braces rather than load-bearing.
						echo do_shortcode( sprintf( '[gravityform id="%d" title="false" description="false" ajax="true"]', $ak_form_id ) );
						?>
					</div>
				<?php endif; ?>
			</div>

			<div class="site-footer__bottom">
				<div class="site-footer__bottom-inner">
					<p class="site-footer__copyright">
						<?php
						printf(
							'© %1$s %2$s. %3$s',
							esc_html( date_i18n( 'Y' ) ),
							esc_html( get_bloginfo( 'name' ) ),
							esc_html( ak_str( 'ak_copyright', 'Todos os direitos reservados.' ) )
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
						 * dev-wp-developer, "Site chrome": "by " is plain text and
						 * "Norml Studio" itself is the link. Lower-case b, no "Concept
						 * by", no bare URL after the name, and never text-only.
						 */
						printf(
							/* translators: %s: Norml Studio, linked. */
							esc_html__( 'by %s', 'kalynyuk' ),
							'<a href="https://norml.studio" target="_blank" rel="noopener">Norml Studio</a>'
						);
						?>
					</p>
				</div>
			</div>
		</footer>

	</div><!-- #et-main-area -->
</div><!-- #page-container -->

<?php wp_footer(); ?>
</body>
</html>
