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
use OMGF\Helper;
use OMGF\Helper as OMGF;

/**
 * @codeCoverageIgnore
 */
class V620 {
	/** @var $version string The version number this migration script was introduced with. */
	private $version = '6.2.0';

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
		Helper::delete_option( 'omgf_optimize_has_run' );
		Helper::delete_option( 'auto_subsets' );

		/**
		 * Update stored version number.
		 */
		OMGF::update_option( Settings::OMGF_CURRENT_DB_VERSION, $this->version );
	}
}
