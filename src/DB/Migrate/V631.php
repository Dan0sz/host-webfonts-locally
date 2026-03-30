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

namespace OMGF\DB\Migrate;

use OMGF\Admin\Settings;
use OMGF\Compatibility\Cloudflare;
use OMGF\Helper as OMGF;

/**
 * @codeCoverageIgnore
 */
class V631 {
	/** @var $version string The version number this migration script was introduced with. */
	private $version = '6.3.1';

	/**
	 * Build class.
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * This migration script doesn't do much, besides showing a notice after updating.
	 *
	 * @return void
	 */
	private function init() {
		Cloudflare::maybe_install_mu_plugin();

		/**
		 * Update stored version number.
		 */
		OMGF::update_option( Settings::OMGF_CURRENT_DB_VERSION, $this->version );
	}
}
