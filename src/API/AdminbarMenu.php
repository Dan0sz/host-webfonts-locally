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

namespace OMGF\API;

use OMGF\Admin\Dashboard;
use OMGF\Admin\Settings;
use OMGF\Helper as OMGF;

class AdminbarMenu {
	private $namespace = 'omgf/v1';

	private $base = 'adminbar-menu';

	private $endpoint = 'status';

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
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	/**
	 * Register the API route.
	 *
	 * @return void
	 *
	 * @codeCoverageIgnore
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->base . '/' . $this->endpoint,
			[
				[
					'methods'             => 'POST',
					'callback'            => [ $this, 'get_admin_bar_status' ],
					'permission_callback' => [ $this, 'get_permission' ],
				],
				'schema' => null,
			]
		);
	}

	/**
	 * Only logged-in administrators should be allowed to use the API.
	 *
	 * @filter omgf_api_adminbar_menu_permission
	 *
	 * @return mixed|null
	 *
	 * @codeCoverageIgnore
	 */
	public function get_permission() {
		$is_allowed = current_user_can( 'manage_options' );

		return apply_filters( 'omgf_api_adminbar_menu_permission', $is_allowed );
	}

	/**
	 * Generate and return the status of the Google Fonts Checker.
	 *
	 * @param \WP_REST_Request $request The request object.
	 *
	 * @filter omgf_ajax_admin_bar_status
	 *
	 * @return array
	 */
	public function get_admin_bar_status( $request ) {
		$params         = $this->clean( $request->get_params() );
		$stored_results = $this->update_google_fonts_checker_results( $params );
		$status         = 'success';

		if ( ! Dashboard::optimize_succeeded() ) {
			$status = 'info';
		}

		if ( ! empty( $stored_results ) ) {
			$status = 'alert';
		}

		if ( empty( $stored_results ) && $this->has_warnings() ) {
			$status = 'notice';
		}

		$unused_fonts_analysis = $this->decode_json_array( $params['unused_fonts_analysis'] ?? [] );
		$preload_analysis      = $this->decode_json_array( $params['preload_analysis'] ?? [] );

		if ( ! empty( $unused_fonts_analysis ) || ! empty( $preload_analysis ) || Dashboard::has_multilang_plugin() ) {
			// Alerts and notices should take precedence.
			if ( $status !== 'alert' && $status !== 'notice' ) {
				$status = 'info';
			}

			$stored_metrics = OMGF::get_option( Settings::OMGF_PERF_CHECK, [] );
			$stored_metrics = is_array( $stored_metrics ) ? $stored_metrics : [];
			$updated        = false;
			$path           = $params['path'] ?? '';

			if ( ! empty( $unused_fonts_analysis['count'] ) && ( empty( $stored_metrics['highest_unused_count'] ) || $unused_fonts_analysis['count'] > $stored_metrics['highest_unused_count'] ) ) {
				$stored_metrics['highest_unused_count']     = $unused_fonts_analysis['count'];
				$stored_metrics['highest_unused_path']      = $path;
				$stored_metrics['highest_unused_impact']    = $unused_fonts_analysis['impact'] ?? __( 'Low', 'host-webfonts-local' );
				$stored_metrics['highest_unused_timestamp'] = time();
				$updated                                    = true;
			}

			if ( ! empty( $preload_analysis['potential_delay_ms'] ) && ( empty( $stored_metrics['highest_delay_ms'] ) || $preload_analysis['potential_delay_ms'] > $stored_metrics['highest_delay_ms'] ) ) {
				$stored_metrics['highest_delay_ms']        = $preload_analysis['potential_delay_ms'];
				$stored_metrics['highest_delay_path']      = $path;
				$stored_metrics['highest_delay_impact']    = $preload_analysis['impact'] ?? __( 'Low', 'host-webfonts-local' );
				$stored_metrics['highest_delay_timestamp'] = time();
				$updated                                   = true;
			}

			if ( $updated ) {
				OMGF::update_option( Settings::OMGF_PERF_CHECK, $stored_metrics );
			}
		}

		return [ 'status' => apply_filters( 'omgf_ajax_admin_bar_status', $status ) ];
	}

	/**
	 * Cleans a given variable by sanitizing its value.
	 *
	 * @param mixed $var The variable to be cleaned. Can be a scalar value or an array of values.
	 *
	 * @return mixed Returns the cleaned variable. If the input is scalar, it will be sanitized accordingly.
	 *               If an array is passed, the method is applied recursively to each element.
	 */
	private function clean( $var ) {
		// If the variable is an array, recursively apply the function to each element of the array.
		if ( is_array( $var ) ) {
			return array_map( [ $this, 'clean' ], $var );
		}

		// If the variable is a scalar value (string, integer, float, boolean).
		if ( is_scalar( $var ) ) {
			// Parse the variable using the wp_parse_url function.
			$parsed = wp_parse_url( $var );
			// If the variable has a scheme (e.g. http:// or https://), sanitize the variable using the esc_url_raw function.
			if ( isset( $parsed['scheme'] ) ) {
				return esc_url_raw( wp_unslash( $var ) );
			}

			// Decode percent encoded characters before sanitization.
			$var = urldecode( $var );

			// If the variable does not have a scheme, sanitize the variable using the sanitize_text_field function.
			return sanitize_text_field( wp_unslash( $var ) );
		}

		// If the variable is not an array or a scalar value, return the variable unchanged.
		return $var; // @codeCoverageIgnore
	}

	/**
	 * Store results of the Google Fonts checker in the database for rendering in the Dashboard.
	 *
	 * @return array
	 */
	private function update_google_fonts_checker_results( $post ) {
		$stored_results = get_option( Settings::OMGF_GOOGLE_FONTS_CHECKER_RESULTS, [] );
		$path           = isset( $post['path'] ) && is_string( $post['path'] ) ? $post['path'] : '';
		$raw_params     = $post['params'] ?? [];

		if ( is_string( $raw_params ) ) {
			$params = json_decode( $raw_params, true );
			$params = is_array( $params ) ? $params : [];
		} elseif ( is_array( $raw_params ) ) {
			$params = $raw_params;
		} else {
			$params = [];
		}

		if ( empty( $path ) || ! is_string( $path ) ) {
			return $stored_results; // @codeCoverageIgnore
		}

		$urls = $post['urls'] ?? [];

		if ( is_string( $urls ) ) {
			$decoded = json_decode( $urls, true );
			$urls    = is_array( $decoded ) ? $decoded : [];
		}

		if ( ! is_array( $urls ) ) {
			$urls = [];
		}

		$urls        = apply_filters( 'omgf_ajax_results', $urls, $params, $path );
		$result_keys = array_keys( $stored_results );
		$solved      = array_diff( $result_keys, $urls );

		/**
		 * We only filter $stored_results if we're running the optimization routine because that's the only point we can actually resolve things.
		 */
		if ( OMGF::is_running_optimize( $params ) && ! empty( $solved ) && ! empty( $stored_results ) ) {
			$stored_results = array_filter(
				$stored_results,
				function ( $url ) use ( $solved ) {
					return ! in_array( $url, $solved );
				},
				ARRAY_FILTER_USE_KEY
			);
		}

		// Store Google Fonts Checker results.
		foreach ( $urls as $url ) {
			// We don't take kindly to malicious actors!
			if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
				continue; // @codeCoverageIgnore
			}

			// Decode special chars (e.g. &#038; to &) for readability.
			$url = htmlspecialchars_decode( $url );

			if ( ! isset( $stored_results[ $url ] ) ) {
				$stored_results[ $url ] = [];
			}

			// Only store the path if it's not already in the array, and we haven't reached the limit of 5.
			if ( ! in_array( $path, $stored_results[ $url ], true ) && count( $stored_results[ $url ] ) < 5 ) {
				$stored_results[ $url ][] = $path;
			}
		}

		/**
		 * We won't show results for more than 5 URLs on the Dashboard to limit the size of the database entry.
		 * We allow adding everything first to update the found paths.
		 */
		if ( count( $stored_results ) >= 5 ) {
			$stored_results = array_slice( $stored_results, 0, 5, true );
		}

		OMGF::update_option( Settings::OMGF_GOOGLE_FONTS_CHECKER_RESULTS, $stored_results, false );

		return $stored_results;
	}

	/**
	 * Check if OMGF has logged any configuration issues that require attention.
	 *
	 * @return bool
	 */
	private function has_warnings() {
		$warnings = Dashboard::get_warnings();

		return ! empty( $warnings );
	}

	/**
	 * Array normalization.
	 *
	 * @param $value
	 *
	 * @return array
	 */
	private function decode_json_array( $value ) {
		if ( is_array( $value ) ) {
			return $value;
		}

		if ( ! is_string( $value ) || $value === '' ) {
			return [];
		}

		$decoded = json_decode( $value, true );

		return is_array( $decoded ) ? $decoded : [];
	}
}
