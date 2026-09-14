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
* @copyright: © 2026 Daan van den Bergh
* @url      : https://daan.dev
* * * * * * * * * * * * * * * * * * * */

namespace OMGF\Frontend\Compatibility;

use OMGF\Admin\Settings;
use OMGF\Helper as OMGF;

/**
 * @codeCoverageIgnore Because it depends on a 3rd party plugin.
 */
class Elementor {
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
	public function init() {
		add_filter( 'omgf_frontend_process_fonts_set', [ $this, 'maybe_modify_id' ], 10, 2 );
		add_filter( 'omgf_frontend_process_parse_links', [ $this, 'validate_link_element' ], 10, 3 );
		add_filter( 'omgf_frontend_process_invalid_request', [ $this, 'validate_request' ], 10, 2 );
	}

	/**
	 * Compatibility fix for Elementor
	 *
	 * @since v5.1.4 Because Elementor uses the same (annoyingly generic) handle for Google Fonts
	 *               stylesheets on each page, even when these contain different Google Fonts than
	 *               other pages, let's append a (kind of) unique identifier to the string, to make
	 *               sure we can make a difference between different Google Fonts configurations.
	 *
	 * TODO: check if this is still needed in Elementor 3.30.
	 */
	public function maybe_modify_id( $id, $href ) {
		if ( OMGF::get_option( Settings::OMGF_ADV_SETTING_COMPATIBILITY ) && $id === 'google-fonts-1' ) {
			$href_attr = is_array( $href ) && isset( $href['href'] ) ? $href['href'] : '';

			return str_replace( '-1', '-' . strlen( $href_attr ), $id ); // @codeCoverageIgnore
		}

		return $id;
	}

	/**
	 * Adds a piece of validation to make sure Elementor's stylesheets are included.
	 *
	 * @filter omgf_frontend_process_parse_links
	 * @see    \OMGF\Frontend\Process::process()
	 *
	 * @since  v6.3.11 Validate the element's attribute values, instead of matching its markup, so only
	 *                 elements which actually point to the stylesheet are included.
	 *
	 * @param $is_valid
	 * @param $link
	 * @param $urls     The element's attribute values, sanitized by @see \OMGF\Frontend\Process::get_element_urls()
	 *
	 * @return bool
	 */
	public function validate_link_element( $is_valid, $link, $urls = [] ) {
		if ( $is_valid ) {
			return true;
		}

		foreach ( (array) $urls as $url ) {
			if ( $this->is_google_fonts_stylesheet( $url ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Does $url point to a Google Fonts stylesheet Elementor stored locally?
	 *
	 * @since v6.3.11
	 *
	 * @param string $url
	 *
	 * @return bool
	 */
	private function is_google_fonts_stylesheet( $url ) {
		$path = wp_parse_url( html_entity_decode( (string) $url ), PHP_URL_PATH );

		return is_string( $path ) && str_contains( $path, '/uploads/elementor/google-fonts' );
	}

	/**
	 * Adds a piece of validation to make sure requests to Elementor's stylesheets aren't marked as invalid (and removed).
	 *
	 * @filter omgf_frontend_process_invalid_request
	 * @see    \OMGF\Frontend\Process::build_search_replace()
	 *
	 * @param $is_invalid
	 * @param $url
	 *
	 * @return bool
	 */
	public function validate_request( $is_invalid, $url ) {
		return $is_invalid && ! $this->is_google_fonts_stylesheet( $url );
	}
}
