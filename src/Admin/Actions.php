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
 * @copyright: © 2017 - 2023 Daan van den Bergh
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
		add_action( 'in_plugin_update_message-' . OMGF_PLUGIN_BASENAME, [ $this, 'render_update_notice' ], 11, 2 );
	}

	/**
	 * Needs to run before admin_menu and admin_init.
	 *
	 * @action _admin_menu
	 */
	public function init_admin() {
		new Settings();
	}

	/**
	 * Initialize the Save & Optimize routine.
	 *
	 * @return void
	 */
	public function do_optimize() {
		new \OMGF\Admin\Optimize();
	}

	/**
	 * We use a custom update action, because we're storing multidimensional arrays upon form submit.
	 *
	 * This prevents us from having to use AJAX, serialize(), stringify() and eventually having to json_decode() it, i.e.
	 * a lot of headaches.
	 *
	 * @since v5.6.0
	 */
	public function update_settings() {
		// phpcs:ignore WordPress.Security
		if ( empty( $_POST['action'] ) || $_POST['action'] !== 'omgf-update' ) {
			return;
		}

		// phpcs:ignore
		$updated_settings = $this->clean($_POST);

		foreach ( $updated_settings as $option_name => $option_value ) {
			if ( strpos( $option_name, 'omgf_' ) !== 0 || ( empty( $option_value ) && $option_value !== '0' ) ) {
				continue;
			}

			$merged = [];

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
		do_action( 'omgf_update_settings' );

		// Redirect back to the settings page that was submitted.
		$goback = add_query_arg( 'settings-updated', 'true', wp_get_referer() );
		// phpcs:ignore
		wp_redirect( $goback );
		exit;
	}

	/**
	 * Clean variables using `sanitize_text_field`.
	 * Arrays are cleaned recursively. Non-scalar values are ignored.
	 *
	 * @param string|array $var Sanitize the variable.
	 *
	 * @since 5.5.7
	 *
	 * @return string|array
	 */
	private function clean( $var ) {
		if ( is_array( $var ) ) {
			return array_map( [ __CLASS__, __METHOD__ ], $var );
		}

		return is_scalar( $var ) ? sanitize_text_field( wp_unslash( $var ) ) : $var;
	}

	/**
	 * Render update notices if available.
	 *
	 * @param mixed $plugin
	 * @param mixed $response
	 * @return void
	 */
	public function render_update_notice( $plugin, $response ) {
		$current_version = $plugin['Version'];
		$new_version     = $plugin['new_version'];

		if ( version_compare( $current_version, $new_version, '<' ) ) {
			$response = wp_remote_get( 'https://daan.dev/omgf-update-notices.json?' . substr( uniqid( '', true ), -5 ) );

			if ( is_wp_error( $response ) ) {
				return;
			}

			$update_notices = (array) json_decode( wp_remote_retrieve_body( $response ) );

			if ( ! isset( $update_notices[ $new_version ] ) ) {
				return;
			}

			$allowed_html = [
				'strong' => [],
				'a'      => [],
			];

			wp_kses( sprintf( ' <strong>' . __( 'This update includes major changes, please <a href="%s" target="_blank">read this</a> before continuing.' ) . '</strong>', $update_notices[ $new_version ]->url ), $allowed_html );
		}
	}
}
