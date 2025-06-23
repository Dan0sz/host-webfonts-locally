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
* @copyright: © 2025 Daan van den Bergh
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
		add_filter( 'omgf_frontend_process_parse_links', [ $this, 'validate_link_element' ], 10, 2 );
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
			$href_attr = is_array( $href ) && isset( $href[ 'href' ] ) ? $href[ 'href' ] : '';

			return str_replace( '-1', '-' . strlen( $href_attr ), $id ); // @codeCoverageIgnore
		}

		return $id;
	}

	/**
	 * Adds a piece of validation to make sure Elementor's stylesheets are included.
	 *
	 * @filter omgf_frontend_process_parse_links
	 * @see    \OMGF\Frontend\Process::parse()
	 *
	 * @param $is_valid
	 * @param $link
	 *
	 * @return bool
	 */
	public function validate_link_element( $is_valid, $link ) {
		return $is_valid || str_contains( $link, '/uploads/elementor/google-fonts' );
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
		return $is_invalid && ! str_contains( $url, '/uploads/elementor/google-fonts' );
	}
}
