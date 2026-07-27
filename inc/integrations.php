<?php
/**
 * Third-party plugin integrations.
 *
 * @package kalynyuk
 */

defined( 'ABSPATH' ) || exit;

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
