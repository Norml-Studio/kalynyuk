<?php
/**
 * Language switcher.
 *
 * The current language is the collapsed trigger; the rest drop below it, matching
 * the design (Figma 1164:468 — a 56×128 stack of circular flags).
 *
 * A language only appears here once it has a published translation of the current
 * object — `hide_if_no_translation` in ak_language_switcher(). That is what makes a
 * future language a pure admin action: `ru` is absent today because its only page
 * is a draft, and it will appear by itself when built. Nothing here knows how many
 * languages exist.
 *
 * @package kalynyuk
 */

defined( 'ABSPATH' ) || exit;

$ak_langs = ak_language_switcher();

if ( count( $ak_langs ) < 2 ) {
	return;
}

$ak_current = $ak_langs[0];
$ak_rest    = array_slice( $ak_langs, 1 );
?>
<div class="lang-switch" data-ak-lang>
	<button
		class="lang-switch__trigger"
		type="button"
		aria-expanded="false"
		aria-controls="ak-lang-list"
		data-ak-lang-trigger
	>
		<span class="u-visually-hidden">
			<?php
			printf(
				/* translators: %s: current language name. */
				esc_html__( 'Language: %s. Change language', 'kalynyuk' ),
				esc_html( $ak_current['name'] )
			);
			?>
		</span>
		<span class="lang-switch__flag">
			<?php
			/*
			 * Inline SVG from the design's own flag components. Polylang's bundled
			 * flags are 16×11 PNGs and were being upscaled 2× into a 32px circle,
			 * which looked mushy. See ak_flag_svg().
			 *
			 * Also note: `raw => 1` makes Polylang return 'flag' as a URL STRING,
			 * not a ready <img> tag — the first version echoed it straight out and
			 * printed a bare URL into the header.
			 */
			echo ak_flag_svg( $ak_current['slug'], $ak_current['flag'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			?>
		</span>
	</button>

	<ul class="lang-switch__list" id="ak-lang-list" hidden data-ak-lang-list>
		<?php foreach ( $ak_langs as $ak_lang ) : ?>
			<li class="lang-switch__item">
				<a
					class="lang-switch__link<?php echo $ak_lang['current'] ? ' is-current' : ''; ?>"
					href="<?php echo esc_url( $ak_lang['url'] ); ?>"
					hreflang="<?php echo esc_attr( $ak_lang['slug'] ); ?>"
					lang="<?php echo esc_attr( $ak_lang['slug'] ); ?>"
					<?php echo $ak_lang['current'] ? ' aria-current="true"' : ''; ?>
				>
					<span class="lang-switch__flag"><?php echo ak_flag_svg( $ak_lang['slug'], $ak_lang['flag'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					<span class="u-visually-hidden"><?php echo esc_html( $ak_lang['name'] ); ?></span>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
	<?php unset( $ak_rest ); ?>
</div>
