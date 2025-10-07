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
	 * @return void
	 */
	public function get_admin_bar_status( $request ) {
		$params         = $this->clean( $request->get_params() );
		$stored_results = $this->update_results( $params );
		$status         = 'success';

		if ( ! empty( $stored_results ) ) {
			$status = 'alert';
		}

		if ( empty( $stored_results ) && $this->has_warnings() ) {
			$status = 'notice';
		}

		return apply_filters( 'omgf_ajax_admin_bar_status', $status );
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
			if ( isset( $parsed[ 'scheme' ] ) ) {
				return esc_url_raw( wp_unslash( $var ), [ $parsed[ 'scheme' ] ] );
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
	private function update_results( $post ) {
		$path           = $post[ 'path' ];
		$params         = isset( $post[ 'params' ] ) ? json_decode( $post[ 'params' ], true ) : [];
		$stored_results = get_option( Settings::OMGF_GOOGLE_FONTS_CHECKER_RESULTS, [] );

		if ( empty( $path ) || ! is_string( $path ) ) {
			return $stored_results; // @codeCoverageIgnore
		}

		$urls = $post[ 'urls' ] ?? [];

		// Decode if $urls is valid JSON.
		if ( is_string( $urls ) && is_array( json_decode( $urls ) ) && json_last_error() === JSON_ERROR_NONE ) {
			$urls = json_decode( $urls );
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
		$task_manager = new Dashboard();
		$warnings     = $task_manager->get_warnings();

		return ! empty( $warnings );
	}
}
