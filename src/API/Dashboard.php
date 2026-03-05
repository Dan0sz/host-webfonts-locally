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

namespace OMGF\API;

use OMGF\Admin\Settings;

class Dashboard {
	/** @var string */
	private $namespace = 'omgf/v1';

	/** @var string */
	private $base = 'dashboard';

	/** @var string */
	private $endpoint = 'dismiss-notice';

	/**
	 * Build class.
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Action/filter hooks.
	 *
	 * @return void
	 */
	private function init() {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	/**
	 * Register the API route.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->base . '/' . $this->endpoint,
			[
				[
					'methods'             => 'POST',
					'callback'            => [ $this, 'dismiss_notice' ],
					'permission_callback' => [ $this, 'get_permission' ],
				],
				'schema' => null,
			]
		);
	}

	/**
	 * Only logged-in administrators should be allowed to use the API.
	 *
	 * @return bool
	 */
	public function get_permission() {
		$is_allowed = current_user_can( 'manage_options' );

		return apply_filters( 'omgf_api_dashboard_permission', $is_allowed );
	}

	/**
	 * Dismiss the performance checker notice for 30 days.
	 *
	 * @return \WP_REST_Response
	 */
	public function dismiss_notice() {
		set_transient( Settings::OMGF_DISMISS_NOTICE_TRANSIENT . get_current_user_id(), true, 30 * DAY_IN_SECONDS );

		return new \WP_REST_Response( [ 'success' => true ] );
	}
}
