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

/**
 * @codeCoverageIgnore Because it depends on a 3rd party plugin.
 */
class WPTT {
	/**
	 * Build class.
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * @return void
	 */
	private function init() {
		add_filter( 'omgf_frontend_process_parse_links', [ $this, 'validate_link_element' ], 10, 2 );
		add_filter( 'omgf_frontend_process_invalid_request', [ $this, 'validate_request' ], 10, 2 );
	}

	/**
	 * Adds a piece of validation to make sure stylesheets added using the WPTT Webfont Loader are included.
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
		return $is_valid || preg_match( '/wp-content\/fonts\/[a-zA-Z0-9]{32}\.css/', $link );
	}

	/**
	 * Adds a piece of validation to make sure requests to WPTT's stylesheets aren't marked as invalid (and removed).
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
		return $is_invalid && preg_match( '/wp-content\/fonts\/[a-zA-Z0-9]{32}\.css/', $url );
	}
}
