<?php
/**
 * FAQ — Figma frame 1136:9152 ("Frame 9125"), 1376×615 at x=32 y=9228.
 *
 * MEASURED (1440 canvas):
 *   lede    470 wide left — heading 48/600/95%/-4% · intro 20/400/116%/-1% · 32 apart,
 *           then a 222×48 accent button 32 below
 *   list    680 wide at x=696 — the shared section spine — items 12 apart
 *   item    OUTLINED, radius 16 (not 24), 1px --border, transparent fill
 *           collapsed 57 tall · padding 20 left / 16 right
 *   question 16/400/116%/-1% --ink   answer 16/400/116%/-1% --ink at 56% = --ink-muted
 *   marker  20×20 vuesax arrow-down, flipped when open
 *
 * ⚠️ SAME NATIVE <details name="…"> ACCORDION AS `trust`, deliberately — the single-open
 * behaviour, the keyboard handling and the Ctrl+F-opens-a-collapsed-answer trick are all
 * the browser's. See trust.php for the full reasoning; it is not repeated here.
 *
 * ⚠️ THE QUESTIONS ARE DIVI'S, NOT FIGMA'S (Petr, 2026-08-04). The design carries eight
 * questions but only ONE answer — the other seven are drawn collapsed, so no answer text
 * exists for them anywhere. Four of those eight have no counterpart in the Divi content
 * either, which would have meant WRITING interest rates, tax percentages and eligibility
 * rules for a licensed credit intermediary. Divi's seven Q&A are complete and already
 * reviewed, so they ship; the design's question list can be adopted once someone writes
 * the answers. Recorded in design.md §13.
 *
 * @package kalynyuk
 */

defined( 'ABSPATH' ) || exit;

$ak_faq = ak_faq_data();

if ( ! $ak_faq ) {
	return;
}
?>
<section class="faq">
	<div class="faq__inner">
		<div class="faq__lede">
			<h2 class="faq__heading"><?php echo esc_html( $ak_faq['heading'] ); ?></h2>

			<?php if ( $ak_faq['intro'] ) : ?>
				<p class="faq__intro"><?php echo esc_html( $ak_faq['intro'] ); ?></p>
			<?php endif; ?>

			<?php if ( $ak_faq['all_url'] ) : ?>
				<a class="btn btn--primary faq__all" href="<?php echo esc_url( $ak_faq['all_url'] ); ?>"><?php echo esc_html( ak_str( 'ak_faq_all', 'Усі запитання і відповіді' ) ); ?></a>
			<?php endif; ?>
		</div>

		<?php if ( $ak_faq['items'] ) : ?>
			<div class="faq__list">
				<?php foreach ( $ak_faq['items'] as $ak_i => $ak_item ) : ?>
					<?php if ( '' === trim( (string) $ak_item['question'] ) ) : ?>
						<?php continue; ?>
					<?php endif; ?>

					<?php
					/*
					 * A DIFFERENT `name` from the trust accordion. Both sections are on the
					 * homepage, and <details> grouping is global to the document — sharing one
					 * name would make opening a FAQ answer close whichever "why us" item was
					 * open, halfway up the page and out of sight.
					 */
					?>
					<details class="faq__item" name="ak-faq"<?php echo 0 === $ak_i ? ' open' : ''; ?>>
						<summary class="faq__question">
							<span class="faq__question-text"><?php echo esc_html( $ak_item['question'] ); ?></span>

							<svg class="faq__marker" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
								<path d="m6 9 6 6 6-6" />
							</svg>
						</summary>

						<?php if ( $ak_item['answer'] ) : ?>
							<div class="faq__answer"><?php echo wpautop( wp_kses_post( $ak_item['answer'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- sanitised above. ?></div>
						<?php endif; ?>
					</details>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
</section>
