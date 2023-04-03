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

namespace OMGF\DB\Migrate;

use OMGF\Admin\Settings;

defined( 'ABSPATH' ) || exit;

class V533 {

	/** @var $version string The version number this migration script was introduced with. */
	private $version = '5.3.3';

	/**
	 * Buid
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
		$subsets = get_option( Settings::OMGF_ADV_SETTING_SUBSETS );

		if ( ! $subsets ) {
			update_option( Settings::OMGF_ADV_SETTING_SUBSETS, [ 'latin', 'latin-ext' ] );
		}

		/**
		 * Update stored version number.
		 */
		update_option( Settings::OMGF_CURRENT_DB_VERSION, $this->version );
	}
}
