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
	private function define_constants() {
		if ( defined( 'OMGF_UPLOAD_URL' ) ) {
			return;
		}

		/** Prevents undefined constant errors in OMGF Pro if it's not at version v3.3.0 (yet) */
		define( 'OMGF_OPTIMIZATION_MODE', false );
		define( 'OMGF_SITE_URL', 'https://daan.dev' );
		define( 'OMGF_CACHE_IS_STALE', OMGF::get_option( Settings::OMGF_FLAG_CACHE_IS_STALE ) );
		define( 'OMGF_CURRENT_DB_VERSION', OMGF::get_option( Settings::OMGF_CURRENT_DB_VERSION, '1.0.0' ) );
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

		/**
		 * @since v6.4.2 Scheduled in @see Admin\Actions::schedule_remove_unused_stylesheets()
		 */
		add_action( 'omgf_remove_unused_stylesheets', [ '\OMGF\Helper', 'remove_unused_stylesheets' ] );
		new API\Dashboard();
		new Compatibility();
		new Filters();

		if ( ! empty( OMGF::get_option( Settings::OMGF_ADV_SETTING_UNINSTALL ) ) ) {
			register_uninstall_hook( OMGF_PLUGIN_FILE, [ '\OMGF\Plugin', 'do_uninstall' ] ); // @codeCoverageIgnore
		}

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			\WP_CLI::add_command( 'omgf', '\OMGF\CLI' ); // @codeCoverageIgnore
		}

		register_activation_hook( OMGF_PLUGIN_FILE, [ '\OMGF\Compatibility\Cloudflare', 'maybe_install_mu_plugin' ] );
		register_activation_hook( OMGF_PLUGIN_FILE, [ '\OMGF\Plugin', 'schedule_events' ] );
		register_deactivation_hook( OMGF_PLUGIN_FILE, [ '\OMGF\Compatibility\Cloudflare', 'uninstall_mu_plugin' ] );
		register_deactivation_hook( OMGF_PLUGIN_FILE, [ '\OMGF\Plugin', 'unschedule_events' ] );
	}

	/**
	 * Daily removal of stylesheets which weren't found on the site for a while.
	 *
	 * @see   OMGF::remove_unused_stylesheets()
	 * @since v6.4.2
	 *
	 * @return void
	 */
	public static function schedule_events() {
		if ( ! wp_next_scheduled( 'omgf_remove_unused_stylesheets' ) ) {
			wp_schedule_event( time(), 'daily', 'omgf_remove_unused_stylesheets' );
		}
	}

	/**
	 * @since v6.4.2
	 *
	 * @return void
	 */
	public static function unschedule_events() {
		wp_clear_scheduled_hook( 'omgf_remove_unused_stylesheets' );
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
