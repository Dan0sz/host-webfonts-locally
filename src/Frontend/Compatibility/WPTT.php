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
		add_filter( 'omgf_frontend_process_parse_links', [ $this, 'validate_link_element' ], 10, 3 );
		add_filter( 'omgf_frontend_process_invalid_request', [ $this, 'validate_request' ], 10, 2 );
	}

	/**
	 * Adds a piece of validation to make sure stylesheets added using the WPTT Webfont Loader are included.
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
			if ( $this->is_webfont_loader_stylesheet( $url ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Does $url point to a stylesheet stored by the WPTT Webfont Loader?
	 *
	 * @since v6.3.11
	 *
	 * @param string $url
	 *
	 * @return bool
	 */
	private function is_webfont_loader_stylesheet( $url ) {
		$path = wp_parse_url( html_entity_decode( (string) $url ), PHP_URL_PATH );

		return is_string( $path ) && (bool) preg_match( '/wp-content\/fonts\/[a-zA-Z0-9]{32}\.css$/', $path );
	}

	/**
	 * Adds a piece of validation to make sure requests to WPTT's stylesheets aren't marked as invalid (and removed).
	 *
	 * @filter omgf_frontend_process_invalid_request
	 * @see    \OMGF\Frontend\Process::build_search_replace()
	 *
	 * @since  v6.3.11 The condition was inverted: a WPTT stylesheet (which has no family parameter) was marked
	 *                 as invalid and removed from the page, while any other request without a family parameter
	 *                 was no longer marked as invalid, and requested instead of removed.
	 *
	 * @param $is_invalid
	 * @param $url
	 *
	 * @return bool
	 */
	public function validate_request( $is_invalid, $url ) {
		return $is_invalid && ! $this->is_webfont_loader_stylesheet( $url );
	}
}
