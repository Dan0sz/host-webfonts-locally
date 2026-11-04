<?php
/* * * * * * * * * * * * * * * * * * * * *
 *
 *  ██████╗ ███╗   ███╗ ██████╗ ███████╗
 * ██╔═══██╗████╗ ████║██╔════╝ ██╔════╝
 * ██║   ██║██╔████╔██║██║  ███╗█████╗
 * ██║   ██║██║╚██╔╝██║██║   ██║██╔══╝
 * ╚██████╔╝██║ ╚═╝ ██║╚██████╔╝██║
 *  ╚═════╝ ╚═╝     ╚═╝ ╚═════╝ ╚═╝
 *
 * @package  : OMGF
 * @author   : Daan van den Bergh
 * @copyright: © 2017 - 2025 Daan van den Bergh
 * @url      : https://daan.dev
 * * * * * * * * * * * * * * * * * * * */

namespace OMGF\Frontend\Compatibility;

/**
 * @codeCoverageIgnore Because it depends on a 3rd party plugin.
 */
class Divi {
	/**
	 * Build class.
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Action/filter hooks.
	 *
	 * @return void
	 */
	private function init() {
		add_filter( 'omgf_frontend_process_fonts_set', [ $this, 'maybe_modify_id' ], 10, 2 );
		add_filter( 'omgf_frontend_process_legacy_ids', [ $this, 'add_legacy_ids' ], 10, 3 );
	}

	/**
	 * Compatibility fix for Divi Builder
	 *
	 * @since v5.1.3 Because Divi Builder uses the same handle for Google Fonts on each page,
	 *               even when these contain Google Fonts, let's append a (kind of) unique
	 *               identifier to the string, to make sure we can make a difference between
	 *               different Google Fonts configurations.
	 * @since v5.2.0 Allow Divi/Elementor compatibility fixes to be disabled, for those who have too
	 *               many different Google Fonts stylesheets configured throughout their pages and
	 *               blame OMGF for the fact that it detects all those different stylesheets. :-/
	 * @since v6.4.2 Use a hash of the URL instead of its length, because different configurations with the same URL
	 *               length got the same identifier, so a cached stylesheet with other fonts was loaded.
	 * @since v6.4.2 Always enabled, because without it a stylesheet with other fonts was loaded after changing fonts.
	 */
	public function maybe_modify_id( $id, $href ) {
		if ( ! str_contains( $id, 'et-builder-googlefonts' ) ) {
			return $id;
		}

		$href_attr = is_array( $href ) && isset( $href['href'] ) ? $href['href'] : '';

		// Ignore line breaks and tabs, like Process::sanitize_url() does before requesting the URL.
		$href_attr = trim( str_replace( [ "\r", "\n", "\t" ], '', $href_attr ) );

		return $id . '-' . substr( md5( $href_attr ), 0, 8 );
	}

	/**
	 * The handles Divi's stylesheets were stored under before v6.4.2: without an identifier (if Divi Compatibility
	 * was disabled) or with the length of the URL.
	 *
	 * @filter omgf_frontend_process_legacy_ids
	 * @since  v6.4.2
	 *
	 * @param array  $legacy_ids
	 * @param string $id
	 * @param array  $href
	 *
	 * @return array
	 */
	public function add_legacy_ids( $legacy_ids, $id, $href ) {
		if ( ! str_contains( $id, 'et-builder-googlefonts' ) ) {
			return $legacy_ids;
		}

		$href_attr = is_array( $href ) && isset( $href['href'] ) ? $href['href'] : '';

		return array_merge( $legacy_ids, [ $id . '-' . strlen( $href_attr ), $id ] );
	}
}
