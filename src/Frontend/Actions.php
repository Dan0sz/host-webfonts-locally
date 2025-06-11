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

namespace OMGF\Frontend;

use OMGF\Admin\Dashboard;
use OMGF\Admin\Settings;
use OMGF\Helper as OMGF;

class Actions {
	const FRONTEND_ASSET_HANDLE = "omgf-frontend";

	/**
	 * Execute all classes required in the frontend.
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'init_frontend' ], 50 );
		add_action( 'admin_bar_menu', [ $this, 'add_admin_bar_item' ], 1000 );
		add_action( 'wp_enqueue_scripts', [ $this, 'maybe_add_frontend_assets' ] );
	}

	/**
	 * Initializes everything required to process frontend optimization.
	 */
	public function init_frontend() {
		new Process();
	}

	/**
	 * @param \WP_Admin_Bar $admin_bar
	 *
	 * @return void
	 */
	public function add_admin_bar_item( \WP_Admin_Bar $admin_bar ) {
		if ( ! $this->should_display_menu() ) {
			return;
		}

		$admin_bar->add_menu(
			[
				'id'     => 'omgf',
				'parent' => null,
				'title'  => apply_filters( 'omgf_settings_page_title', __( 'OMGF', 'host-webfonts-local' ) ),
				'href'   => admin_url( 'options-general.php?page=' . Settings::OMGF_ADMIN_PAGE ),
			]
		);

		$admin_bar->add_menu(
			[
				'id'     => 'omgf-optimize',
				'parent' => 'omgf',
				'title'  => __( 'Re-run fonts optimization', 'host-webfonts-local' ),
				'href'   => add_query_arg( 'omgf_optimize', '1', home_url() ),
			]
		);

		global $wp;

		$permalink_structure = get_option( 'permalink_structure' );
		$site_url            = home_url( $wp->request );

		if ( ! $permalink_structure ) {
			foreach ( $wp->query_vars as $query_var_key => $query_var_value ) {
				$site_url = add_query_arg( $query_var_key, $query_var_value, $site_url );
			}
		}

		$admin_bar->add_menu(
			[
				'id'     => 'omgf-optimize-this',
				'parent' => 'omgf',
				'title'  => __( 'Re-run fonts optimization for current page', 'host-webfonts-local' ),
				'href'   => add_query_arg( 'omgf_optimize', '1', $site_url ),
			]
		);
	}

	/**
	 * Top adminbar menu should be displayed when:
	 *
	 * - User is an administrator
	 * - This is not an admin screen i.e., we're in the frontend
	 * - Disable Quick Access is disabled.
	 *
	 * @return bool
	 */
	private function should_display_menu() {
		$is_admin_user         = current_user_can( 'manage_options' );
		$is_admin_screen       = is_admin();
		$quick_access_disabled = ! empty( OMGF::get_option( Settings::OMGF_ADV_SETTING_DISABLE_ADMIN_BAR_MENU ) );

		return $is_admin_user && ! $is_admin_screen && ! $quick_access_disabled;
	}

	/**
	 * These scripts are only loaded for logged-in administrators unless:
	 * - The Disable Admin Bar Menu option is enabled.
	 * - The Enable Google Fonts checker option is enabled.
	 * - OMGF shouldn't run.
	 * - The current request directly points to a PHP file (some plugin's preview pages do that)
	 *
	 * @return void
	 */
	public function maybe_add_frontend_assets() {
		if ( apply_filters( 'omgf_do_not_load_frontend_assets', ! current_user_can( 'manage_options' ) ) ) {
			return;
		}

		if ( ! empty( $_SERVER[ 'REQUEST_URI' ] ) && str_contains( esc_url_raw( $_SERVER[ 'REQUEST_URI' ] ), '.php' ) || ! Process::should_start() ) {
			return;
		}

		$file_ext = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		$js_file  = plugin_dir_url( OMGF_PLUGIN_FILE ) . "assets/js/" . self::FRONTEND_ASSET_HANDLE . "$file_ext.js";
		$js_path  = plugin_dir_path( OMGF_PLUGIN_FILE ) . "assets/js/" . self::FRONTEND_ASSET_HANDLE . "$file_ext.js";

		wp_register_script( self::FRONTEND_ASSET_HANDLE, $js_file, [], filemtime( $js_path ), [ 'strategy' => 'defer' ] );
		wp_localize_script(
			self::FRONTEND_ASSET_HANDLE,
			'omgf_frontend_i18n',
			[
				'info_box_alert_text'  => __( 'Google Fonts were found on this page. Click here for more information.', 'host-webfonts-local' ),
				'info_box_notice_text' => __( 'There are potential issues in your configuration that require your attention.', 'host-webfonts-local' ),
				'info_box_admin_url'   => admin_url( 'options-general.php?page=' . Settings::OMGF_ADMIN_PAGE ),
				'api_url'              => get_rest_url( null, 'omgf/v1/adminbar-menu/status' ),
				'nonce'                => wp_create_nonce( 'wp_rest' ),
			]
		);
		wp_enqueue_script( self::FRONTEND_ASSET_HANDLE );

		// Even if the above filter forces the JS to load, we'll only need the CSS if the current user is an admin.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$css_file = plugin_dir_url( OMGF_PLUGIN_FILE ) . "assets/css/" . self::FRONTEND_ASSET_HANDLE . "$file_ext.css";
		$css_path = plugin_dir_path( OMGF_PLUGIN_FILE ) . "assets/css/" . self::FRONTEND_ASSET_HANDLE . "$file_ext.css";

		wp_enqueue_style( self::FRONTEND_ASSET_HANDLE, $css_file, [], filemtime( $css_path ) );
	}
}
