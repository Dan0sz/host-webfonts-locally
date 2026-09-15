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
* @copyright: © 2026 Daan van den Bergh
* @url      : https://daan.dev
* * * * * * * * * * * * * * * * * * * */

namespace OMGF\Admin;

use OMGF\Admin\Settings;
use OMGF\Optimize\Run;

class Optimize {
	/**
	 * Nonce action which authorizes a Save & Optimize run. It's added to the redirect URL after
	 * the settings are saved, so it's only present on a request that follows a form submit.
	 */
	const NONCE_ACTION = 'omgf-optimize';

	/** @var string */
	private $settings_page = '';

	/** @var string */
	private $settings_tab = '';

	/** @var bool */
	private $settings_updated = false;

	/** @var string */
	private $nonce = '';

	/**
	 * OMGF\Admin\Optimize constructor.
	 */
	public function __construct() {
		$this->settings_page    = isset( $_GET['page'] ) ? sanitize_text_field( $_GET['page'] ) : '';
		$this->settings_tab     = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : Settings::OMGF_SETTINGS_FIELD_OPTIMIZE;
		$this->settings_updated = isset( $_GET['settings-updated'] );
		$this->nonce            = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( $_GET['_wpnonce'] ) : '';

		$this->init();
	}

	/**
	 * Run Optimization after settings are updated.
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

		/**
		 * A run has to be authorized by a capable user and the nonce that's issued when the settings are saved.
		 *
		 * @see Actions::update_settings()
		 */
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( wp_verify_nonce( $this->nonce, self::NONCE_ACTION ) < 1 ) {
			return;
		}

		add_filter( 'http_request_args', [ $this, 'verify_ssl' ] );

		$this->run();
	}

	/**
	 * Run Save & Optimize.
	 *
	 * @return void
	 */
	private function run() {
		new Run();
	}

	/**
	 * If this site is non-SSL, it makes no sense to verify its SSL certificates.
	 * Settings sslverify to false will set CURLOPT_SSL_VERIFYPEER and CURLOPT_SSL_VERIFYHOST
	 * to 0 further down the road.
	 *
	 * @param $args
	 *
	 * @return array
	 *
	 * @codeCoverageIgnore
	 */
	public function verify_ssl( $args ) {
		$args['sslverify'] = apply_filters(
			'omgf_admin_optimize_verify_ssl',
			str_contains( get_home_url(), 'https:' )
		);

		return $args;
	}
}
