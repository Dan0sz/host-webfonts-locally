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
use OMGF\Helper as OMGF;

defined( 'ABSPATH' ) || exit;

class V560 {

	/** @var $version string The version number this migration script was introduced with. */
	private $version = '5.6.0';

	/**
	 * All DB rows that need to be migrated and removed.
	 *
	 * @var string[]
	 */
	private $rows = [];

	/**
	 * Buid
	 *
	 * @return void
	 */
	public function __construct() {
		$this->rows = [
			Settings::OMGF_OPTIMIZE_SETTING_AUTO_SUBSETS,
			Settings::OMGF_OPTIMIZE_SETTING_DISPLAY_OPTION,
			Settings::OMGF_OPTIMIZE_SETTING_TEST_MODE,
			Settings::OMGF_OPTIMIZE_SETTING_CACHE_KEYS,
			Settings::OMGF_OPTIMIZE_SETTING_UNLOAD_STYLESHEETS,
			Settings::OMGF_ADV_SETTING_COMPATIBILITY,
			Settings::OMGF_ADV_SETTING_SUBSETS,
			Settings::OMGF_ADV_SETTING_DEBUG_MODE,
			Settings::OMGF_ADV_SETTING_UNINSTALL,
		];

		$this->init();
	}

	/**
	 * Initialize
	 *
	 * @return void
	 */
	private function init() {
		$new_settings = OMGF::get_settings();

		foreach ( $this->rows as $row ) {
			$option_value = get_option( "omgf_$row" );

			if ( $option_value !== false ) {
				$new_settings[ $row ] = get_option( "omgf_$row" );

				delete_option( "omgf_$row" );
			}
		}

		OMGF::update_option( 'omgf_settings', $new_settings );

		/**
		 * Update stored version number.
		 */
		OMGF::update_option( Settings::OMGF_CURRENT_DB_VERSION, $this->version );
	}
}
