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

class Actions {
	/**
	 * Execute all classes required in the frontend.
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'init_frontend' ], 50 );

		add_action( 'admin_bar_menu', [ $this, 'add_admin_bar_item' ], 1000 );
	}

	/**
	 *
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
		if ( ! current_user_can( 'manage_options' ) || is_admin() ) {
			return;
		}

		$admin_bar->add_menu(
			[
				'id'     => 'omgf-pro',
				'parent' => null,
				'title'  => apply_filters( 'omgf_settings_page_title', __( 'OMGF', 'host-webfonts-local' ) ),
				'href'   => admin_url( 'options-general.php?page=optimize-webfonts' ),
			]
		);

		global $wp;

		$admin_bar->add_menu(
			[
				'id'     => 'omgf-pro-refresh-cache',
				'parent' => 'omgf-pro',
				'title'  => __( 'Refresh cached fonts', 'host-webfonts-local' ),
				'href'   => home_url( $wp->request . '?omgf_optimize=1' ),
			]
		);

		$admin_bar->add_menu(
			[
				'id'     => 'omgf-pro-refresh-cache-current-page',
				'parent' => 'omgf-pro',
				'title'  => __( 'Refresh cached fonts for this page', 'host-webfonts-local' ),
				'href'   => home_url( $wp->request . '?omgf_optimize=1' ),
			]
		);
	}
}
