<?php
/**
 * Hero section — Figma frame 1130:3907 ("Frame 18"), 1440×812.
 *
 * MEASURED (1440 canvas, offsets relative to the photo top at y=78):
 *   section     green #307155, bottom-left + bottom-right radius 24, top corners 0
 *   photo       1440×734
 *   overlay     linear gradient #0D0D0D → transparent, bottom-to-top,
 *               stops 0 / 18 / 39 / 73 %
 *   H1          x=32, 40 from top, max-width 502, 48/600/95%/-4%, cream
 *   buttons     x=32, 218 from top, gap 8 — .btn--on-accent + .btn--ghost, 224×48
 *   caption     x=32, 32 from bottom, max-width 371, 20/400/124%/-1%, cream
 *   stat        right 32, 32 from bottom, max-width 288, 32/600/116%/-4%, TWO colours
 *
 * Both bottom items share a baseline: caption and stat end at y=780, i.e. 32px above
 * the section bottom.
 *
 * ⚠️ The Figma `shadow` layer carries a 64px LAYER_BLUR. Blurring a linear gradient
 * is visually a no-op — the result is the same gradient — so it is not reproduced.
 * Noted so nobody "restores" a `filter: blur()` that costs a repaint for nothing.
 *
 * ⚠️ The PRIMARY CTA is the shared one from Site chrome, NOT a hero-specific field.
 * header-standard requires the header, hero and footer CTAs to be one funnel; three
 * separately-editable labels is how they drift apart.
 *
 * @package kalynyuk
 */

defined( 'ABSPATH' ) || exit;

$ak_hero = ak_hero_data();

if ( ! $ak_hero ) {
	return;
}

$ak_img = $ak_hero['image'];
?>
<section class="hero">
	<?php if ( is_array( $ak_img ) && ! empty( $ak_img['url'] ) ) : ?>
		<?php
		/*
		 * fetchpriority="high" + eager: this is the LCP element on the homepage, so
		 * it must not be lazy-loaded. sizes="100vw" because the image is full-bleed.
		 */
		echo wp_get_attachment_image(
			(int) $ak_img['ID'],
			'full',
			false,
			array(
				'class'         => 'hero__media',
				'sizes'         => '100vw',
				'loading'       => 'eager',
				'fetchpriority' => 'high',
				'decoding'      => 'sync',
				'alt'           => $ak_img['alt'] ? $ak_img['alt'] : '',
			)
		);
		?>
	<?php endif; ?>

	<div class="hero__overlay" aria-hidden="true"></div>

	<div class="hero__inner">
		<h1 class="hero__heading"><?php echo esc_html( $ak_hero['heading'] ); ?></h1>

		<?php if ( $ak_hero['cta'] || $ak_hero['cta2'] ) : ?>
			<div class="hero__actions">
				<?php if ( $ak_hero['cta'] ) : ?>
					<a
						class="btn btn--on-accent"
						href="<?php echo esc_url( $ak_hero['cta']['url'] ); ?>"
						<?php echo ak_link_target_attrs( $ak_hero['cta']['url'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					><?php echo esc_html( $ak_hero['cta']['label'] ); ?></a>
				<?php endif; ?>

				<?php if ( $ak_hero['cta2'] ) : ?>
					<a
						class="btn btn--ghost"
						href="<?php echo esc_url( $ak_hero['cta2']['url'] ); ?>"
						<?php echo ak_link_target_attrs( $ak_hero['cta2']['url'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					><?php echo esc_html( $ak_hero['cta2']['label'] ); ?></a>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<div class="hero__foot">
			<?php if ( $ak_hero['caption'] ) : ?>
				<p class="hero__caption"><?php echo esc_html( $ak_hero['caption'] ); ?></p>
			<?php endif; ?>

			<?php if ( $ak_hero['stat_strong'] || $ak_hero['stat_muted'] ) : ?>
				<p class="hero__stat">
					<?php if ( $ak_hero['stat_strong'] ) : ?>
						<span class="hero__stat-strong"><?php echo esc_html( $ak_hero['stat_strong'] ); ?></span>
					<?php endif; ?>
					<?php if ( $ak_hero['stat_muted'] ) : ?>
						<span class="hero__stat-muted"><?php echo esc_html( $ak_hero['stat_muted'] ); ?></span>
					<?php endif; ?>
				</p>
			<?php endif; ?>
		</div>
	</div>
</section>
