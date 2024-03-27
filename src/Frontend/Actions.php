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
 * @copyright: © 2017 - 2024 Daan van den Bergh
 * @url      : https://daan.dev
 * * * * * * * * * * * * * * * * * * * */

namespace OMGF\Frontend;

use OMGF\Admin\Settings;
use OMGF\Helper as OMGF;

class Actions {
	/**
	 * Execute all classes required in the frontend.
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'init_frontend' ], 50 );

		add_action( 'admin_bar_menu', [ $this, 'add_admin_bar_item' ], 1000 );
	}

	/**
	 * Initializes everything required to process frontend optimization.
	 */
	public function init_frontend() {
		new \OMGF\Frontend\Process();
	}

	/**
	 * @param \WP_Admin_Bar $admin_bar
	 *
	 * @return void
	 */
	public function add_admin_bar_item( \WP_Admin_Bar $admin_bar ) {
		/**
		 * Display only in frontend, for logged in admins.
		 */
		if ( ! defined( 'DAAN_DOING_TESTS' ) &&
			( ! current_user_can( 'manage_options' ) || is_admin() || OMGF::get_option( Settings::OMGF_ADV_SETTING_DISABLE_QUICK_ACCESS ) ) ) {
			return; // @codeCoverageIgnore
		}

		$admin_bar->add_menu(
			[
				'id'     => 'omgf',
				'parent' => null,
				'title'  => apply_filters( 'omgf_settings_page_title', __( 'OMGF', 'host-webfonts-local' ) ),
				'href'   => admin_url( 'options-general.php?page=optimize-webfonts' ),
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
}
