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
					'label'   => __( 'Primary CTA target', 'kalynyuk' ),
					'name'    => 'ak_cta_page',
					'type'    => 'post_object',
					'post_type' => array( 'page' ),
					'return_format' => 'id',
					'allow_null' => 1,
					'ui'      => 1,
					'instructions' => __( 'One CTA, shared by the header and the footer — header-standard requires they mirror each other. The page is translated automatically per language, so pick the Ukrainian one.', 'kalynyuk' ),
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
				array(
					'key'   => 'field_ak_telegram',
					'label' => __( 'Telegram URL', 'kalynyuk' ),
					'name'  => 'ak_telegram',
					'type'  => 'url',
				),
				array(
					'key'   => 'field_ak_instagram',
					'label' => __( 'Instagram URL', 'kalynyuk' ),
					'name'  => 'ak_instagram',
					'type'  => 'url',
				),
				array(
					'key'   => 'field_ak_logo',
					'label' => __( 'Logo (SVG)', 'kalynyuk' ),
					'name'  => 'ak_logo',
					'type'  => 'image',
					'return_format' => 'array',
					'mime_types' => 'svg',
					'instructions' => __( 'Falls back to the Divi Theme Options logo while the migration is in flight.', 'kalynyuk' ),
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
 * The primary CTA, resolved to the current language.
 *
 * @return array{label:string,url:string}|null
 */
function ak_primary_cta() {
	$page_id = (int) ak_chrome( 'ak_cta_page', 0 );
	$label   = ak_str( 'ak_cta_label', 'Отримати консультацію' );

	if ( ! $page_id ) {
		return null;
	}

	$url = get_permalink( ak_translate_id( $page_id ) );

	return $url ? array(
		'label' => $label,
		'url'   => $url,
	) : null;
}
