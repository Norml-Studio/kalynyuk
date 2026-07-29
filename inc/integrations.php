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
