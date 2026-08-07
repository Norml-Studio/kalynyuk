<?php
/**
 * ACF options page for the site chrome, plus the translatable chrome strings.
 *
 * ⚠️ THE SPLIT, AND WHY. Polylang does not translate ACF options out of the box —
 * options are global, not per-post. The usual workaround (a language-suffixed
 * options page via `acf/validate_post_id`) rewrites every options read in admin
 * and front end, which is a lot of blast radius for a phone number. So:
 *
 *   · TEXT that needs translating  → pll_register_string() + pll__().
 *     Editable in Polylang → Translations → Strings. Works in free Polylang.
 *   · CONFIG that does not         → ACF options, one global set.
 *     A phone number, an Instagram URL and a logo do not need translating.
 *   · The CTA's TARGET             → one ACF page field, resolved per language at
 *     render time by ak_translate_id(). No duplicate fields, no per-language page.
 *
 * Fields are registered in PHP rather than through the admin so they are
 * version-controlled and travel with the theme. ACF JSON sync is not used here
 * for the same reason — there is exactly one field group and it is chrome, not
 * editorial content.
 *
 * @package kalynyuk
 */

defined( 'ABSPATH' ) || exit;

/**
 * Bail early everywhere if ACF is missing, so the theme still renders.
 *
 * @return bool
 */
function ak_has_acf() {
	return function_exists( 'acf_add_options_page' ) && function_exists( 'get_field' );
}

/**
 * Register the options page.
 */
function ak_acf_options_page() {
	if ( ! ak_has_acf() ) {
		return;
	}

	acf_add_options_page(
		array(
			'page_title' => __( 'Site chrome', 'kalynyuk' ),
			'menu_title' => __( 'Site chrome', 'kalynyuk' ),
			'menu_slug'  => 'ak-chrome',
			'capability' => 'edit_theme_options',
			'icon_url'   => 'dashicons-align-center',
			'position'   => 60,
			'redirect'   => false,
		)
	);
}
add_action( 'acf/init', 'ak_acf_options_page' );

/**
 * Register the chrome field group.
 */
function ak_acf_chrome_fields() {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	acf_add_local_field_group(
		array(
			'key'      => 'group_ak_chrome',
			'title'    => __( 'Site chrome', 'kalynyuk' ),
			'location' => array(
				array(
					array(
						'param'    => 'options_page',
						'operator' => '==',
						'value'    => 'ak-chrome',
					),
				),
			),
			'fields'   => array(
				array(
					'key'     => 'field_ak_cta_page',
					'label'   => __( 'Primary CTA target — page', 'kalynyuk' ),
					'name'    => 'ak_cta_page',
					'type'    => 'post_object',
					'post_type' => array( 'page' ),
					'return_format' => 'id',
					'allow_null' => 1,
					'ui'      => 1,
					'instructions' => __( 'One CTA, shared by the header and the footer — header-standard requires they mirror each other. The page is translated automatically per language, so pick the Ukrainian one. Ignored if the external URL below is set.', 'kalynyuk' ),
				),
				array(
					'key'   => 'field_ak_cta_url',
					'label' => __( 'Primary CTA target — external URL', 'kalynyuk' ),
					'name'  => 'ak_cta_url',
					'type'  => 'url',
					'instructions' => __( 'Takes precedence over the page. Use for an off-site form (e.g. Tally). External links open in a new tab automatically — the decision is made by host, never hardcoded.', 'kalynyuk' ),
				),
				array(
					'key'   => 'field_ak_phone',
					'label' => __( 'Phone', 'kalynyuk' ),
					'name'  => 'ak_phone',
					'type'  => 'text',
					'instructions' => __( 'Displayed as typed; the tel: link is derived automatically.', 'kalynyuk' ),
				),
				array(
					'key'   => 'field_ak_email',
					'label' => __( 'Email', 'kalynyuk' ),
					'name'  => 'ak_email',
					'type'  => 'email',
				),
				/*
				 * Social links as a REPEATER, not one field per network.
				 *
				 * The old shape hardcoded `ak_telegram` and `ak_instagram`, which meant
				 * adding Facebook or YouTube was a code change in three places (the
				 * field group, the drawer and the footer). A repeater makes it an
				 * admin action. Existing values are migrated automatically — see
				 * ak_migrate_socials().
				 *
				 * Label is a plain text field rather than a fixed list: the drawer and
				 * footer render the label verbatim, and it needs to be writable in
				 * Ukrainian ("Телеграм", not "Telegram").
				 */
				array(
					'key'          => 'field_ak_socials',
					'label'        => __( 'Social links', 'kalynyuk' ),
					'name'         => 'ak_socials',
					'type'         => 'repeater',
					'layout'       => 'table',
					'button_label' => __( 'Add link', 'kalynyuk' ),
					'instructions' => __( 'Rendered in the mobile menu and the footer, in this order. One source — edit here and both update.', 'kalynyuk' ),
					'sub_fields'   => array(
						array(
							'key'      => 'field_ak_social_label',
							'label'    => __( 'Label', 'kalynyuk' ),
							'name'     => 'label',
							'type'     => 'text',
							'required' => 1,
						),
						array(
							'key'      => 'field_ak_social_url',
							'label'    => __( 'URL', 'kalynyuk' ),
							'name'     => 'url',
							'type'     => 'url',
							'required' => 1,
						),
					),
				),
				array(
					'key'          => 'field_ak_show_lang',
					'label'        => __( 'Show the language switcher', 'kalynyuk' ),
					'name'         => 'ak_show_lang',
					'type'         => 'true_false',
					'default_value' => 1,
					'ui'           => 1,
					'instructions' => __( 'Hides the switcher in the header and the mobile menu without deactivating Polylang. Useful while a second language is still being translated.', 'kalynyuk' ),
				),
				array(
					'key'   => 'field_ak_logo',
					'label' => __( 'Logo (SVG)', 'kalynyuk' ),
					'name'  => 'ak_logo',
					'type'  => 'image',
					'return_format' => 'array',
					'mime_types' => 'svg',
					'instructions' => __( 'The full lockup — uploads/2026/05/logotype.svg (216×32). NOT Anna-Kalynyuk.svg, which is the wordmark only.', 'kalynyuk' ),
				),
				array(
					'key'           => 'field_ak_author_photo',
					'label'         => __( 'Author photo', 'kalynyuk' ),
					'name'          => 'ak_author_photo',
					'type'          => 'image',
					'return_format' => 'id',
					'preview_size'  => 'thumbnail',
					'instructions'  => __( 'Shown in the author card on article pages. An option rather than a user avatar so it needs no Gravatar account and no external request from an EU visitor.', 'kalynyuk' ),
				),

				/*
				 * ─── Regulatory disclosure ────────────────────────────────────
				 * A credit intermediary operating in Portugal must publish its
				 * Banco de Portugal registration number and the alternative
				 * dispute-resolution (RAL) entities it is bound to. This is a
				 * LEGAL requirement, not footer decoration — do not hide it, do
				 * not shrink it below the `body-xs` role, and do not remove a
				 * field because it looks empty.
				 */
				array(
					'key'   => 'field_ak_reg_tab',
					'label' => __( 'Regulatory disclosure (legally required)', 'kalynyuk' ),
					'type'  => 'tab',
				),
				array(
					'key'   => 'field_ak_intermediary_no',
					'label' => __( 'Credit intermediary number', 'kalynyuk' ),
					'name'  => 'ak_intermediary_no',
					'type'  => 'text',
					'instructions' => __( 'Rendered as “Intermediário de crédito n.º {value}”. Required by Banco de Portugal.', 'kalynyuk' ),
				),
				array(
					'key'   => 'field_ak_intermediary_url',
					'label' => __( 'Banco de Portugal register URL', 'kalynyuk' ),
					'name'  => 'ak_intermediary_url',
					'type'  => 'url',
				),
				array(
					'key'   => 'field_ak_complaints_url',
					'label' => __( 'Livro de Reclamações URL', 'kalynyuk' ),
					'name'  => 'ak_complaints_url',
					'type'  => 'url',
				),
				array(
					'key'   => 'field_ak_cniacc_url',
					'label' => __( 'CNIACC URL', 'kalynyuk' ),
					'name'  => 'ak_cniacc_url',
					'type'  => 'url',
					'instructions' => __( 'Leave empty and the label still renders, as plain text rather than a link.', 'kalynyuk' ),
				),
				array(
					'key'   => 'field_ak_caccl_url',
					'label' => __( 'CACCL URL', 'kalynyuk' ),
					'name'  => 'ak_caccl_url',
					'type'  => 'url',
					'instructions' => __( 'Leave empty and the label still renders, as plain text rather than a link.', 'kalynyuk' ),
				),
			),
		)
	);
}
add_action( 'acf/init', 'ak_acf_chrome_fields' );

/**
 * The calculator's ⓘ help copy, keyed by badge.
 *
 * ⚠️ PORTED VERBATIM, NOT WRITTEN. Every string below was extracted from page 11's
 * `_et_pb_custom_css`, where the Divi original kept its tooltip bodies as CSS
 * `content:` strings — which is why they were invisible to every content search and
 * why they did not survive into the native rebuild on their own. They quote bank
 * thresholds (70–80% LTV for non-residents, terms of 10–40 years, ages 75–80) on the
 * site of a licensed credit intermediary, so they are Anna's reviewed copy: fix a
 * wording question with her, do not "improve" it here.
 *
 * ⚠️ The array was GENERATED from the meta, not retyped — 1 460 characters of which
 * two carry a straight apostrophe (об'єкта, обов'язкові) and one a pair of straight
 * double quotes ("на руки"). ak_str() resolves by SOURCE string, so a single
 * transcription slip would leave a translation nothing ever looks up.
 *
 * ⚠️ THE SELECTOR NAMES IN THE ORIGINAL CSS LIE, and one of them matters: `down` came
 * from `.loan-term-input`, whose label in the markup is «Сума першого внеску» — the
 * DOWN PAYMENT, not the term. The map was verified by reading each block's visible
 * label out of `post_content` rather than trusting its class. `.loan-term` is the
 * separate, genuine term tooltip.
 *
 * ⚠️ FOUR MORE TOOLTIPS EXIST IN THAT CSS AND ARE DELIBERATELY NOT PORTED:
 * `.loanam`, `.lterm`, `.lprc` and `.imt-ilhas` are not in `post_content` at all —
 * orphan rules for modules that no longer exist, the same class of debris as the
 * consent-checkbox rule in `docs/05-issues.md`. Three of them duplicate a tooltip we
 * do port; `.imt-ilhas` belonged to an islands metric this design does not have.
 * Its body is a better definition of IMT than the live `.imt-cont` one it sat beside
 * ("Податок на передачу нерухомості" vs "Спеціальні податки") — the two look
 * swapped. We ship the LIVE text, because that is what visitors read today, and the
 * improvement is Anna's call rather than a silent edit.
 *
 * Keys are ours; they name the badge, not the old selector.
 *
 * @return array<string,string> key => Ukrainian source string.
 */
function ak_calc_tips() {
	$tips = array(
		'price'       => 'Вкажіть загальну ціну об\'єкта нерухомості, який плануєте придбати. Це повна ринкова вартість житла.',
		'down'        => 'Сума, яку ви маєте сплатити власними коштами при оформленні іпотеки. Чим більший перший внесок — тим вища ймовірність погодження кредиту на вигідних умовах.',
		'term'        => 'Період, на який береться іпотека. У Португалії стандартний термін становить від 10 до 40 років, залежно від вашого віку та доходу.',
		'salary'      => 'Сума вашого офіційного щомісячного доходу до оподаткування. Банк враховує дохід усіх співпозичальників.',
		'income'      => 'Сума, яку ви отримуєте "на руки" після оподаткування, щомісяця. Цей показник важливий для розрахунку співвідношення боргового навантаження',
		'expenses'    => 'Ваші регулярні обов\'язкові платежі та витрати: інші кредити, аліменти, утримання дітей тощо. Враховується для обчислення фінансового навантаження.',
		'rate'        => 'Фіксована або змінна річна процентна ставка банку по іпотеці. Остаточна ставка залежить від типу кредиту й обраної банківської програми',
		'mensalidade' => 'Щомісячний платіж, який ви сплачуватимете за кредитом протягом усього терміну.',
		'montante'    => 'Загальна сума, яку ви плануєте позичити в банку.',
		'prazo'       => 'Тривалість кредиту в місяцях',
		'ltv'         => 'Відсоток від вартості нерухомості, який фінансується банком. Чим нижче, тим краще для схвалення.',
		'tan'         => 'Загальна ставка без урахування додаткових витрат. Вона включає indexante + spread.',
		'indexante'   => 'Змінна частина відсоткової ставки, яка базується на Euribor.',
		'spread'      => 'Фіксована надбавка, яку банк додає до indexante.',
		'selo'        => 'Податок, який сплачується при укладенні кредитного договору.',
		'imt'         => 'Спеціальні податки на материковій частині Португалії.',
	);

	return $tips;
}

/**
 * One tip, translated.
 *
 * @param string $key Tip key from ak_calc_tips().
 * @return string Empty string when the key is unknown, so a renamed key drops the
 *                badge rather than printing a PHP notice into the markup.
 */
function ak_calc_tip( $key ) {
	$tips = ak_calc_tips();

	return isset( $tips[ $key ] ) ? ak_str( 'ak_calc_tip_' . $key, $tips[ $key ] ) : '';
}

/**
 * One ⓘ help control: the button and the panel it toggles.
 *
 * ⚠️ A DISCLOSURE, NOT `role="tooltip"`. The bodies run to 250 characters and have to
 * be readable on a phone, which rules out a hover-only tooltip; and WCAG 2.2 §1.4.13
 * requires content shown on hover to be dismissible, hoverable and persistent, which a
 * CSS-only `:hover::after` — what the Divi original used — cannot be. `aria-expanded` +
 * `aria-controls` is also the contract the «Довідка» panel in this same section already
 * uses, so there is one pattern here rather than two.
 *
 * ⚠️ The button is rendered as a SIBLING of the `<label>`, never inside it. `<button>`
 * is a labelable element, and a `<label>` may not contain a labelable descendant other
 * than its own control — nesting it would be invalid HTML. (Activation is not the
 * problem: the spec already stops label forwarding for clicks on interactive
 * descendants. Validity is.)
 *
 * The accessible name carries the FIELD LABEL, so the 16 buttons are distinguishable in
 * a screen reader's control list instead of reading as sixteen identical "Пояснення".
 *
 * @param string $key   Tip key from ak_calc_tips().
 * @param string $label The visible label this tip explains, already translated.
 * @return string Escaped HTML, or '' when there is no copy for the key.
 */
function ak_calc_hint_html( $key, $label ) {
	$text = ak_calc_tip( $key );

	// No copy, no badge. A decorative ⓘ that opens nothing is what this replaces.
	if ( '' === trim( $text ) ) {
		return '';
	}

	$id = 'ak-tip-' . $key;

	return sprintf(
		'<span class="calc-tip" data-ak-tip>'
			. '<button class="calc-tip__button" type="button" aria-expanded="false" aria-controls="%1$s">'
				. '<span class="u-visually-hidden">%2$s</span>'
				. '<span class="calc-tip__glyph" aria-hidden="true">i</span>'
			. '</button>'
			// `hidden` is load-bearing, not belt-and-braces: without it the absolutely
			// positioned panel still occupies layout, and 16 closed 310px panels push the
			// document into a horizontal scroll at every viewport width.
			. '<span class="calc-tip__panel" id="%1$s" hidden>%3$s</span>'
		. '</span>',
		esc_attr( $id ),
		esc_html( sprintf( ak_str( 'ak_calc_tip_label', 'Пояснення: %s' ), $label ) ),
		esc_html( $text )
	);
}

/**
 * Register the chrome's translatable strings with Polylang.
 *
 * These are editable per language in Polylang → Translations → Strings, so the
 * client can change the CTA label in Portuguese without a deploy.
 */
function ak_register_pll_strings() {
	if ( ! function_exists( 'pll_register_string' ) ) {
		return;
	}

	$strings = array(
		'ak_cta_label'    => 'Отримати консультацію',
		/*
		 * ⚠️ The source must be UKRAINIAN. This read 'Todos os direitos reservados.' — the
		 * Portuguese — so the Ukrainian site rendered "© 2026 Todos os direitos reservados."
		 * and Portuguese looked right only by accident, because the source doubled as the pt
		 * translation. ak_str() resolves by SOURCE string, and Polylang always returns the
		 * source for the default language, so no translation could have fixed it.
		 */
		'ak_copyright'    => 'Усі права захищено.',
		'ak_privacy'      => 'Конфіденційність',
		'ak_menu_open'    => 'Меню',
		'ak_menu_close'   => 'Закрити меню',
		'ak_back'         => 'Назад',
		'ak_telegram'     => 'Телеграм',
		'ak_instagram'    => 'Інстаграм',
		'ak_lang_label'   => 'Мова',
		'ak_form_title'   => 'Форма для зворотнього зв’язку',
		'ak_to_top'       => 'Вгору',
	);

	// Article chrome — Figma 1139:10037.
	$strings += array(
		'ak_author_name'      => 'Анна Калинюк',
		'ak_author_role'      => 'Засновниця Anna Kalynyuk Mortgage',
		'ak_read_minutes_one' => '%d хвилина',
		'ak_read_minutes_few' => '%d хвилини',
		'ak_read_minutes_many' => '%d хвилин',
		'ak_toc_title'      => 'Зміст:',
		'ak_share_title'    => 'Поширити статтю',
		'ak_share_facebook' => 'Фейсбук',
		'ak_share_linkedin' => 'Лінкедин',
		'ak_share_copy'     => 'Скопіювати посилання',
		'ak_share_copied'   => 'Скопійовано',
		'ak_related_title'  => 'Інші статті',
		'ak_calc_help'      => 'Довідка',
	);

	/*
	 * Calculator chrome — Figma 1130:4130.
	 *
	 * ⚠️ These were HARDCODED UKRAINIAN in template-parts/sections/calculator.php, which is
	 * exactly what multilingual rule 3 exists to prevent. The consequence was visible rather
	 * than theoretical: the calculator is the one section that renders natively on /pt/ with
	 * `.et_pb_section` down to 0, so with the labels frozen in the template the Portuguese
	 * homepage showed a Portuguese page with a fully Ukrainian calculator in the middle of it.
	 *
	 * The six field labels double as the `aria-label` on their range input, so each is
	 * resolved ONCE in the template and used for both — a screen-reader user gets the same
	 * words a sighted one reads, in the same language.
	 *
	 * ⚠️ NOT registered here, deliberately: the metric labels (Mensalidade, Montante, Prazo,
	 * LTV, TAN, Indexante, Spread, Imposto Selo, IMT Cont.) and the two stepper aria-labels
	 * ('TAN, %' / 'Spread, %'). They are Portuguese banking terms and acronyms that read the
	 * same in every language, and they are the terms a client will meet on the bank's own
	 * paperwork — the same call already made for «Livro de Reclamações» in the footer.
	 * Ukrainian is the DEFAULT language, so Polylang returns the source for it regardless;
	 * registering them would buy nothing on uk and invite a pt "translation" that renames a
	 * term away from the one the bank uses.
	 */
	$strings += array(
		'ak_calc_f_price'    => 'Вартість нерухомості',
		'ak_calc_f_down'     => 'Сума першого внеску',
		'ak_calc_f_term'     => 'Термін кредиту (у роках)',
		'ak_calc_f_salary'   => 'Зарплата',
		'ak_calc_f_income'   => 'Щомісячний чистий дохід',
		'ak_calc_f_expenses' => 'Щомісячні витрати',
		'ak_calc_rate'       => 'Процентна ставка',
		/*
		 * A separate string from the term above because it is a separate node — the note
		 * carries $weight-regular against the label's bold, so it cannot be one text run.
		 * Safe as a fragment only because it is parenthetical AND trailing in Ukrainian and
		 * Portuguese alike; a language that needs it before the term makes ak_calc_rate a
		 * format string instead of translating this in place.
		 */
		'ak_calc_rate_note'  => '(річна)',
		'ak_calc_variable'   => 'Змінна',
		'ak_calc_fixed'      => 'Фіксована',
		// The third rate mode and the head CTA, both added 2026-08-05.
		'ak_calc_manual'     => 'Вручну',
		'ak_calc_cta'        => 'Розрахувати',
		// The term field's value suffix. It is written into the INPUT'S VALUE by
		// formatYears(), which is why it hid from the Cyrillic sweep and shipped as
		// «20 років» on the Portuguese page.
		'ak_calc_years'      => 'років',
		'ak_calc_form_title' => 'Надсилайте нам розрахунок і ми з вами зв’яжемось',
		// The ⓘ buttons' accessible name. A FORMAT STRING — %s is the field label, so a
		// screen reader announces "Пояснення: Вартість нерухомості" rather than 16
		// identical "Пояснення" buttons with no way to tell them apart.
		'ak_calc_tip_label'  => 'Пояснення: %s',
	);

	/*
	 * The ⓘ bodies, from the one source in ak_calc_tips(). Registered in a loop rather
	 * than listed again here — 1 460 characters duplicated between a registration list
	 * and the template is exactly how the two drift out of sync and translations quietly
	 * stop resolving.
	 */
	foreach ( ak_calc_tips() as $ak_tip_key => $ak_tip_text ) {
		$strings[ 'ak_calc_tip_' . $ak_tip_key ] = $ak_tip_text;
	}

	// Testimonials — Figma 1130:9143. These replace Review Wall's own strings, which are
	// plain get_option() values: ONE global string for all four languages, which is the
	// whole reason that section was rebuilt.
	$strings += array(
		'ak_reviews_all'    => 'Усі відгуки',
		'ak_reviews_prev'   => 'Попередні відгуки',
		'ak_reviews_next'   => 'Наступні відгуки',
		// A FORMAT STRING, like ak_read_minutes_* — word order round a number is not the same
		// in every language, and 'Avaliação 5,0' is right where '5,0 Avaliação' is not.
		'ak_reviews_rating' => '%s Оцінка',
		'ak_faq_all'        => 'Усі запитання і відповіді',
	);

	// Assistive-technology strings. They were __() with the theme text domain, which never
	// translated — the theme ships no languages/ folder — so a Portuguese screen-reader user
	// heard them in English. Only ak_skip_link is ever visible (on keyboard focus).
	$strings += array(
		'ak_skip_link'   => 'Перейти до вмісту',
		'ak_footer_nav'  => 'Навігація у футері',
		// A FORMAT STRING — %s is the current language name.
		'ak_lang_switch' => 'Мова: %s. Змінити мову',
	);

	foreach ( $strings as $name => $default ) {
		pll_register_string( $name, $default, 'kalynyuk' );
	}

	/*
	 * The socials repeater, registered from its ACF rows rather than from the list above.
	 *
	 * `ak_telegram` / `ak_instagram` are already there and already translated, and because
	 * ak_str() looks up by SOURCE string those two work whichever way they are registered.
	 * This loop is for the row an editor adds LATER: without it, a fifth social would render
	 * in Ukrainian on every language and never appear in the Strings screen, so nobody would
	 * even see that it needed translating — the same silent failure the labels just had.
	 *
	 * ⚠️ ADMIN ONLY. Registration only populates the Strings screen; translation itself does
	 * not need it. Reading an ACF options repeater on every front-end request to build a
	 * screen no visitor opens is not worth it.
	 */
	if ( is_admin() && function_exists( 'get_field' ) ) {
		$rows = get_field( 'ak_socials', 'option' );

		if ( is_array( $rows ) ) {
			foreach ( $rows as $i => $row ) {
				if ( ! empty( $row['label'] ) ) {
					pll_register_string( 'ak_social_' . $i, (string) $row['label'], 'kalynyuk' );
				}
			}
		}
	}
}
add_action( 'init', 'ak_register_pll_strings' );

/**
 * Translate one of the chrome strings, with a safe fallback.
 *
 * @param string $name    Registered string name.
 * @param string $default Default (Ukrainian) value.
 * @return string
 */
function ak_str( $name, $default ) {
	unset( $name );

	return function_exists( 'pll__' ) ? pll__( $default ) : $default;
}

/**
 * Read a chrome option with a fallback.
 *
 * @param string $field Field name.
 * @param mixed  $fallback Value when ACF is absent or the field is empty.
 * @return mixed
 */
function ak_chrome( $field, $fallback = '' ) {
	if ( ! ak_has_acf() ) {
		return $fallback;
	}

	$value = get_field( $field, 'option' );

	return ( null === $value || '' === $value || false === $value ) ? $fallback : $value;
}

/**
 * The site logo, as an <img> or the site title.
 *
 * Falls back to Divi's configured logo (et_divi.divi_logo) so the header renders
 * correctly before anyone visits the options page.
 *
 * @return string Escaped HTML.
 */
function ak_logo_html() {
	$logo = ak_chrome( 'ak_logo' );
	$url  = '';
	$alt  = get_bloginfo( 'name' );
	$w    = 0;
	$h    = 0;

	if ( is_array( $logo ) && ! empty( $logo['url'] ) ) {
		$url = $logo['url'];
		$alt = $logo['alt'] ? $logo['alt'] : $alt;
		$w   = (int) ( $logo['width'] ?? 0 );
		$h   = (int) ( $logo['height'] ?? 0 );
	} else {
		/*
		 * ⚠️ DO NOT fall back to et_divi.divi_logo. It points at
		 * uploads/2025/07/Anna-Kalynyuk.svg, which is 128×19 and contains a SINGLE
		 * path — the wordmark ONLY, with no "AK" monogram and no
		 * "INTERMEDIÁRIO DE CRÉDITO" line. The lockup the old header actually
		 * rendered is uploads/2026/05/logotype.svg (216×32, 38 paths), referenced
		 * directly inside Divi header layout 284, not through Theme Options.
		 *
		 * Found the hard way: the first build of this header used divi_logo and
		 * shipped a logo missing two thirds of the mark.
		 */
		$fallback = get_page_by_path( 'logotype', OBJECT, 'attachment' );

		if ( $fallback ) {
			$url = wp_get_attachment_url( $fallback->ID );
		}
	}

	if ( ! $url ) {
		return esc_html( get_bloginfo( 'name' ) );
	}

	/*
	 * Width/height attributes are emitted only when both are known. SVG
	 * attachments frequently report 0×0 in WordPress, and asserting the design's
	 * 216×32 on an asset with a different intrinsic ratio distorts it — which is
	 * exactly what happened on the first pass. When they are unknown, CSS sizes by
	 * height and `width: auto` preserves the real aspect ratio.
	 */
	$dims = ( $w > 0 && $h > 0 )
		? sprintf( ' width="%d" height="%d"', $w, $h )
		: '';

	return sprintf(
		'<img src="%s" alt="%s"%s />',
		esc_url( $url ),
		esc_attr( $alt ),
		$dims // Built from ints above.
	);
}

/**
 * Social links, as a flat list of `{label, url}`.
 *
 * ⚠️ THE ONE SOURCE. The mobile drawer and the footer both render this — the SAME
 * array, read once from options. They are two placements of one dataset, not two
 * copies: change a link here and both update. Nothing is duplicated in the data
 * layer, and no template holds its own list.
 *
 * Falls back to the retired `ak_telegram` / `ak_instagram` fields so an install
 * that has not been re-saved yet keeps rendering. See ak_migrate_socials().
 *
 * @return array<int, array{label:string,url:string}>
 */
function ak_socials() {
	$rows = ak_chrome( 'ak_socials', array() );
	$out  = array();

	if ( is_array( $rows ) ) {
		foreach ( $rows as $row ) {
			if ( ! empty( $row['url'] ) && ! empty( $row['label'] ) ) {
				$out[] = array(
					/*
					 * ⚠️ THROUGH ak_str(), because this repeater lives on an ACF OPTIONS page —
					 * which is global, one value for every language. That is precisely what
					 * CLAUDE.md's multilingual rule 3 warns against, and the labels shipped as
					 * «Телеграм» / «Інстаграм» on /pt/ because of it. The legacy fallback below
					 * always translated; the repeater that replaced it did not, so the fix went
					 * out with the feature that introduced it.
					 *
					 * ak_str() looks up by SOURCE string, so nothing needs to know which row
					 * this is — an editor adding a fifth social gets it in Polylang → Strings
					 * as soon as the registration below runs.
					 */
					'label' => ak_str( 'ak_social_label', (string) $row['label'] ),
					'url'   => (string) $row['url'],
				);
			}
		}
	}

	if ( $out ) {
		return $out;
	}

	// Legacy shape, kept only as a read fallback.
	foreach ( array(
		'ak_telegram'  => ak_str( 'ak_telegram', 'Телеграм' ),
		'ak_instagram' => ak_str( 'ak_instagram', 'Інстаграм' ),
	) as $field => $label ) {
		$url = ak_chrome( $field );

		if ( $url ) {
			$out[] = array(
				'label' => $label,
				'url'   => $url,
			);
		}
	}

	return $out;
}

/**
 * One-time migration of the two hardcoded social fields into the repeater.
 *
 * Runs once, guarded by an option, so it neither repeats nor overwrites anything a
 * human has since edited: it bails the moment the repeater already has rows.
 */
function ak_migrate_socials() {
	if ( ! ak_has_acf() || get_option( 'ak_socials_migrated' ) ) {
		return;
	}

	$existing = get_field( 'ak_socials', 'option' );

	if ( ! empty( $existing ) ) {
		update_option( 'ak_socials_migrated', 1, false );

		return;
	}

	$rows = array();

	foreach ( array(
		'ak_telegram'  => 'Телеграм',
		'ak_instagram' => 'Інстаграм',
	) as $field => $label ) {
		$url = get_field( $field, 'option' );

		if ( $url ) {
			$rows[] = array(
				'label' => $label,
				'url'   => $url,
			);
		}
	}

	if ( $rows ) {
		update_field( 'ak_socials', $rows, 'option' );
	}

	update_option( 'ak_socials_migrated', 1, false );
}
add_action( 'admin_init', 'ak_migrate_socials' );

/**
 * Should the language switcher render?
 *
 * Defaults to TRUE when the field has never been saved, so an install that predates
 * the toggle keeps its current behaviour rather than silently losing the switcher.
 *
 * @return bool
 */
function ak_show_lang_switcher() {
	if ( ! ak_has_acf() ) {
		return true;
	}

	$value = get_field( 'ak_show_lang', 'option' );

	return ( null === $value || '' === $value ) ? true : (bool) $value;
}

/**
 * The primary CTA, resolved to the current language.
 *
 * @return array{label:string,url:string}|null
 */
function ak_primary_cta() {
	$label = ak_str( 'ak_cta_label', 'Отримати консультацію' );

	// An external URL wins over the page. The site's primary CTA currently points
	// at a Tally form, which is off-site, so a page field alone cannot express it.
	// ak_link_target_attrs() decides target/rel by HOST, so nothing here needs to
	// know or hardcode that it is external.
	$external = ak_chrome( 'ak_cta_url' );

	if ( $external ) {
		return array(
			'label' => $label,
			'url'   => $external,
		);
	}

	$page_id = (int) ak_chrome( 'ak_cta_page', 0 );

	if ( ! $page_id ) {
		return null;
	}

	$url = get_permalink( ak_translate_id( $page_id ) );

	return $url ? array(
		'label' => $label,
		'url'   => $url,
	) : null;
}
