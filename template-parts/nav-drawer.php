<?php
/**
 * Mobile navigation drawer.
 *
 * Implements header-standard §4 verbatim, and the design already asks for the same
 * thing (Figma 1166:2455 root / 1166:2560 drill-down):
 *
 *   · full-viewport-width green panel, slides in, over a scrim
 *   · root panel = top-level items as large tap targets
 *   · an item WITH children is a <button> with a chevron-RIGHT that slides a
 *     sub-panel in from the right
 *   · the sub-panel opens with a "‹ Назад" row
 *   · a PINNED action bar at the bottom holds the CTA and does not scroll
 *
 * Panels are absolutely stacked; sub-panels sit at translateX(100%) until active.
 * Scroll-lock, Escape, focus in/out and reduced-motion are handled in header.js.
 *
 * @package kalynyuk
 */

defined( 'ABSPATH' ) || exit;

$ak_nav  = $args['nav'] ?? array();
$ak_cta  = $args['cta'] ?? null;
$ak_back = $args['back'] ?? 'Назад';

if ( ! $ak_nav ) {
	return;
}

$ak_phone   = ak_chrome( 'ak_phone' );
$ak_email   = ak_chrome( 'ak_email' );
// One source, shared with the footer — see ak_socials().
$ak_socials = ak_socials();
?>
<div class="nav-drawer" id="ak-drawer" hidden data-ak-drawer>
	<div class="nav-drawer__scrim" data-ak-drawer-scrim></div>

	<div class="nav-drawer__dialog" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Menu', 'kalynyuk' ); ?>">

		<div class="nav-drawer__panels">

			<!-- root panel -->
			<div class="nav-drawer__panel is-active" data-ak-drawer-panel="root">
				<ul class="nav-drawer__list">
					<?php foreach ( $ak_nav as $ak_item ) : ?>
						<li class="nav-drawer__item">
							<?php if ( ! empty( $ak_item['children'] ) ) : ?>
								<button
									class="nav-drawer__trigger"
									type="button"
									aria-expanded="false"
									aria-controls="ak-drawer-sub-<?php echo esc_attr( $ak_item['id'] ); ?>"
									data-ak-drawer-drill="<?php echo esc_attr( $ak_item['id'] ); ?>"
								>
									<span><?php echo esc_html( $ak_item['title'] ); ?></span>
									<svg class="nav-drawer__chevron" width="20" height="20" viewBox="0 0 20 20" aria-hidden="true" focusable="false">
										<path d="M7.5 4.5 13 10l-5.5 5.5" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
									</svg>
								</button>
							<?php else : ?>
								<a
									class="nav-drawer__link<?php echo $ak_item['current'] ? ' is-current' : ''; ?>"
									href="<?php echo esc_url( $ak_item['url'] ); ?>"
									<?php echo $ak_item['current'] ? ' aria-current="page"' : ''; ?>
									<?php echo ak_link_target_attrs( $ak_item['url'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								><?php echo esc_html( $ak_item['title'] ); ?></a>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ul>

				<?php if ( $ak_socials || $ak_phone || $ak_email ) : ?>
					<hr class="nav-drawer__rule" />

					<ul class="nav-drawer__contacts">
						<?php foreach ( $ak_socials as $ak_social ) : ?>
							<li>
								<a class="nav-drawer__contact" href="<?php echo esc_url( $ak_social['url'] ); ?>"
									<?php echo ak_link_target_attrs( $ak_social['url'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								><?php echo esc_html( $ak_social['label'] ); ?></a>
							</li>
						<?php endforeach; ?>
						<?php if ( $ak_phone ) : ?>
							<li><a class="nav-drawer__contact" href="tel:<?php echo esc_attr( preg_replace( '/[^\d+]/', '', $ak_phone ) ); ?>"><?php echo esc_html( $ak_phone ); ?></a></li>
						<?php endif; ?>
						<?php if ( $ak_email ) : ?>
							<li><a class="nav-drawer__contact" href="mailto:<?php echo esc_attr( $ak_email ); ?>"><?php echo esc_html( $ak_email ); ?></a></li>
						<?php endif; ?>
					</ul>
				<?php endif; ?>
			</div>

			<!-- one sub-panel per parent item -->
			<?php foreach ( $ak_nav as $ak_item ) : ?>
				<?php if ( empty( $ak_item['children'] ) ) { continue; } ?>
				<div
					class="nav-drawer__panel nav-drawer__panel--sub"
					id="ak-drawer-sub-<?php echo esc_attr( $ak_item['id'] ); ?>"
					data-ak-drawer-panel="<?php echo esc_attr( $ak_item['id'] ); ?>"
				>
					<button class="nav-drawer__back" type="button" data-ak-drawer-back>
						<svg width="20" height="20" viewBox="0 0 20 20" aria-hidden="true" focusable="false">
							<path d="M12.5 4.5 7 10l5.5 5.5" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
						</svg>
						<span><?php echo esc_html( $ak_back ); ?></span>
					</button>

					<ul class="nav-drawer__list">
						<?php foreach ( $ak_item['children'] as $ak_child ) : ?>
							<li class="nav-drawer__item">
								<a
									class="nav-drawer__link"
									href="<?php echo esc_url( $ak_child['url'] ); ?>"
									<?php echo ak_link_target_attrs( $ak_child['url'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								><?php echo esc_html( $ak_child['title'] ); ?></a>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endforeach; ?>
		</div>

		<?php if ( $ak_cta ) : ?>
			<div class="nav-drawer__actions">
				<a
					class="btn btn--on-accent nav-drawer__cta"
					href="<?php echo esc_url( $ak_cta['url'] ); ?>"
					<?php echo ak_link_target_attrs( $ak_cta['url'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				><?php echo esc_html( $ak_cta['label'] ); ?></a>
			</div>
		<?php endif; ?>
	</div>
</div>
