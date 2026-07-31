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
 * The "Довідка" popup it links to (#infobox) is still the SECOND Divi section on the
 * page — deliberately not migrated. It is 6 KB of editorial help text, and moving it
 * into the theme would hardcode client copy that belongs in the editor.
 *
 * @package kalynyuk
 */

defined( 'ABSPATH' ) || exit;

$ak_id      = (int) get_queried_object_id();
$ak_heading = (string) ak_section_field( 'ak_calc_heading', $ak_id );
$ak_intro   = (string) ak_section_field( 'ak_calc_intro', $ak_id );
$ak_help    = ak_str( 'ak_calc_help', 'Довідка' );
?>
<section class="calculator">
	<div class="calculator__inner">

		<?php if ( '' !== $ak_heading || '' !== $ak_intro ) : ?>
			<div class="calculator__head">
				<?php if ( '' !== $ak_heading ) : ?>
					<h1 class="calculator__title"><?php echo esc_html( $ak_heading ); ?></h1>
				<?php endif; ?>
				<?php if ( '' !== $ak_intro ) : ?>
					<p class="calculator__intro"><?php echo nl2br( esc_html( $ak_intro ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above. ?></p>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<a class="dovidka" href="#infobox"><span class="tooltip"></span> <?php echo esc_html( $ak_help ); ?></a>

		<div class="calculator__panel">
			
			<div class="calculator-grid">
			
			  <!-- Property Price -->
			  <div class="input-block property-price">
			    <div class="input-row">
			      <label for="property-price-input" class="input-label">Вартість нерухомості <span class="tooltip"></span></label>
			      <div class="input-inline-control">
			        <input type="text" id="property-price-input" min="50000" max="1500000" step="5000" value="200000"
			          maxlength="7" />
			      </div>
			    </div>
			    <input type="range" id="property-price-range" min="50000" max="1500000" step="5000" value="200000" />
			  </div>
			
			  <!-- Down Payment -->
			  <div class="input-block loan-term-input">
			    <div class="input-row">
			      <label for="loan-amount-input" class="input-label">Сума першого внеску <span class="tooltip"></span></label>
			      <div class="input-inline-control">
			        <input type="text" id="loan-amount-input" min="0" max="1000000" step="5000" value="30000" maxlength="7" />
			      </div>
			    </div>
			    <input type="range" id="loan-amount-range" min="0" max="1000000" step="5000" value="30000" />
			  </div>
			
			  <!-- Loan Term -->
			  <div class="input-block loan-term">
			    <div class="input-row">
			      <label for="loan-term-input" class="input-label">Термін кредиту (у роках) <span class="tooltip"></span></label>
			      <div class="input-inline-control">
			        <input type="text" id="loan-term-input" min="5" max="40" step="1" value="20" maxlength="2" />
			      </div>
			    </div>
			    <input type="range" id="loan-term-range" min="5" max="40" step="1" value="20" />
			  </div>
			
			  <!-- Salary -->
			  <div class="input-block salary">
			    <div class="input-row">
			      <label for="salary-input" class="input-label">Зарплата <span class="tooltip"></span></label>
			      <div class="input-inline-control">
			        <input type="text" id="salary-input" min="0" max="10000" step="500" value="3500" maxlength="5" />
			      </div>
			    </div>
			    <input type="range" id="salary-range" min="0" max="10000" step="500" value="3500" />
			  </div>
			
			  <!-- Net Income -->
			  <div class="input-block net-income">
			    <div class="input-row">
			      <label for="net-income-input" class="input-label">Щомісячний чистий дохід <span class="tooltip"></span></label>
			      <div class="input-inline-control">
			        <input type="text" id="net-income-input" min="0" max="10000" step="500" value="2500" maxlength="5" />
			      </div>
			    </div>
			    <input type="range" id="net-income-range" min="0" max="10000" step="500" value="2500" />
			  </div>
			
			  <!-- Expenses -->
			  <div class="input-block expenses">
			    <div class="input-row">
			      <label for="expenses-input" class="input-label">Щомісячні витрати <span class="tooltip"></span></label>
			      <div class="input-inline-control">
			        <input type="text" id="expenses-input" min="0" max="10000" step="250" value="0" maxlength="5" />
			      </div>
			    </div>
			    <input type="range" id="expenses-range" min="0" max="10000" step="250" value="0" />
			  </div>
			
			  <!-- Interest Rate (full width) -->
			  <div class="input-block interest-rate" style="grid-column: span 2;">
			    <label class="input-label">Процентна ставка <span class="rate-annual">(річна)</span> <span
			        class="tooltip"></span></label>
			
			    <div class="interest-rate-wrapper">
			      <div class="interest-rate-options">
			        <label><input type="radio" name="rate-type" value="variable" /> Змінна (Spread)</label>
			        <label><input type="radio" name="rate-type" value="fixed" checked /> Фіксована (TAN)</label>
			      </div>
			
			      <div class="rate-stepper-wrapper">
			        <!-- variable -->
			        <div id="variable-stepper" class="rate-stepper">
			          <button class="rate-minus" data-target="variable">−</button>
			          <div class="input-wrapper">
			            <input type="number" id="variable-rate-input" value="0.7" step="0.01" min="0" max="100" />
			            <span class="percent-symbol">%</span>
			          </div>
			          <button class="rate-plus" data-target="variable">+</button>
			        </div>
			
			        <!-- fixed -->
			        <div id="fixed-stepper" class="rate-stepper hidden">
			          <button class="rate-minus" data-target="fixed">−</button>
			          <div class="input-wrapper">
			            <input type="number" id="fixed-rate-input" value="2.80" step="0.01" min="0" max="100" />
			            <span class="percent-symbol">%</span>
			          </div>
			          <button class="rate-plus" data-target="fixed">+</button>
			        </div>
			      </div>
			    </div>
			  </div>
			
			</div>
			

			
			<div class="calculator-result">
			  <!-- Header with payment and DSTI -->
			  <div class="result-header">
			    <div class="mensalidade">
			      <span class="label"><span class="opc56">Mensalidade </span><span class="tooltip"></span></span>
			      <div class="value" id="mensalidade">0,00 €</div>
			    </div>
			    <div class="dsti">
			      <div class="dsti-gauge">
			        <svg id="dsti-svg" width="187" height="70" viewBox="0 0 191 73" fill="none" xmlns="http://www.w3.org/2000/svg">
			          <path id="path-green" d="M2 71C6.53488 52 24.655 19.5 69.5 6" stroke="#6DC797" stroke-width="4" stroke-linecap="round"/>
			          <path id="path-yellow" d="M75.9362 4.33528C82.5254 2.89915 90.8481 2.05155 100.065 2.66889" stroke="#F9D504" stroke-width="4" stroke-linecap="round"/>
			          <path id="path-red" d="M107 3C145.5 6.52593 175.5 29.6963 189 71" stroke="#D23644" stroke-width="4" stroke-linecap="round"/>
			          <circle id="gauge-dot" r="7" fill="#2A2011" transform="translate(2,71)"/>
			        </svg>
			        <div id="dsti-info">
			          <div class="dsti-percent" id="dsti-percent">—</div>
			          <span>DSTI</span>
			        </div>
			      </div>
			    </div>
			  </div>
			
			  <!-- Main data -->
			  <div class="result-data">
			    <div class="result-row">
			      <div class="item montant">
			        <span class="label">Montante <span class="tooltip"></span></span>
			        <div class="value" id="montante">0 €</div>
			      </div>
			      <div class="item prazo">
			        <span class="label">Prazo <span class="tooltip tlt-right"></span></span>
			        <div class="value" id="prazo">0 Meses</div>
			      </div>
			      <div class="item ltv">
			        <span class="label">LTV <span class="tooltip tlt-right"></span></span>
			        <div class="value" id="ltv">0%</div>
			      </div>
			    </div>
			    <div class="result-row">
			      <div class="item tan">
			        <span class="label">TAN <span class="tooltip"></span></span>
			        <div class="value" id="tan">0.000%</div>
			      </div>
			      <div class="item indexante">
			        <span class="label">Indexante <span class="tooltip tlt-right"></span></span>
			        <div class="value" id="indexante">0.000%</div>
			      </div>
			      <div class="item spread">
			        <span class="label">Spread <span class="tooltip tlt-right"></span></span>
			        <div class="value" id="spread">0.000%</div>
			      </div>
			    </div>
			  </div>
			
			  <hr class="divider" />
			
			  <!-- Taxes -->
			  <div class="result-data">
			    <div class="result-row">
			      <div class="item i-selo">
			        <span class="label">Imposto Selo <span class="tooltip"></span></span>
			        <div class="value" id="imposto-selo">0 €</div>
			      </div>
			      <div class="item imt-cont">
			        <span class="label">IMT Cont. <span class="tooltip tlt-right"></span></span>
			        <div class="value" id="imt-cont">0 €</div>
			      </div>
			    </div>
			  </div>
			
			  <!-- Form -->
			  <div class="contact-form">
			    <p style="font-size: 16px;">Надсилайте нам розрахунок і ми з вами зв'яжемось</p>
			    <?php echo do_shortcode( '[gravityform id="3" title="false" ajax="true"]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			  </div>
			</div>
			
		</div>
	</div>
</section>
