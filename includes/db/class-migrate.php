<?php
defined( 'ABSPATH' ) || exit;

/**
 * @package   OMGF Pro
 * @author    Daan van den Bergh
 *            https://daan.dev
 * @copyright © 2022 Daan van den Bergh. All Rights Reserved.
 */
class OMGF_DB_Migrate {

	/** @var string */
	private $current_version = '';

	/**
	 * DB Migration constructor.
	 */
	public function __construct() {
		/**
		 * Can be used to block migration scripts that shouldn't be run on a fresh install.
		 */
		$this->current_version = get_option( OMGF_Admin_Settings::OMGF_CURRENT_DB_VERSION );

		if ( $this->should_run_migration( '5.3.3' ) ) {
			new OMGF_DB_Migrate_V533();
		}

		if ( $this->should_run_migration( '5.3.4' ) ) {
			new OMGF_DB_Migrate_V534();
		}

		if ( $this->should_run_migration( '5.5.7' ) ) {
			new OMGF_DB_Migrate_V557();
		}
	}

	/**
	 * Checks whether migration script has been run.
	 * 
	 * @param mixed $version 
	 * @return bool 
	 */
	private function should_run_migration( $version ) {
		return version_compare( $this->current_version, $version ) < 0;
	}
}
