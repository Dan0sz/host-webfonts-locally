<?php
defined( 'ABSPATH' ) || exit;

/**
 * @package   OMGF Pro
 * @author    Daan van den Bergh
 *            https://daan.dev
 * @copyright © 2022 Daan van den Bergh. All Rights Reserved.
 * @since     v5.5.7
 */
class OMGF_DB_Migrate_V557 {

	/** @var $version string The version number this migration script was introduced with. */
	private $version = '5.5.7';

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
			OMGF_Admin_Settings::OMGF_OPTIMIZE_SETTING_AUTO_SUBSETS,
			OMGF_Admin_Settings::OMGF_OPTIMIZE_SETTING_DISPLAY_OPTION,
			OMGF_Admin_Settings::OMGF_OPTIMIZE_SETTING_TEST_MODE,
			OMGF_Admin_Settings::OMGF_ADV_SETTING_COMPATIBILITY,
			OMGF_Admin_Settings::OMGF_ADV_SETTING_SUBSETS,
			OMGF_Admin_Settings::OMGF_ADV_SETTING_DEBUG_MODE,
			OMGF_Admin_Settings::OMGF_ADV_SETTING_UNINSTALL,
		];
		
		$this->init();
	}

	/**
	 * Initialize
	 * 
	 * @return void 
	 */
	private function init() {
		$new_settings = [];
		
		foreach ( $this->rows as $row ) {
			$new_settings[ $row ] = get_option( "omgf_$row" );
			delete_option( "omgf_$row" );
		}

		update_option( 'omgf_settings', $new_settings );

		/**
		 * Update stored version number.
		 */
		update_option( OMGF_Admin_Settings::OMGF_CURRENT_DB_VERSION, $this->version );
	}
}
