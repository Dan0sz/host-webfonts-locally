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
* @copyright: © 2024 Daan van den Bergh
* @url      : https://daan.dev
* * * * * * * * * * * * * * * * * * * */

namespace OMGF;

use OMGF\Admin\Dashboard;
use OMGF\Admin\Settings;

class Ajax {
	/**
	 * Build class.
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Actions hooks.
	 *
	 * @return void
	 */
	private function init() {
		add_action( 'wp_ajax_omgf_admin_bar_status', [ $this, 'get_admin_bar_status' ] );
		add_action( 'wp_ajax_nopriv_omgf_admin_bar_status', [ $this, 'get_admin_bar_status' ] );
	}

	/**
	 * Determines the status of our admin bar menu based on stored results and warnings.
	 *
	 * @return void Sends a JSON response with one of the statuses: 'alert', 'warning', or 'success'.
	 */
	public function get_admin_bar_status() {
		check_ajax_referer( 'omgf_frontend_nonce', '_wpnonce' );

		$stored_results = $this->store_results();
		$status         = 'success';

		if ( ! empty( $stored_results ) ) {
			$status = 'alert';
		}

		if ( empty( $stored_results ) && $this->has_warnings() ) {
			$status = 'notice';
		}

		$status = apply_filters( 'omgf_ajax_admin_bar_status', $status );

		wp_send_json_success( $status );
	}

	/**
	 * Store results of Google Fonts checker in database, for rendering in the Dashboard.
	 *
	 * @return array
	 */
	private function store_results() {
		$urls           = $_POST[ 'urls' ] ?? [];
		$path           = $_POST[ 'path' ];
		$stored_results = get_option( Settings::OMGF_GOOGLE_FONTS_CHECKER_RESULTS, [] );

		// This issue has been solved, so remove it from the results.
		if ( empty( $urls ) && ! empty( $stored_results[ $path ] ) ) {
			unset( $stored_results[ $path ] );
		}

		// We won't show results for more than 5 URLs on the Dashboard, to limit the size of the database entry.
		if ( count( $stored_results ) > 5 ) {
			return $stored_results;
		}

		// Store Google Fonts Checker results.
		foreach ( $urls as $url ) {
			if ( ! isset( $stored_results[ $path ] ) ) {
				$stored_results[ $path ] = [];
			}

			if ( ! in_array( $url, $stored_results[ $path ], true ) ) {
				$stored_results[ $path ][] = $url;
			}
		}

		update_option( Settings::OMGF_GOOGLE_FONTS_CHECKER_RESULTS, $stored_results, false );

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
