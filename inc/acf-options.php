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
		'ak_copyright'    => 'Todos os direitos reservados.',
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

	foreach ( $strings as $name => $default ) {
		pll_register_string( $name, $default, 'kalynyuk' );
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
					'label' => (string) $row['label'],
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
