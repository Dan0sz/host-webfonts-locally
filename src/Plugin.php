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

namespace OMGF;

use OMGF\Admin\Settings;
use OMGF\Helper as OMGF;

defined( 'ABSPATH' ) || exit;

class Plugin {
	/**
	 * OMGF constructor.
	 */
	public function __construct() {
		$this->define_constants();

		if ( version_compare( OMGF_CURRENT_DB_VERSION, OMGF_DB_VERSION ) < 0 ) {
			add_action( 'plugins_loaded', [ $this, 'do_migrate_db' ] );
		}

		if ( is_admin() ) {
			new \OMGF\Admin\Actions();
			new \OMGF\Admin\Ajax();
		}

		if ( ! is_admin() ) {
			new \OMGF\Frontend\Actions();
			new \OMGF\Frontend\Filters();
		}

		new \OMGF\Filters();

		if ( ! empty( OMGF::get_option( Settings::OMGF_ADV_SETTING_UNINSTALL ) ) ) {
			register_uninstall_hook( OMGF_PLUGIN_FILE, [ $this, 'do_uninstall' ] );
		}
	}

	/**
	 * Define constants.
	 */
	public function define_constants() {
		/** Prevents undefined constant in OMGF Pro, if its not at version v3.3.0 (yet) */
		define( 'OMGF_OPTIMIZATION_MODE', false );
		define( 'OMGF_SITE_URL', 'https://daan.dev' );
		define( 'OMGF_CACHE_IS_STALE', esc_attr( OMGF::get_option( Settings::OMGF_CACHE_IS_STALE ) ) );
		define( 'OMGF_CURRENT_DB_VERSION', esc_attr( OMGF::get_option( Settings::OMGF_CURRENT_DB_VERSION ) ) );
		define( 'OMGF_UPLOAD_DIR', apply_filters( 'omgf_upload_dir', WP_CONTENT_DIR . '/uploads/omgf' ) );
		define( 'OMGF_UPLOAD_URL', apply_filters( 'omgf_upload_url', str_replace( [ 'http:', 'https:' ], '', WP_CONTENT_URL . '/uploads/omgf' ) ) );
	}

	/**
	 * Run any DB migration scripts if needed.
	 *
	 * @return void
	 */
	public function do_migrate_db() {
		new \OMGF\DB\Migrate();
	}

	/**
	 * Run uninstall script
	 *
	 * @return void
	 */
	public function do_uninstall() {
		new \OMGF\Uninstall();
	}
}
