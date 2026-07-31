<?php
/**
 * Mortgage calculator section — ported out of page 566's Divi code modules.
 *
 * The markup below is VERBATIM from those modules; only the container around it is
 * new (it replaces Divi's row + two 1/2 columns — see _calculator-legacy.scss for
 * the geometry, read off the shortcode attributes rather than guessed).
 *
 * ⚠️ The element IDs are load-bearing. calculator.js addresses every field by id
 * (`property-price-input`, `mensalidade`, `dsti-percent`, `imt-cont`…), so renaming
 * one silently breaks a number on the page rather than throwing. Leave them alone
 * until the legacy CSS/JS is rewritten as a whole.
 *
 * The Gravity Form is untouched by decision (Petr, 2026-07-31): it stays form 3 via
 * shortcode, so notifications and integrations keep working exactly as they do now.
 *
 * ⚠️ USED ON TWO PAGES, and that is the point. The calculator existed TWICE in the
 * database — once as page 566 and once as a Divi section on the homepage — as two
 * near-identical copies that had already drifted apart (97 443 vs 97 526 characters
 * of code, different checksums), each carrying its own copy of the DSTI defect. Both
 * now render from this file, so a fix is made once. Do not fork it back.
 *
 * The heading level is CLAIMED, not hardcoded — see ak_claim_h1(). On /calc/ the
 * calculator is the page and takes the h1; on the homepage the hero already has it,
 * so this becomes an h2. The LOOK is unaffected either way: `.calculator__title`
 * carries the type, the tag carries only the document outline.
 *
 * The "Довідка" help panel is rendered here, from an ACF field. It replaced a Divi
 * Popups section whose trigger was already dead on production.
 *
 * @package kalynyuk
 */

defined( 'ABSPATH' ) || exit;

$ak_id      = (int) get_queried_object_id();
$ak_heading = (string) ak_section_field( 'ak_calc_heading', $ak_id );
$ak_intro   = (string) ak_section_field( 'ak_calc_intro', $ak_id );
$ak_help    = ak_str( 'ak_calc_help', 'Довідка' );
$ak_help_html = (string) ak_section_field( 'ak_calc_help_content', $ak_id );

/*
 * ⚠️ THE INDEX IS NOW EDITABLE, AND THAT IS THE POINT.
 *
 * It used to be `const INDEXANTE_CONST = 0.02143` hardcoded in the inline script —
 * a Euribor snapshot frozen at the moment someone typed it. Euribor moves; the
 * constant did not, so the variable-rate results drifted further from reality every
 * month and only a developer could correct them. That matches the client's report
 * that the numbers went wrong "a few months ago".
 *
 * The default is deliberately the SAME 2.143, so publishing this change moves no
 * number on the page today. It only makes the number correctable without a deploy.
 */
$ak_indexante = ak_section_field( 'ak_calc_indexante', $ak_id );
$ak_indexante = ( '' === $ak_indexante || null === $ak_indexante ) ? 2.143 : (float) $ak_indexante;
?>
<section class="calculator" data-ak-indexante="<?php echo esc_attr( (string) $ak_indexante ); ?>">
	<div class="calculator__inner">

		<?php if ( '' !== $ak_heading || '' !== $ak_intro ) : ?>
			<div class="calculator__head">
				<?php if ( '' !== $ak_heading ) : ?>
					<?php $ak_h = ak_claim_h1(); ?>
					<<?php echo $ak_h; ?> class="calculator__title"><?php echo esc_html( $ak_heading ); ?></<?php echo $ak_h; ?>>
				<?php endif; ?>
				<?php if ( '' !== $ak_intro ) : ?>
					<p class="calculator__intro"><?php echo nl2br( esc_html( $ak_intro ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above. ?></p>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<?php
		/*
		 * A <button>, not the old `<a href="#infobox">`.
		 *
		 * That link pointed at a Divi popup section which no longer exists — and it
		 * had already stopped working on production before this migration: measured
		 * there as a 0×0 element whose click opened nothing. It was a control in name
		 * only. This is a real button with real state, and the panel below is ours.
		 */
		?>
		<?php if ( '' !== trim( $ak_help_html ) ) : ?>
			<button
				class="dovidka"
				type="button"
				aria-expanded="false"
				aria-controls="ak-calc-help"
				data-ak-help-open
			>
				<svg width="16" height="16" viewBox="0 0 16 16" aria-hidden="true" focusable="false">
					<circle cx="8" cy="8" r="6.5" fill="none" stroke="currentColor" stroke-width="1.5" />
					<path d="M8 7.2v3.4M8 5.2v.9" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
				</svg>
				<span><?php echo esc_html( $ak_help ); ?></span>
			</button>
		<?php endif; ?>

		<?php
		/*
		 * ⚠️ EVERY `id` AND `name="rate-type"` BELOW IS LOAD-BEARING.
		 *
		 * calculator.js addresses the DOM only by id — and six of them not through a
		 * literal `getElementById('…')` but as STRING ARGUMENTS to the sync helpers
		 * (`syncFormattedInput("expenses-range", "expenses-input")`), which a grep for
		 * getElementById does not find. Renaming one does not throw; it silently
		 * prints a wrong number. The 30-id contract is checked after every change.
		 *
		 * Classes are ours and free to change. Ids are not.
		 */
		$ak_fields = array(
			array( 'property-price', 'Вартість нерухомості', 50000, 1500000, 5000, 200000, 7 ),
			array( 'loan-amount', 'Сума першого внеску', 0, 1000000, 5000, 30000, 7 ),
			array( 'loan-term', 'Термін кредиту (у роках)', 5, 40, 1, 20, 2 ),
			array( 'salary', 'Зарплата', 0, 10000, 500, 3500, 5 ),
			array( 'net-income', 'Щомісячний чистий дохід', 0, 10000, 500, 2500, 5 ),
			array( 'expenses', 'Щомісячні витрати', 0, 10000, 250, 0, 5 ),
		);
		?>
		<div class="calculator__layout">

			<div class="calculator__fields">
				<?php foreach ( $ak_fields as $ak_f ) : ?>
					<?php list( $ak_key, $ak_label, $ak_min, $ak_max, $ak_step, $ak_val, $ak_len ) = $ak_f; ?>
					<div class="calculator__field">
						<label class="calculator__label" for="<?php echo esc_attr( $ak_key ); ?>-input">
							<span><?php echo esc_html( $ak_label ); ?></span>
							<span class="calculator__hint" aria-hidden="true">i</span>
						</label>

						<input
							class="calculator__input"
							type="text"
							inputmode="numeric"
							id="<?php echo esc_attr( $ak_key ); ?>-input"
							min="<?php echo esc_attr( $ak_min ); ?>"
							max="<?php echo esc_attr( $ak_max ); ?>"
							step="<?php echo esc_attr( $ak_step ); ?>"
							value="<?php echo esc_attr( $ak_val ); ?>"
							maxlength="<?php echo esc_attr( $ak_len ); ?>"
						/>

						<input
							class="calculator__range"
							type="range"
							id="<?php echo esc_attr( $ak_key ); ?>-range"
							min="<?php echo esc_attr( $ak_min ); ?>"
							max="<?php echo esc_attr( $ak_max ); ?>"
							step="<?php echo esc_attr( $ak_step ); ?>"
							value="<?php echo esc_attr( $ak_val ); ?>"
							aria-label="<?php echo esc_attr( $ak_label ); ?>"
						/>
					</div>
				<?php endforeach; ?>

				<?php
				/*
				 * The rate row spans both columns. TWO modes only — the Figma frame
				 * shows a third ("Вручну"), but updateCalculation() branches on exactly
				 * 'fixed' and 'variable' and initialises interestRate to 0, so a third
				 * option would compute the payment at 0% and understate it. Petr's call
				 * (2026-07-31): ship the two that work.
				 */
				?>
				<div class="calculator__field calculator__field--wide">
					<p class="calculator__label">
						<span>Процентна ставка <span class="calculator__label-note">(річна)</span></span>
						<span class="calculator__hint" aria-hidden="true">i</span>
					</p>

					<div class="calculator__rate">
						<div class="calculator__modes">
							<label class="calculator__mode">
								<input type="radio" name="rate-type" value="variable" />
								<span>Змінна</span>
							</label>
							<label class="calculator__mode">
								<input type="radio" name="rate-type" value="fixed" checked />
								<span>Фіксована</span>
							</label>
						</div>

						<?php
						/*
						 * Both steppers stay in the DOM — calculator.js toggles the
						 * `hidden` class on them and reads their inputs by id, so
						 * rendering only the active one would break the swap. `hidden`
						 * here is that legacy class, not the HTML attribute.
						 */
						?>
						<div class="calculator__stepper rate-stepper hidden" id="variable-stepper">
							<button class="calculator__step" type="button" aria-label="-">-</button>
							<span class="input-wrapper">
								<input class="calculator__step-value" type="text" inputmode="decimal" id="variable-rate-input" value="0,75" aria-label="Spread, %" />
								<span class="percent-symbol">%</span>
							</span>
							<button class="calculator__step" type="button" aria-label="+">+</button>
						</div>

						<div class="calculator__stepper rate-stepper" id="fixed-stepper">
							<button class="calculator__step" type="button" aria-label="-">-</button>
							<span class="input-wrapper">
								<input class="calculator__step-value" type="text" inputmode="decimal" id="fixed-rate-input" value="2,80" aria-label="TAN, %" />
								<span class="percent-symbol">%</span>
							</span>
							<button class="calculator__step" type="button" aria-label="+">+</button>
						</div>
					</div>
				</div>
			</div>

			<div class="calculator__result">
				<div class="calculator__headline">
					<div class="calculator__payment">
						<span class="calculator__metric-label">Mensalidade <span class="calculator__hint" aria-hidden="true">i</span></span>
						<p class="calculator__payment-value" id="mensalidade">0,00 €</p>
					</div>

					<div class="calculator__gauge">
						<svg id="dsti-svg" width="187" height="70" viewBox="0 0 191 73" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
							<path id="path-green" d="M2 71C6.53488 52 24.655 19.5 69.5 6" stroke="#6DC797" stroke-width="4" stroke-linecap="round"/>
							<path id="path-yellow" d="M75.9362 4.33528C82.5254 2.89915 90.8481 2.05155 100.065 2.66889" stroke="#F9D504" stroke-width="4" stroke-linecap="round"/>
							<path id="path-red" d="M107 3C145.5 6.52593 175.5 29.6963 189 71" stroke="#D23644" stroke-width="4" stroke-linecap="round"/>
							<circle id="gauge-dot" r="7" fill="#2A2011" transform="translate(2,71)"/>
						</svg>

						<div class="calculator__gauge-read" id="dsti-info">
							<p class="calculator__gauge-value" id="dsti-percent">—</p>
							<p class="calculator__gauge-caption">DSTI</p>
						</div>
					</div>
				</div>

				<?php
				$ak_metrics = array(
					array(
						array( 'montante', 'Montante', '0 €' ),
						array( 'prazo', 'Prazo', '0 Meses' ),
						array( 'ltv', 'LTV', '0%' ),
					),
					array(
						array( 'tan', 'TAN', '0.000%' ),
						array( 'indexante', 'Indexante', '0.000%' ),
						array( 'spread', 'Spread', '0.000%' ),
					),
				);
				?>
				<?php foreach ( $ak_metrics as $ak_row ) : ?>
					<div class="calculator__metrics">
						<?php foreach ( $ak_row as $ak_m ) : ?>
							<div class="calculator__metric">
								<span class="calculator__metric-label"><?php echo esc_html( $ak_m[1] ); ?> <span class="calculator__hint" aria-hidden="true">i</span></span>
								<p class="calculator__metric-value" id="<?php echo esc_attr( $ak_m[0] ); ?>"><?php echo esc_html( $ak_m[2] ); ?></p>
							</div>
						<?php endforeach; ?>
					</div>
				<?php endforeach; ?>

				<hr class="calculator__divider" />

				<div class="calculator__metrics calculator__metrics--taxes">
					<div class="calculator__metric">
						<span class="calculator__metric-label">Imposto Selo <span class="calculator__hint" aria-hidden="true">i</span></span>
						<p class="calculator__metric-value" id="imposto-selo">0 €</p>
					</div>
					<div class="calculator__metric">
						<span class="calculator__metric-label">IMT Cont. <span class="calculator__hint" aria-hidden="true">i</span></span>
						<p class="calculator__metric-value" id="imt-cont">0 €</p>
					</div>
				</div>

				<div class="calculator__form contact-form">
					<p class="calculator__form-title">Надсилайте нам розрахунок і ми з вами зв’яжемось</p>
					<?php echo do_shortcode( '[gravityform id="3" title="false" ajax="true"]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
			</div>
		</div>
	</div>

	<?php if ( '' !== trim( $ak_help_html ) ) : ?>
		<?php
		/*
		 * The help panel, replacing the Divi Popups section. Deliberately NOT a
		 * <dialog>: Divi's stylesheet and the legacy calculator CSS both fight the
		 * backdrop, and a plain overlay with the drawer's proven open/close contract
		 * (Escape, scrim click, focus moved in and restored, body scroll locked) is
		 * fewer unknowns than debugging ::backdrop against 16 KB of !important.
		 *
		 * `hidden` on the wrapper means it costs nothing until opened, and a visitor
		 * without JS simply never sees a button that would not have worked anyway.
		 */
		?>
		<div class="calc-help" id="ak-calc-help" hidden data-ak-help>
			<div class="calc-help__scrim" data-ak-help-close></div>

			<div class="calc-help__dialog" role="dialog" aria-modal="true" aria-labelledby="ak-calc-help-title">
				<div class="calc-help__bar">
					<p class="calc-help__title" id="ak-calc-help-title"><?php echo esc_html( $ak_help ); ?></p>

					<button class="calc-help__close" type="button" data-ak-help-close>
						<span class="u-visually-hidden"><?php echo esc_html( ak_str( 'ak_menu_close', 'Закрити меню' ) ); ?></span>
						<svg width="24" height="24" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
							<path d="M5 5l14 14M19 5L5 19" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" />
						</svg>
					</button>
				</div>

				<div class="calc-help__body">
					<?php echo wp_kses_post( $ak_help_html ); ?>
				</div>
			</div>
		</div>
	<?php endif; ?>
</section>
