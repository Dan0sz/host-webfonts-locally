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
		add_filter( 'omgf_frontend_process_parse_links', [ $this, 'validate_link_element' ], 10, 2 );
		add_filter( 'omgf_frontend_process_invalid_request', [ $this, 'validate_request' ], 10, 2 );
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
