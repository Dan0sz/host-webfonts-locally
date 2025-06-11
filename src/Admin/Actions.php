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

class Actions {
	/**
	 * Execute all actions required in wp-admin.
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( '_admin_menu', [ $this, 'init_admin' ] );
		add_action( 'admin_init', [ $this, 'do_optimize' ] );
		add_action( 'admin_init', [ $this, 'update_settings' ] );
		add_action( 'in_plugin_update_message-' . OMGF_PLUGIN_BASENAME, [ $this, 'render_update_notice' ], 11 );
		add_action( 'omgf_pre_update_setting_cache_keys', [ $this, 'clean_stale_cache' ], 10, 2 );
	}

	/**
	 * Needs to run before admin_menu and admin_init.
	 *
	 * @action _admin_menu
	 *
	 * @codeCoverageIgnore
	 */
	public function init_admin() {
		new Settings();
	}

	/**
	 * Initialize the Save & Optimize routine.
	 *
	 * @return void
	 *
	 * @codeCoverageIgnore
	 */
	public function do_optimize() {
		new Optimize();
	}

	/**
	 * We use a custom update action, because we're storing multidimensional arrays upon form submit.
	 * This prevents us from having to use AJAX, serialize(), stringify() and eventually having to json_decode() it, i.e.
	 * a lot of headaches.
	 *
	 * @since v5.6.0
	 */
	public function update_settings() {
		if ( wp_doing_cron() || wp_doing_ajax() || empty( $_POST[ 'action' ] ) || $_POST[ 'action' ] !== 'omgf-update' ) {
			return; // @codeCoverageIgnore
		}

		$action = array_key_exists( 'tab', $_GET ) ? $_GET[ 'tab' ] . '-options' : 'omgf-optimize-settings-options';
		$nonce  = $_POST[ '_wpnonce' ] ?? '';

		if ( wp_verify_nonce( $nonce, $action ) < 1 ) {
			return; // @codeCoverageIgnore
		}

		if ( ! defined( 'DAAN_DOING_TESTS' ) && ! current_user_can( 'manage_options' ) ) {
			return; // @codeCoverageIgnore
		}

		$updated_settings = $this->clean( $_POST );

		foreach ( $updated_settings as $option_name => $option_value ) {
			if ( ! str_starts_with( $option_name, 'omgf_' ) || ( empty( $option_value ) && $option_value !== '0' ) ) {
				continue;
			}

			if ( is_array( $option_value ) ) {
				foreach ( $option_value as $setting_name => $setting_value ) {
					do_action( "omgf_pre_update_setting_$setting_name", $setting_name, $setting_value );
				}
			}

			if ( is_string( $option_value ) && $option_value !== '0' ) {
				$merged = $option_value;
			} elseif ( $option_value === '0' ) {
				$merged = [];
			} else {
				$current_options = ! empty( OMGF::get_option( $option_name, [] ) ) ? OMGF::get_option( $option_name ) : [];
				$merged          = array_replace( $current_options, $option_value );
			}

			OMGF::update_option( $option_name, $merged );
		}

		/**
		 * Additional update actions can be added here.
		 *
		 * @since v5.6.0
		 */
		do_action( 'omgf_update_settings', $updated_settings );

		// Display settings errors.
		set_transient( 'settings_errors', get_settings_errors(), 30 );

		// Redirect back to the settings page that was submitted.
		$goback = add_query_arg( 'settings-updated', 'true', wp_get_referer() );

		if ( ! defined( 'DAAN_DOING_TESTS' ) ) {
			wp_redirect( $goback ); // @codeCoverageIgnore
			exit; // @codeCoverageIgnore
		}
	}

	/**
	 * Clean variables using `sanitize_text_field`.
	 * Arrays are cleaned recursively. Non-scalar values are ignored.
	 *
	 * @since 5.5.7
	 *
	 * @param string|array $var Sanitize the variable.
	 *
	 * @return string|array
	 */
	private function clean( $var ) {
		if ( is_array( $var ) ) {
			return array_map( [ $this, 'clean' ], $var );
		}

		return is_scalar( $var ) ? sanitize_text_field( wp_unslash( $var ) ) : $var;
	}

	/**
	 * Render update notices if available.
	 *
	 * @param mixed $plugin
	 * @param mixed $response
	 *
	 * @return void
	 *
	 * @codeCoverageIgnore
	 */
	public function render_update_notice( $plugin ) {
		$current_version = $plugin[ 'Version' ];
		$new_version     = $plugin[ 'new_version' ];

		if ( version_compare( $current_version, $new_version, '<' ) ) {
			$response = wp_remote_get( 'https://daan.dev/omgf-update-notices.json?' . substr( uniqid( '', true ), - 5 ) );

			if ( is_wp_error( $response ) ) {
				return;
			}

			$update_notices = (array) json_decode( wp_remote_retrieve_body( $response ) );

			if ( ! isset( $update_notices[ $new_version ] ) ) {
				return;
			}

			echo wp_kses_post(
				sprintf(
					' <strong>' . __( 'This update includes major changes, please <a href="%s" target="_blank">read this</a> before continuing.', 'host-webfonts-local' ) . '</strong>',
					$update_notices[ $new_version ]->url
				)
			);
		}
	}

	/**
	 * Cleans up the old (unused) cache directories.
	 */
	public function clean_stale_cache( $option_name, $option_value ) {
		$old_keys = OMGF::cache_keys();
		$new_keys = explode( ',', $option_value );
		$diff     = array_diff( $new_keys, $old_keys );

		foreach ( $diff as $new_cache_key ) {
			$dir_to_remove = '';
			$base_key      = preg_replace( '/-mod.*?$/', '', $new_cache_key );

			foreach ( $old_keys as $old_cache_key ) {
				if ( str_contains( $old_cache_key, $base_key ) ) {
					$dir_to_remove = $old_cache_key;

					break;
				}
			}

			if ( ! $dir_to_remove ) {
				continue; // @codeCoverageIgnore
			}

			$dir = OMGF_UPLOAD_DIR . '/' . $dir_to_remove;

			if ( $dir !== realpath( $dir ) ) {
				continue; // @codeCoverageIgnore
			}

			$this->delete_files( $dir );

			if ( $this->dir_is_empty( $dir ) ) {
				rmdir( OMGF_UPLOAD_DIR . '/' . $dir_to_remove );
			}
		}
	}

	/**
	 * Delete files from $dir.
	 *
	 * @param mixed $dir
	 *
	 * @return void
	 */
	private function delete_files( $dir ) {
		array_map( 'unlink', glob( $dir . '/*.*' ) );
	}

	/**
	 * Check if directory is empty.
	 * This works because a new FilesystemIterator will initially point to the first file in the folder -
	 * if there are no files in the folder, valid() will return false
	 *
	 * @param mixed $dir
	 *
	 * @return bool
	 */
	private function dir_is_empty( $dir ) {
		if ( ! file_exists( $dir ) ) {
			return false; // @codeCoverageIgnore
		}

		$iterator = new \FilesystemIterator( $dir );

		return ! $iterator->valid();
	}
}
