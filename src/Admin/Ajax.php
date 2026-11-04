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

namespace OMGF\Admin;

use OMGF\Cache;
use OMGF\Helper as OMGF;

class Ajax {
	/**
	 * OMGF\Ajax constructor.
	 */
	public function __construct() {
		add_action( 'wp_ajax_omgf_hide_notice', [ $this, 'hide_notice' ] );
		add_action( 'wp_ajax_omgf_remove_stylesheet_from_db', [ $this, 'remove_stylesheet_from_db' ] );
		add_action( 'wp_ajax_omgf_dismiss_removed_stylesheets', [ $this, 'dismiss_removed_stylesheets' ] );
		add_action( 'wp_ajax_omgf_refresh_cache', [ $this, 'refresh_cache' ] );
		add_action( 'wp_ajax_omgf_empty_dir', [ $this, 'empty_directory' ] );
		add_action( 'wp_ajax_omgf_download_log', [ $this, 'download_log' ] );
		add_action( 'wp_ajax_omgf_delete_log', [ $this, 'delete_log' ] );
	}

	/**
	 * @since              v5.4.0 Remove notice from dashboard and return new HTML.
	 * @return void Valid HTML.
	 *
	 * @codeCoverageIgnore because code execution is killed at the end.
	 */
	public function hide_notice() {
		check_ajax_referer( Settings::OMGF_ADMIN_PAGE, 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'Hmmm, are you lost?', 'host-webfonts-local' ) ); // @codeCoverageIgnore
		}

		$warning_id     = $_POST['warning_id'];
		$hidden_notices = OMGF::get_option( Settings::OMGF_HIDDEN_NOTICES, [] );

		if ( ! in_array( $warning_id, $hidden_notices ) ) {
			$hidden_notices[] = $warning_id;
		}

		OMGF::update_option( Settings::OMGF_HIDDEN_NOTICES, $hidden_notices );

		$result = Dashboard::get_dashboard_html();

		wp_send_json_success( $result );
	}

	/**
	 * Remove stylesheet with $handle from database.
	 *
	 * @codeCoverageIgnore Because it just deletes entries from the database.
	 */
	public function remove_stylesheet_from_db() {
		check_ajax_referer( Settings::OMGF_ADMIN_PAGE, 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( "Hmmm, you're not supposed to be here.", 'host-webfonts-local' ) ); // @codeCoverageIgnore
		}

		OMGF::remove_stylesheet( sanitize_text_field( wp_unslash( $_POST['handle'] ?? '' ) ) );
	}

	/**
	 * Mark the notice about automatically removed stylesheets as read.
	 *
	 * @since v6.4.2 Uses admin-ajax.php, because it's on the same origin as the admin, unlike the REST API.
	 *
	 * @codeCoverageIgnore Because code execution is killed at the end.
	 */
	public function dismiss_removed_stylesheets() {
		check_ajax_referer( 'omgf_dismiss_removed_stylesheets', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( "Hmmm, you're not supposed to be here.", 'host-webfonts-local' ) );
		}

		$handles = array_map( 'sanitize_text_field', (array) wp_unslash( $_POST['handles'] ?? [] ) );

		OMGF::dismiss_removed_stylesheets( $handles );

		wp_send_json_success();
	}

	/**
	 * Removes the stale cache mark. Should be triggered along with a form submit.
	 *
	 * @codeCoverageIgnore Because all it does, basically, is delete an option from the DB.
	 * @throws \ReflectionException
	 */
	public function refresh_cache() {
		check_ajax_referer( Settings::OMGF_ADMIN_PAGE, 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( "Hmmm, you're not supposed to be here.", 'host-webfonts-local' ) ); // @codeCoverageIgnore
		}

		add_filter(
			'omgf_clean_up_instructions',
			function () {
				return [
					'init'    => Settings::OMGF_ADMIN_PAGE,
					'exclude' => [],
					'queue'   => [
						Settings::OMGF_FLAG_CACHE_IS_STALE,
					],
				];
			}
		);

		OMGF::flush_cache();

		delete_option( Settings::OMGF_FLAG_CACHE_IS_STALE );
	}

	/**
	 * Empty cache directory.
	 * @since              v4.5.3: Hardened security.
	 * @since              v4.5.5: Added authentication.
	 *
	 * @codeCoverageIgnore Because basically all it does is throw notices.
	 */
	public function empty_directory() {
		check_ajax_referer( Settings::OMGF_ADMIN_PAGE, 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( "Hmmm, you're not supposed to be here.", 'host-webfonts-local' ) ); // @codeCoverageIgnore
		}

		try {
			$init = $_POST['init'] ?? '';

			OMGF::flush_cache( $init );

			Notice::set_notice( __( 'Cache directory successfully emptied.', 'host-webfonts-local' ) );
		} catch ( \Exception $e ) {
			Notice::set_notice(
				__( 'OMGF encountered an error while emptying the cache directory: ', 'host-webfonts-local' ) . $e->getMessage(),
				'omgf-cache-error',
				'error',
				$e->getCode()
			);
		}
	}

	/**
	 * Returns the debug log file as a download prompt to the browser (if it exists)
	 *
	 * @codeCoverageIgnore because it kills code execution at the end.
	 */
	public function download_log() {
		check_ajax_referer( Settings::OMGF_ADMIN_PAGE, 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( "Hmmm, you're not supposed to be here.", 'host-webfonts-local' ) ); // @codeCoverageIgnore
		}

		$filename = OMGF::log_file();

		/**
		 * Shouldn't happen, but you never know.
		 */
		if ( ! file_exists( $filename ) ) {
			wp_die();
		}

		$basename = basename( $filename );
		$filesize = filesize( $filename );

		header( 'Content-Description: File Transfer' );
		header( 'Content-Type: text/plain' );
		header( 'Cache-Control: no-cache, must-revalidate' );
		header( 'Expires: 0' );
		header( "Content-Disposition: attachment; filename=$basename" );
		header( "Content-Length: $filesize" );
		header( 'Pragma: public' );

		flush();

		readfile( $filename );

		wp_die();
	}

	/**
	 * Deletes the OMGF log file if it exists.
	 *
	 * This method performs necessary permission checks and nonce verification.
	 * If the log file is successfully deleted, a success message is added to settings errors.
	 * The function is designed to terminate the request after execution.
	 *
	 * @return void
	 *
	 * @codeCoverageIgnore because it kills code execution at the end.
	 */
	public function delete_log() {
		check_ajax_referer( Settings::OMGF_ADMIN_PAGE, 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( "Hmmm, you're not supposed to be here.", 'host-webfonts-local' ) ); // @codeCoverageIgnore
		}

		$filename = OMGF::log_file();

		if ( file_exists( $filename ) ) {
			unlink( $filename );

			add_settings_error(
				'general',
				'omgf-log-file-deleted',
				__( 'Log file successfully deleted', 'host-webfonts-local' ),
				'success'
			);
		}

		wp_die();
	}
}
