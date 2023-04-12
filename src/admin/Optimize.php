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
* @copyright: © 2023 Daan van den Bergh
* @url      : https://daan.dev
* * * * * * * * * * * * * * * * * * * */

namespace OMGF\Admin;

use OMGF\Admin\Settings;
use OMGF\Optimize\Run;

defined( 'ABSPATH' ) || exit;

class Optimize {

	/** @var string */
	private $settings_page = '';

	/** @var string */
	private $settings_tab = '';

	/** @var bool */
	private $settings_updated = false;

	/**
	 * OMGF\Admin\Optimize constructor.
	 */
	public function __construct() {
		$this->settings_page    = $_GET['page'] ?? '';
		$this->settings_tab     = $_GET['tab'] ?? Settings::OMGF_SETTINGS_FIELD_OPTIMIZE;
		$this->settings_updated = isset( $_GET['settings-updated'] );

		$this->init();
	}

	/**
	 * Run either manual or auto mode after settings are updated.
	 *
	 * @return void
	 */
	private function init() {
		if ( Settings::OMGF_ADMIN_PAGE !== $this->settings_page ) {
			return;
		}

		if ( Settings::OMGF_SETTINGS_FIELD_OPTIMIZE !== $this->settings_tab ) {
			return;
		}

		if ( ! $this->settings_updated ) {
			return;
		}

		add_filter( 'http_request_args', [ $this, 'verify_ssl' ] );

		$this->run();
	}

	/**
	 * If this site is non-SSL it makes no sense to verify its SSL certificates.
	 *
	 * Settings sslverify to false will set CURLOPT_SSL_VERIFYPEER and CURLOPT_SSL_VERIFYHOST
	 * to 0 further down the road.
	 *
	 * @param mixed $url
	 * @return array
	 */
	public function verify_ssl( $args ) {
		$args['sslverify'] = apply_filters( 'omgf_admin_optimize_verify_ssl', strpos( get_home_url(), 'https:' ) !== false );

		return $args;
	}

	/**
	 * Run Force mode.
	 *
	 * @return void
	 */
	private function run() {
		new Run();
	}
}
