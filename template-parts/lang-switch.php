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
		<span class="lang-switch__flag" aria-hidden="true">
			<?php
			/*
			 * ⚠️ With `raw => 1`, Polylang returns 'flag' as a URL STRING, not as a
			 * ready <img> tag. Echoing it printed a bare URL into the header. Caught
			 * by rendering the page, not by reading the docs — which is the whole
			 * argument for the Playwright pass.
			 */
			printf(
				'<img src="%s" alt="" width="16" height="11" decoding="async" />',
				esc_url( $ak_current['flag'] )
			);
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
					<span class="lang-switch__flag" aria-hidden="true"><?php printf( '<img src="%s" alt="" width="16" height="11" decoding="async" />', esc_url( $ak_lang['flag'] ) ); ?></span>
					<span class="u-visually-hidden"><?php echo esc_html( $ak_lang['name'] ); ?></span>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
	<?php unset( $ak_rest ); ?>
</div>
