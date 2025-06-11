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

use OMGF\Helper as OMGF;

class Ajax {
	/**
	 * OMGF\Ajax constructor.
	 */
	public function __construct() {
		add_action( 'wp_ajax_omgf_hide_notice', [ $this, 'hide_notice' ] );
		add_action( 'wp_ajax_omgf_remove_stylesheet_from_db', [ $this, 'remove_stylesheet_from_db' ] );
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

		$warning_id     = $_POST[ 'warning_id' ];
		$hidden_notices = OMGF::get_option( Settings::OMGF_HIDDEN_NOTICES, [] );

		if ( ! in_array( $warning_id, $hidden_notices ) ) {
			$hidden_notices[] = $warning_id;
		}

		OMGF::update_option( Settings::OMGF_HIDDEN_NOTICES, $hidden_notices, 'off' );

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

		$handle                   = $_POST[ 'handle' ];
		$optimized_fonts          = OMGF::admin_optimized_fonts();
		$optimized_fonts_frontend = OMGF::optimized_fonts();
		$unloaded_fonts           = OMGF::unloaded_fonts();
		$unloaded_stylesheets     = OMGF::unloaded_stylesheets();
		$preloaded_fonts          = OMGF::preloaded_fonts();
		$cache_keys               = OMGF::cache_keys();

		$this->maybe_unset( Settings::OMGF_OPTIMIZE_SETTING_CACHE_KEYS, $cache_keys, $handle, true );
		$this->maybe_unset( Settings::OMGF_OPTIMIZE_SETTING_OPTIMIZED_FONTS, $optimized_fonts, $handle );
		$this->maybe_unset( Settings::OMGF_OPTIMIZE_SETTING_OPTIMIZED_FONTS_FRONTEND, $optimized_fonts_frontend, $handle );
		$this->maybe_unset( Settings::OMGF_OPTIMIZE_SETTING_UNLOAD_FONTS, $unloaded_fonts, $handle );
		$this->maybe_unset( Settings::OMGF_OPTIMIZE_SETTING_UNLOAD_STYLESHEETS, $unloaded_stylesheets, $handle, true );
		$this->maybe_unset( Settings::OMGF_OPTIMIZE_SETTING_PRELOAD_FONTS, $preloaded_fonts, $handle );
	}

	/**
	 * Unset a $key from $array and update $option_name. Optionally, store the array as a comma-separated string.
	 *
	 * @param string $option_name     The option name to update.
	 * @param array  $array           The array to saarch.
	 * @param string $key             The key to unset when found.
	 * @param bool   $comma_separated When true, $array is converted to a comma-separated string before saving it
	 *                                to the database.
	 *
	 * @return void
	 */
	private function maybe_unset( $option_name, $array, $key, $comma_separated = false ) {
		if ( isset( $array[ $key ] ) || in_array( $key, $array ) ) {
			if ( $comma_separated ) {
				$cache_key = OMGF::get_cache_key( $key ) ?: $key;
				$key_key   = array_search( $cache_key, $array );

				unset( $array[ $key_key ] );
			} else {
				unset( $array[ $key ] );
			}

			if ( $comma_separated ) {
				$array = implode( ',', $array );
			}

			OMGF::update_option( $option_name, $array );
		}
	}

	/**
	 * Removes the stale cache mark. Should be triggered along with a form submit.
	 *
	 * @codeCoverageIgnore Because all it does, basically, is delete an option from the DB.
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
						Settings::OMGF_CACHE_IS_STALE,
					],
				];
			}
		);

		$this->empty_cache();

		delete_option( Settings::OMGF_CACHE_IS_STALE );
	}

	/**
	 * Empties all cache related entries in the database.
	 *
	 * @param string $initiator
	 *
	 * @return void
	 *
	 * @codeCoverageIgnore because this works the file system.
	 */
	private function empty_cache( $initiator = 'optimize-webfonts' ) {
		$entries      = array_filter( (array) glob( OMGF_UPLOAD_DIR . '/*' ) );
		$instructions = apply_filters(
			'omgf_clean_up_instructions',
			[
				'init'    => $initiator,
				'exclude' => [],
				'queue'   => [
					Settings::OMGF_GOOGLE_FONTS_CHECKER_RESULTS,
					Settings::OMGF_AVAILABLE_USED_SUBSETS,
					Settings::OMGF_CACHE_IS_STALE,
					Settings::OMGF_CACHE_TIMESTAMP,
					Settings::OMGF_FOUND_IFRAMES,
					Settings::OMGF_OPTIMIZE_HAS_RUN,
					Settings::OMGF_OPTIMIZE_SETTING_CACHE_KEYS,
					Settings::OMGF_OPTIMIZE_SETTING_OPTIMIZED_FONTS,
					Settings::OMGF_OPTIMIZE_SETTING_OPTIMIZED_FONTS_FRONTEND,
					Settings::OMGF_OPTIMIZE_SETTING_UNLOAD_FONTS,
					Settings::OMGF_OPTIMIZE_SETTING_PRELOAD_FONTS,
					Settings::OMGF_OPTIMIZE_SETTING_UNLOAD_STYLESHEETS,
				],
			]
		);

		foreach ( $entries as $entry ) {
			if ( in_array( $entry, $instructions[ 'exclude' ] ) ) {
				continue;
			}

			OMGF::delete( $entry );
		}

		foreach ( $instructions[ 'queue' ] as $option ) {
			OMGF::delete_option( $option );
		}
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
			$init = $_POST[ 'init' ] ?? '';

			$this->empty_cache( $init );

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
