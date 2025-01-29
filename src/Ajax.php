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
		add_action( 'wp_ajax_omgf_store_checker_results', [ $this, 'store_checker_results' ] );
		add_action( 'wp_ajax_nopriv_omgf_store_checker_results', [ $this, 'store_checker_results' ] );
	}

	/**
	 * Store results of Google Fonts checker in database, for rendering in the Task Manager.
	 *
	 * @return void
	 */
	public function store_checker_results() {
		check_ajax_referer( 'omgf_store_checker_results', '_wpnonce' );

		$urls           = $_POST[ 'urls' ] ?? [];
		$path           = $_POST[ 'path' ];
		$stored_results = get_option( Settings::OMGF_GOOGLE_FONTS_CHECKER_RESULTS, [] );

		foreach ( $urls as $url ) {
			if ( ! isset( $stored_results[ $path ] ) ) {
				$stored_results[ $path ] = [];
			}

			if ( ! in_array( $url, $stored_results[ $path ], true ) ) {
				$stored_results[ $path ][] = $url;
			}
		}

		update_option( Settings::OMGF_GOOGLE_FONTS_CHECKER_RESULTS, $stored_results, false );

		wp_send_json_success( sprintf( __( '%1$s - Google Fonts Checker results saved.', 'host-webfonts-local' ), apply_filters( 'omgf_settings_page_title', 'OMGF' ) ) );
	}
}
