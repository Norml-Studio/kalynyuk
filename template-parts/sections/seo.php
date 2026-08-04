<?php
/**
 * SEO copy block — Figma frame 1146:12634, 1376×1333 at x=32 y=9963.
 *
 * MEASURED (1440 canvas):
 *   heading  506 wide left, 48/600/95%/-4%
 *   column   680 wide at x=696 — the shared section spine — blocks 80 apart
 *   block    sub-heading 32/600/116%/-4% + body, 32 between them
 *
 * ⚠️ THIS SECTION HAS NO DIVI ORIGINAL. Every other migrated section replaced something;
 * this one is new in the redesign, so it is placed by `ak_append_sections` — a third
 * mechanism added for exactly this case. See ak_append_section_slugs() for why inventing a
 * Divi section to replace was rejected.
 *
 * ⚠️ HEADING LEVELS MATTER MORE HERE THAN ANYWHERE ELSE ON THE PAGE. This is the page's
 * long-form ranking copy, sitting last, and it is the only block whose whole job is to be
 * read by a crawler as well as a person. The section takes an H2 and each block an H3, so
 * the outline stays one level deep rather than flattening into a wall of equal headings.
 *
 * @package kalynyuk
 */

defined( 'ABSPATH' ) || exit;

$ak_seo = ak_seo_data();

if ( ! $ak_seo ) {
	return;
}
?>
<section class="seo">
	<div class="seo__inner">
		<h2 class="seo__heading"><?php echo esc_html( $ak_seo['heading'] ); ?></h2>

		<?php if ( $ak_seo['items'] ) : ?>
			<div class="seo__body">
				<?php foreach ( $ak_seo['items'] as $ak_item ) : ?>
					<?php if ( '' === trim( (string) $ak_item['heading'] ) && '' === trim( (string) $ak_item['body'] ) ) : ?>
						<?php continue; ?>
					<?php endif; ?>

					<div class="seo__block">
						<?php if ( $ak_item['heading'] ) : ?>
							<h3 class="seo__block-heading"><?php echo esc_html( $ak_item['heading'] ); ?></h3>
						<?php endif; ?>

						<?php if ( $ak_item['body'] ) : ?>
							<div class="seo__block-body"><?php echo wpautop( wp_kses_post( $ak_item['body'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- sanitised above. ?></div>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
</section>
