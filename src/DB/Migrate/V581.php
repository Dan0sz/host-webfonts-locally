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
* @copyright: © 2025 Daan van den Bergh
* @url      : https://daan.dev
* * * * * * * * * * * * * * * * * * * */

namespace OMGF\DB\Migrate;

use OMGF\Admin\Settings;
use OMGF\Helper as OMGF;

/**
 * @codeCoverageIgnore
 */
class V581 {
	/** @var $version string The version number this migration script was introduced with. */
	private $version = '5.8.1';

	/**
	 * Build class
	 *
	 * @return void
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Initialize
	 *
	 * @return void
	 */
	private function init() {
		/**
		 * Delete the omgf_fonts object which is no longer used.
		 */
		OMGF::delete_option( 'omgf_fonts' );

		/**
		 * Update stored version number.
		 */
		OMGF::update_option( Settings::OMGF_CURRENT_DB_VERSION, $this->version );
	}
}
