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
* @copyright: © 2023 Daan van den Bergh
* @url      : https://daan.dev
* * * * * * * * * * * * * * * * * * * */

namespace OMGF\Optimize;

use OMGF\Helper as OMGF;
use OMGF\Admin\Notice;
use OMGF\Admin\Settings;

defined( 'ABSPATH' ) || exit;

class Run {
	const DOCS_TEST_URL = 'https://daan.dev/docs/omgf-pro-troubleshooting/test-omgf-pro/';

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
		OMGF::update_option( Settings::OMGF_OPTIMIZE_HAS_RUN, true );

		$front_html = $this->get_front_html( get_home_url() );

		if ( is_wp_error( $front_html ) || wp_remote_retrieve_response_code( $front_html ) != 200 ) {
			$this->frontend_fetch_failed( $front_html );
		} else {
			$this->optimization_succeeded();
		}
	}

	/**
	 * Wrapper for wp_remote_get() with preset params.
	 *
	 * @param mixed $url
	 * @return array|WP_Error
	 */
	private function get_front_html( $url ) {
		$result = wp_remote_get(
			$this->no_cache_optimize_url( $url ),
			[
				'timeout' => 60,
			]
		);

		return $result;
	}

	/**
	 * Generate a request to $uri including the required parameters for OMGF to run in the frontend.
	 *
	 * @param $url
	 *
	 * @since v5.4.4 Added omgf_optimize_run_args filter so other plugins can add query parameters to the Save & Optimize routine.
	 *
	 * @return string
	 */
	private function no_cache_optimize_url( $url ) {
		$args = apply_filters(
			'omgf_optimize_run_args',
			[
				'omgf_optimize' => 1,
				'nocache'       => substr(
					md5( microtime() ),
					wp_rand( 0, 26 ),
					5
				),
			]
		);

		return add_query_arg( $args, $url );
	}

	/**
	 * @return void
	 */
	private function optimization_succeeded() {
		if ( count( get_settings_errors() ) ) {
			global $wp_settings_errors;

			// phpcs:ignore
			$wp_settings_errors = [];
		}

		/**
		 * @since v5.4.4 Check if selected Used Subset(s) are actually available in all detected font families,
		 *               and update the Used Subset(s) option if not.
		 */
		$available_used_subsets = OMGF::available_used_subsets( null, true );

		/**
		 * If $diff is empty, this means that the detected fonts are available in all selected subsets of the
		 * Used Subset(s) option and no further action is required.
		 */
		$diff = array_diff( OMGF::get_option( Settings::OMGF_ADV_SETTING_SUBSETS ), $available_used_subsets );

		if ( ! empty( OMGF::get_option( Settings::OMGF_OPTIMIZE_SETTING_AUTO_SUBSETS ) ) && ! empty( $diff ) ) {
			$break = false;

			if ( empty( $diff ) ) {
				$break = true;
			}

			if ( ! $break && $available_used_subsets && ! empty( $diff ) ) {
				OMGF::debug_array( 'Remaining Subsets (compared to Available Used Subsets)', $diff );

				Notice::set_notice(
					sprintf(
						_n(
							'%s is removed as a Used Subset, as not all detected font families are available in this subset. <a href="#" id="omgf-optimize-again">Run optimization again</a> to process these changes.',
							'%s are removed as Used Subset(s), as not all detected font families are available in these subsets. <a href="#" id="omgf-optimize-again">Run optimization again</a> to process these changes.',
							count( $diff ),
							'host-webfonts-local'
						),
						$this->fluent_implode( $diff )
					),
					'omgf-used-subsets-removed',
					'info'
				);

				OMGF::update_option( Settings::OMGF_ADV_SETTING_SUBSETS, $available_used_subsets );

				return;
			}

			$diff = array_diff( OMGF::get_option( Settings::OMGF_ADV_SETTING_SUBSETS ), [ 'latin' ] );

			if ( ! $break && ! empty( $diff ) ) {
				OMGF::debug_array( 'Remaining Subsets (compared to Latin)', $diff );

				/**
				 * If detected fonts aren't available in any of the subsets that were selected, just set Used Subsets to Latin
				 * to make sure nothing breaks.
				 */
				Notice::set_notice(
					sprintf(
						_n(
							'Used Subset(s) is set to Latin, since %s isn\'t available in all detected font-families. <a href="#" id="omgf-optimize-again">Run optimization again</a> to process these changes.',
							'Used Subset(s) is set to Latin, since %s aren\'t available in all detected font-families. <a href="#" id="omgf-optimize-again">Run optimization again</a> to process these changes.',
							count( $diff ),
							'host-webfonts-local'
						),
						$this->fluent_implode( $diff )
					),
					'omgf-used-subsets-defaults',
					'info'
				);

				OMGF::update_option( Settings::OMGF_ADV_SETTING_SUBSETS, [ 'latin' ] );

				return;
			}
		}

		add_settings_error( 'general', 'omgf_optimization_success', __( 'Optimization completed successfully.', 'host-webfonts-local' ) . ' ' . sprintf( '<a target="_blank" href="%s">', self::DOCS_TEST_URL ) . __( 'How can I verify it\'s working?', 'host-webfonts-local' ) . '</a>', 'success' );

		Notice::set_notice(
			sprintf( __( 'Make sure you flush any caches of 3rd party plugins you\'re using (e.g. Revolution Slider, WP Rocket, Autoptimize, W3 Total Cache, etc.) to allow %s\'s optimizations to take effect. ', 'host-webfonts-local' ), apply_filters( 'omgf_settings_page_title', 'OMGF' ) ),
			'omgf-cache-notice',
			'warning'
		);
	}

	/**
	 * Generate a fluent sentence from array, e.g. "1, 2, 3 and 4" if element is count is > 1.
	 *
	 * @since v5.4.4
	 *
	 * @param array $array
	 *
	 * @return string
	 */
	private function fluent_implode( $array ) {
		if ( count( $array ) == 1 ) {
			return ucfirst( reset( $array ) );
		}

		$last  = array_pop( $array );
		$first = implode( ', ', array_map( 'ucfirst', $array ) );

		return $first . ' and ' . ucfirst( $last );
	}

	/**
	 * @param $response WP_Error|array
	 */
	private function frontend_fetch_failed( $response ) {
		if ( $response instanceof \WP_REST_Response && $response->is_error() ) {
			// Convert to WP_Error if WP_REST_Response
			$response = $response->as_error();
		}

		add_settings_error( 'general', 'omgf_frontend_fetch_failed', sprintf( __( '%s encountered an error while fetching this site\'s frontend HTML', 'host-webfonts-local' ), apply_filters( 'omgf_settings_page_title', 'OMGF' ) ) . ': ' . $this->get_error_code( $response ) . ' - ' . $this->get_error_message( $response ), 'error' );

		if ( $this->get_error_code( $response ) == '403' ) {
			Notice::set_notice( sprintf( __( 'It looks like OMGF isn\'t allowed to fetch your frontend. Try <a class="omgf-optimize-forbidden" href="%s" target="_blank">running the optimization manually</a> (you might have to allow pop-ups) and return here after the page has finished loading.', 'host-webfonts-local' ), $this->no_cache_optimize_url( get_home_url() ) ), 'omgf-forbidden', 'info' );
		}
	}

	/**
	 * @param WP_REST_Response|WP_Error|array $response
	 *
	 * @return int|string
	 */
	private function get_error_code( $response ) {
		if ( is_wp_error( $response ) ) {
			return $response->get_error_code();
		}

		return wp_remote_retrieve_response_code( $response );
	}

	/**
	 * @param WP_REST_Response|WP_Error|array $response
	 *
	 * @return int|string
	 */
	private function get_error_message( $response ) {
		if ( is_wp_error( $response ) ) {
			return $response->get_error_message();
		}

		return wp_remote_retrieve_response_message( $response );
	}
}
