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

namespace OMGF\Optimize;

use OMGF\Helper as OMGF;
use OMGF\Admin\Notice;
use WP_Error;

class Run {
	/**
	 * Build class.
	 *
	 * @return void
	 */
	public function __construct() {
		$this->run();
	}

	/**
	 * Does a quick fetch to the site_url to trigger all the action.
	 *
	 * @return void
	 */
	private function run() {
		$front_html = $this->get_front_html( get_home_url() );

		if ( is_wp_error( $front_html ) || wp_remote_retrieve_response_code( $front_html ) != 200 ) {
			$this->frontend_fetch_failed( $front_html ); // @codeCoverageIgnore
		} else {
			$this->optimization_succeeded();
		}
	}

	/**
	 * Wrapper for wp_remote_get() with preset params.
	 *
	 * @param mixed $url
	 *
	 * @return array|WP_Error
	 */
	private function get_front_html( $url ) {
		return wp_remote_get(
			OMGF::no_cache_optimize_url( $url ),
			[
				'timeout' => 60,
			]
		);
	}

	/**
	 * @param $response WP_Error|array
	 *
	 * @codeCoverageIgnore
	 */
	private function frontend_fetch_failed( $response ) {
		if ( $response instanceof \WP_REST_Response && $response->is_error() ) {
			// Convert to WP_Error if WP_REST_Response
			$response = $response->as_error();
		}

		add_settings_error(
			'general',
			'omgf_frontend_fetch_failed',
			sprintf(
				__( '%s encountered an error while fetching this site\'s frontend HTML', 'host-webfonts-local' ),
				apply_filters( 'omgf_settings_page_title', 'OMGF' )
			) . ': ' . $this->get_error_code( $response ) . ' - ' . $this->get_error_message( $response ),
			'error'
		);

		if ( $this->get_error_code( $response ) == '403' ) {
			Notice::set_notice(
				sprintf(
					__(
						'It looks like OMGF isn\'t allowed to fetch your frontend. Try <a class="omgf-optimize-forbidden" href="%s" target="_blank">running the optimization manually</a> (you might have to allow pop-ups) and return here after the page has finished loading.',
						'host-webfonts-local'
					),
					OMGF::no_cache_optimize_url( get_home_url() )
				),
				'omgf-forbidden',
				'info'
			);
		}
	}

	/**
	 * @param WP_Error|array $response
	 *
	 * @return int|string
	 *
	 * @codeCoverageIgnore
	 */
	private function get_error_code( $response ) {
		if ( is_wp_error( $response ) ) {
			/** @var WP_Error $response */
			return $response->get_error_code();
		}

		/** @var $response array */
		return wp_remote_retrieve_response_code( $response );
	}

	/**
	 * @param WP_Error|array $response
	 *
	 * @return int|string
	 *
	 * @codeCoverageIgnore
	 */
	private function get_error_message( $response ) {
		if ( is_wp_error( $response ) ) {
			/** @var WP_Error $response */
			return $response->get_error_message();
		}

		/** @var $response array */
		return wp_remote_retrieve_response_message( $response );
	}

	/**
	 * @return void
	 */
	private function optimization_succeeded() {
		if ( count( get_settings_errors() ) ) {
			global $wp_settings_errors;

			$wp_settings_errors = [];
		}

		add_settings_error(
			'general',
			'omgf_optimization_success',
			__( 'Optimization completed successfully.', 'host-webfonts-local' ) . '</a>',
			'success'
		);
	}
}
