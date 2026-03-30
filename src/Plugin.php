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

namespace OMGF;

use OMGF\Admin\Settings;
use OMGF\DB\Migrate;
use OMGF\Helper as OMGF;

class Plugin {
	/**
	 * OMGF constructor.
	 */
	public function __construct() {
		$this->define_constants();
		$this->init();
	}

	/**
	 * Define constants.
	 *
	 * @codeCoverageIgnore
	 */
	public function define_constants() {
		if ( defined( 'OMGF_UPLOAD_URL' ) ) {
			return;
		}

		/** Prevents undefined constant errors in OMGF Pro if it's not at version v3.3.0 (yet) */
		define( 'OMGF_OPTIMIZATION_MODE', false );
		define( 'OMGF_SITE_URL', 'https://daan.dev' );
		define( 'OMGF_CACHE_IS_STALE', esc_attr( OMGF::get_option( Settings::OMGF_FLAG_CACHE_IS_STALE ) ) );
		define( 'OMGF_CURRENT_DB_VERSION', esc_attr( OMGF::get_option( Settings::OMGF_CURRENT_DB_VERSION ) ) );
		define( 'OMGF_UPLOAD_DIR', apply_filters( 'omgf_upload_dir', WP_CONTENT_DIR . '/uploads/omgf' ) );
		define( 'OMGF_UPLOAD_URL', apply_filters( 'omgf_upload_url', str_replace( [ 'http:', 'https:' ], '', WP_CONTENT_URL . '/uploads/omgf' ) ) );
	}

	private function init() {
		if ( version_compare( OMGF_CURRENT_DB_VERSION, OMGF_DB_VERSION ) < 0 ) {
			add_action( 'plugins_loaded', [ $this, 'do_migrate_db' ] );
		}

		// Only load in wp-admin.
		if ( is_admin() ) {
			new Admin\Actions();
			new Admin\Ajax();
		}

		// Only load in the frontend.
		if ( ! is_admin() ) {
			new Frontend\Actions();
			new Frontend\Filters();
			new Frontend\Compatibility();
		}

		// Load globally.
		new API\AdminbarMenu();
		new API\Dashboard();
		new Compatibility();
		new Filters();

		if ( ! empty( OMGF::get_option( Settings::OMGF_ADV_SETTING_UNINSTALL ) ) ) {
			register_uninstall_hook( OMGF_PLUGIN_FILE, [ '\OMGF\Plugin', 'do_uninstall' ] ); // @codeCoverageIgnore
		}

		if ( is_admin() ) {
			register_activation_hook( OMGF_PLUGIN_FILE, [ '\OMGF\Compatibility\Cloudflare', 'maybe_install_mu_plugin' ] );
			register_deactivation_hook( OMGF_PLUGIN_FILE, [ '\OMGF\Compatibility\Cloudflare', 'uninstall_mu_plugin' ] );
		}
	}

	/**
	 * Run uninstall script
	 *
	 * @return void
	 */
	public static function do_uninstall() {
		new Uninstall();
	}

	/**
	 * Run any DB migration scripts if needed.
	 *
	 * @return void
	 * @codeCoverageIgnore
	 */
	public function do_migrate_db() {
		new Migrate();
	}
}
