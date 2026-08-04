<?php
/**
 * Third-party plugin integrations.
 *
 * @package kalynyuk
 */

defined( 'ABSPATH' ) || exit;

/**
 * Give Cyr-To-Lat a Ukrainian transliteration table.
 *
 * ⚠️ THE PLUGIN HAS NO UKRAINIAN TABLE. `CyrToLat\ConversionTables::get()` only
 * branches on `bel`, `bg_BG`, `he_IL`, `ka_GE`, `mk_MK`, `sr_RS` and `zh_*`;
 * everything else falls through to the RUSSIAN default. This site's WordPress
 * locale is `en_US` (Polylang handles content languages separately), so the
 * Russian table was in force on a Ukrainian-default site.
 *
 * Measured on production before this fix:
 *
 *   Процес отримання іпотеки в Португалії → proczes-otrimannya-ipoteki-v-portugali%d1%97
 *   Ще їжак ґудзик є                      → shhe-%d1%97zhak-%d2%91udzik-%d1%94
 *   Ціни та відгуки                       → czini-ta-vidguki
 *   Київ                                  → ki%d1%97v
 *
 * Two distinct defects: `і ї є ґ` were absent from the table entirely, so they
 * survived into the slug and got percent-encoded (`Київ` → `ki%d1%97v`); and the
 * shared letters used Russian conventions (`ц`→`cz`, `и`→`i`, `щ`→`shh`).
 *
 * The mapping below follows the Ukrainian national standard (Cabinet of Ministers
 * resolution No. 55, 2010 — the one used for passports and place names).
 *
 * ⚠️ ONE KNOWN COMPROMISE. That standard is context-sensitive: `є ї й ю я` take
 * `ye yi y yu ya` word-initially and `ie i i iu ia` elsewhere, and `зг` → `zgh`.
 * Cyr-To-Lat's table is a flat character map and cannot express position, so the
 * "elsewhere" forms are used throughout — the common choice for Ukrainian WordPress
 * sites, and consistent, which matters more for URLs than strict compliance.
 *
 * Russian letters are kept mapped as well: the site has a `ru` language configured
 * in Polylang, and dropping them would reintroduce the percent-encoding bug the
 * moment Russian content is added.
 *
 * ⚠️ FORWARD-LOOKING ONLY. This changes how NEW slugs are generated. Existing posts
 * keep theirs — deliberately. Rewriting the slug of a published, indexed page breaks
 * its URL, and the plugin's bulk "convert existing slugs" tool would do exactly that
 * with no redirects. Checked production: no public URL was affected (the only two
 * percent-encoded slugs were an orphaned Elementor template and one guide created
 * during the cutover, which was corrected by hand).
 *
 * @param array $table Character map.
 * @return array
 */
function ak_cyr2lat_ukrainian_table( $table ) {
	$uk = array(
		'А' => 'A',  'а' => 'a',
		'Б' => 'B',  'б' => 'b',
		'В' => 'V',  'в' => 'v',
		'Г' => 'H',  'г' => 'h',   // Ukrainian г is H, not G — this is the one most often wrong.
		'Ґ' => 'G',  'ґ' => 'g',
		'Д' => 'D',  'д' => 'd',
		'Е' => 'E',  'е' => 'e',
		'Є' => 'Ie', 'є' => 'ie',
		'Ж' => 'Zh', 'ж' => 'zh',
		'З' => 'Z',  'з' => 'z',
		'И' => 'Y',  'и' => 'y',   // Ukrainian и is Y; the Russian table gave I.
		'І' => 'I',  'і' => 'i',
		'Ї' => 'I',  'ї' => 'i',
		'Й' => 'I',  'й' => 'i',
		'К' => 'K',  'к' => 'k',
		'Л' => 'L',  'л' => 'l',
		'М' => 'M',  'м' => 'm',
		'Н' => 'N',  'н' => 'n',
		'О' => 'O',  'о' => 'o',
		'П' => 'P',  'п' => 'p',
		'Р' => 'R',  'р' => 'r',
		'С' => 'S',  'с' => 's',
		'Т' => 'T',  'т' => 't',
		'У' => 'U',  'у' => 'u',
		'Ф' => 'F',  'ф' => 'f',
		'Х' => 'Kh', 'х' => 'kh',
		'Ц' => 'Ts', 'ц' => 'ts',  // was cz
		'Ч' => 'Ch', 'ч' => 'ch',
		'Ш' => 'Sh', 'ш' => 'sh',
		'Щ' => 'Shch', 'щ' => 'shch', // was shh
		'Ь' => '',   'ь' => '',
		'Ю' => 'Iu', 'ю' => 'iu',
		'Я' => 'Ia', 'я' => 'ia',
		'’' => '',   '\'' => '',   // apostrophe is dropped, per the standard

		// Russian-only letters, kept so `ru` content does not regress.
		'Ё' => 'Yo', 'ё' => 'yo',
		'Ъ' => '',   'ъ' => '',
		'Ы' => 'Y',  'ы' => 'y',
		'Э' => 'E',  'э' => 'e',
	);

	// Ours wins on every shared key; anything the plugin knows that we do not is kept.
	return array_merge( (array) $table, $uk );
}
add_filter( 'ctl_table', 'ak_cyr2lat_ukrainian_table' );

/**
 * Force every Gravity Form to render in AJAX mode.
 *
 * Unchanged behaviour, renamed off the global namespace. The old name,
 * `filter_gravity_forms_force_ajax`, is exactly the kind of name a Gravity Forms
 * snippet plugin would also define — a fatal redeclare waiting to happen
 * (.claude/docs/05-issues.md).
 *
 * Applies site-wide with no escape hatch, which is intentional today: all three
 * forms (footer contact #1, the empty #2, mortgage enquiry #3) are AJAX. If a form
 * ever needs a full page reload, gate this on $form_args['form_id'] rather than
 * removing the filter.
 *
 * @param array $form_args Gravity Forms render arguments.
 * @return array
 */
function ak_gform_force_ajax( $form_args ) {
	$form_args['ajax'] = true;

	return $form_args;
}
add_filter( 'gform_form_args', 'ak_gform_force_ajax' );

/**
 * Translate Gravity Forms field content through Polylang.
 *
 * ⚠️ GRAVITY FORMS HAS NO POLYLANG INTEGRATION AT ALL, and this is the answer to why
 * the footer form stayed Ukrainian on /pt/ while everything around it translated.
 *
 * Two different layers were being confused:
 *
 *   1. GF's OWN interface strings — "0 of 600 max characters", validation messages,
 *      "This field is required" — are ordinary WordPress i18n. Polylang switches the
 *      locale per language, GF loads its matching .mo, and they translate with no work
 *      from us. That is why the character counter already read "0 de 600 máximo de
 *      caracteres" while the labels beside it did not.
 *   2. FORM CONTENT — labels, placeholders, choice text, the submit button — is entered
 *      by an editor and lives in GF's OWN database tables, not in options or posts.
 *      Polylang's string scanner never sees it, and there is no official Polylang addon
 *      (Gravity Forms Multilingual is a WPML product).
 *
 * The usual workaround is one duplicated form per language, picked at render time. It was
 * rejected: four forms means four entry streams to read, four notification configs to keep
 * in sync, and a field added to one and forgotten in the others — for a four-field contact
 * form. The cost lands on whoever maintains it, forever.
 *
 * This filters the form OBJECT instead, so there stays exactly ONE form, one entry stream,
 * one set of notifications, and the labels become translatable in Polylang → Translations
 * → Strings alongside every other UI string on the site (CLAUDE.md multilingual rule 3).
 *
 * ⚠️ ALL THREE HOOKS ARE REQUIRED. `gform_pre_render` alone translates what the visitor
 * sees but not what the validator and the submission handler work with, so a failed
 * validation would re-render the form with the ORIGINAL labels and the notification email
 * would carry them too. The trio keeps display, validation and submission consistent.
 *
 * Deliberately NOT hooked on `gform_admin_pre_render`: the editor must keep seeing the
 * source strings, or they would end up editing a translation and creating a new, untracked
 * source string every time they saved.
 *
 * @param array $form Gravity Forms form object.
 * @return array
 */
function ak_gform_translate( $form ) {
	if ( ! function_exists( 'pll__' ) || empty( $form['fields'] ) ) {
		return $form;
	}

	foreach ( $form['fields'] as $field ) {
		foreach ( array( 'label', 'placeholder', 'description', 'errorMessage' ) as $prop ) {
			if ( ! empty( $field->$prop ) ) {
				$field->$prop = pll__( $field->$prop );
			}
		}

		// Choice text carries the consent sentence, HTML link and all — pll__() is a plain
		// string lookup, so the markup survives untouched.
		if ( ! empty( $field->choices ) && is_array( $field->choices ) ) {
			foreach ( $field->choices as $i => $choice ) {
				if ( ! empty( $choice['text'] ) ) {
					$field->choices[ $i ]['text'] = pll__( $choice['text'] );
				}
			}
		}
	}

	if ( ! empty( $form['button']['text'] ) ) {
		$form['button']['text'] = pll__( $form['button']['text'] );
	}

	/*
	 * The thank-you message. It reaches the visitor through `gform_pre_submission_filter`,
	 * because GFFormDisplay::process_form() picks the confirmation out of the form object it
	 * gets back from that filter (form_display.php — handle_submission() at ~line 210).
	 *
	 * ⚠️ NOT the `gform_confirmation` filter, which is the more obvious hook: by the time it
	 * fires the message is already merged with the entry and wrapped in GF's markup, so the
	 * string handed to pll__() would be per-submission HTML that matches no registered source.
	 * Translating the stored message keeps the lookup key a stable, editor-authored sentence.
	 *
	 * NOTIFICATIONS are deliberately left alone. They go to Anna, not to the visitor, so they
	 * belong in her language regardless of which language the form was filled in.
	 */
	if ( ! empty( $form['confirmations'] ) && is_array( $form['confirmations'] ) ) {
		foreach ( $form['confirmations'] as $id => $confirmation ) {
			if ( ! empty( $confirmation['message'] ) ) {
				$form['confirmations'][ $id ]['message'] = pll__( $confirmation['message'] );
			}
		}
	}

	return $form;
}
add_filter( 'gform_pre_render', 'ak_gform_translate' );
add_filter( 'gform_pre_validation', 'ak_gform_translate' );
add_filter( 'gform_pre_submission_filter', 'ak_gform_translate' );

/**
 * Register every Gravity Forms string with Polylang so it appears in the Strings screen.
 *
 * ⚠️ ADMIN ONLY, and that is not an oversight. pll__() translates by looking the SOURCE
 * string up in the language's compiled .mo — it does not need the string to be registered.
 * Registration exists purely so the string shows up in Translations → Strings for someone
 * to fill in. Doing that on the front end would mean a GFAPI::get_forms() database read on
 * every page view to populate a screen no visitor ever opens.
 *
 * Registered for ALL forms, not just the footer one, so the calculator's form is ready when
 * its own migration comes. Untranslated strings simply return their source.
 *
 * @return void
 */
function ak_gform_register_strings() {
	if ( ! is_admin() || ! class_exists( 'GFAPI' ) || ! function_exists( 'pll_register_string' ) ) {
		return;
	}

	foreach ( GFAPI::get_forms() as $form ) {
		$group = 'kalynyuk';

		foreach ( $form['fields'] as $field ) {
			foreach ( array( 'label', 'placeholder', 'description', 'errorMessage' ) as $prop ) {
				if ( ! empty( $field->$prop ) ) {
					pll_register_string( 'gf_' . $form['id'] . '_' . $field->id . '_' . $prop, $field->$prop, $group, 'description' === $prop );
				}
			}

			if ( ! empty( $field->choices ) && is_array( $field->choices ) ) {
				foreach ( $field->choices as $i => $choice ) {
					if ( ! empty( $choice['text'] ) ) {
						pll_register_string( 'gf_' . $form['id'] . '_' . $field->id . '_choice' . $i, $choice['text'], $group, true );
					}
				}
			}
		}

		if ( ! empty( $form['button']['text'] ) ) {
			pll_register_string( 'gf_' . $form['id'] . '_button', $form['button']['text'], $group );
		}

		if ( ! empty( $form['confirmations'] ) && is_array( $form['confirmations'] ) ) {
			$i = 0;
			foreach ( $form['confirmations'] as $confirmation ) {
				if ( ! empty( $confirmation['message'] ) ) {
					pll_register_string( 'gf_' . $form['id'] . '_confirmation' . $i, $confirmation['message'], $group, true );
				}
				$i++;
			}
		}
	}
}
add_action( 'init', 'ak_gform_register_strings', 20 );

/**
 * Keep the privacy-policy link alive in languages the page has not been translated into.
 *
 * ⚠️ POLYLANG RETURNS 0, NOT THE ORIGINAL. It filters `option_wp_page_for_privacy_policy`
 * at priority 20 and hands back the translated page — or 0 when there is none. Every caller
 * treats 0 as "no privacy policy configured", so on /pt/, /en/ and /ru/ the footer link
 * silently vanished, and so did WP core's own `get_privacy_policy_url()` (login screen,
 * `the_privacy_policy_link()`, the personal-data export mails).
 *
 * A missing translation should degrade to the Ukrainian page, not to nothing: the link still
 * works, it is merely untranslated. For a credit intermediary the privacy policy has to be
 * reachable from every page in every language, so "no link at all" is the one outcome that
 * is not acceptable.
 *
 * Done at the OPTION level rather than in footer.php on purpose — the footer was where it was
 * noticed, but core and any plugin reading the option had the same hole.
 *
 * Implemented as capture-at-19 / restore-at-21 so Polylang's filter is left completely alone.
 * Reading the raw value inside the filter instead would mean either recursing through
 * get_option() or tearing out another plugin's callback.
 *
 * @param int|string $value Option value.
 * @return int|string
 */
function ak_privacy_page_capture( $value ) {
	ak_privacy_page_stash( (int) $value );

	return $value;
}
add_filter( 'option_wp_page_for_privacy_policy', 'ak_privacy_page_capture', 19 );

/**
 * Restore the captured id when Polylang blanked it.
 *
 * @param int|string $value Option value, post-Polylang.
 * @return int|string
 */
function ak_privacy_page_restore( $value ) {
	return $value ? $value : ak_privacy_page_stash();
}
add_filter( 'option_wp_page_for_privacy_policy', 'ak_privacy_page_restore', 21 );

/**
 * Holder for the untranslated privacy-policy page id.
 *
 * A static rather than a global: the stored option cannot change mid-request, and the
 * capture/restore pair is the only thing that has any business touching it.
 *
 * @param int|null $value Id to stash, or null to read.
 * @return int
 */
function ak_privacy_page_stash( $value = null ) {
	static $raw = 0;

	if ( null !== $value ) {
		$raw = $value;
	}

	return $raw;
}
