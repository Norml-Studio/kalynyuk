<?php
/**
 * Helpers.
 *
 * @package kalynyuk
 */

defined( 'ABSPATH' ) || exit;

/**
 * Is this URL external to the site?
 *
 * Required by dev-wp-developer as a hard rule: internal links open in the same
 * window, external links open in a new tab, and the decision is made by HOST —
 * never hardcoded per-link, never a blanket target="_blank" on a reusable
 * component.
 *
 * mailto: and tel: count as INTERNAL: the OS hands them to another application,
 * so a new browser tab would be pointless.
 *
 * @param string $url URL to test.
 * @return bool
 */
if ( ! function_exists( 'ak_is_external_url' ) ) {
	function ak_is_external_url( $url ) {
		$url = trim( (string) $url );

		if ( '' === $url ) {
			return false;
		}

		// Protocol handlers the browser does not navigate to.
		if ( preg_match( '#^(mailto|tel|sms|callto):#i', $url ) ) {
			return false;
		}

		// Relative, root-relative, anchor or query-only — always internal.
		if ( preg_match( '#^(/|\#|\?|\.)#', $url ) ) {
			return false;
		}

		$host = wp_parse_url( $url, PHP_URL_HOST );

		// Protocol-relative or malformed with no host: treat as internal.
		if ( empty( $host ) ) {
			return false;
		}

		$home_host = wp_parse_url( home_url(), PHP_URL_HOST );

		// Compare case-insensitively and ignore a leading www. on either side, so
		// kalynyuk.com and www.kalynyuk.com are the same site.
		$normalise = static function ( $h ) {
			return preg_replace( '#^www\.#i', '', strtolower( (string) $h ) );
		};

		return $normalise( $host ) !== $normalise( $home_host );
	}
}

/**
 * Attribute string for a link, gated on ak_is_external_url().
 *
 * Use this instead of writing target/rel by hand, so the rule is applied in
 * exactly one place.
 *
 * @param string $url URL the link points at.
 * @return string Ready-to-echo attributes, already escaped. Empty when internal.
 */
if ( ! function_exists( 'ak_link_target_attrs' ) ) {
	function ak_link_target_attrs( $url ) {
		return ak_is_external_url( $url ) ? ' target="_blank" rel="noopener noreferrer"' : '';
	}
}

/**
 * Language slugs configured in Polylang, in display order.
 *
 * Every piece of language-aware code MUST read from here rather than hardcoding
 * a list. That is the whole "make room for a future language" requirement: adding
 * a third language becomes an admin action with zero code changes.
 *
 * Falls back to a single-element array so the theme still renders if Polylang is
 * deactivated.
 *
 * @return string[] e.g. array( 'uk', 'pt' )
 */
if ( ! function_exists( 'ak_languages' ) ) {
	function ak_languages() {
		if ( function_exists( 'pll_languages_list' ) ) {
			$langs = pll_languages_list( array( 'fields' => 'slug' ) );

			if ( ! empty( $langs ) ) {
				return array_values( $langs );
			}
		}

		return array( ak_current_language() );
	}
}

/**
 * Current language slug, with a safe fallback.
 *
 * @return string
 */
if ( ! function_exists( 'ak_current_language' ) ) {
	function ak_current_language() {
		if ( function_exists( 'pll_current_language' ) ) {
			$slug = pll_current_language( 'slug' );

			if ( ! empty( $slug ) ) {
				return $slug;
			}
		}

		if ( function_exists( 'pll_default_language' ) ) {
			$slug = pll_default_language( 'slug' );

			if ( ! empty( $slug ) ) {
				return $slug;
			}
		}

		// Matches the Polylang default recorded in .claude/docs/04-content-structure.md.
		return 'uk';
	}
}
