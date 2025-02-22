<?php
/**
 * @package OMGF integration tests - Admin
 */

namespace OMGF\Tests\Integration;

use OMGF\Admin;
use OMGF\Admin\Settings;
use OMGF\Helper as OMGF;
use OMGF\Tests\TestCase;

class AdminTest extends TestCase {
	/**
	 * @see Admin::do_optimize_settings()
	 * @return void
	 */
	public function testDoOptimizeSettings() {
		new Admin();

		$this->expectOutputContains( 'Dashboard' );

		do_action( 'omgf_optimize_settings_content' );
	}

	/**
	 * @see Admin::do_advanced_settings()
	 * @return void
	 */
	public function testDoAdvancedSettings() {
		new Admin();

		$this->expectOutputContains( 'Remove Settings/Files At Uninstall' );

		do_action( 'omgf_advanced_settings_content' );
	}

	/**
	 * @see Admin::do_help()
	 * @return void
	 */
	public function testDoHelp() {
		new Admin();

		$this->expectOutputContains( 'Thank you for using' );

		do_action( 'omgf_help_content' );
	}

	/**
	 * @see Admin::maybe_show_stale_cache_notice()
	 * @return void
	 */
	public function testShowStaleCacheNotice() {
		global $wp_settings_errors;

		/**
		 * Make sure it's empty.
		 */
		$wp_settings_errors = [];

		$class = new Admin();

		$_GET[ 'page' ] = Settings::OMGF_ADMIN_PAGE;
		$_GET[ 'tab' ]  = 'test';

		$class->maybe_show_stale_cache_notice( [ 'subsets' => [ 'latin-ext' ] ], [ 'subsets' => [ 'latin' ] ] );

		$this->assertTrue( OMGF::get_option( Settings::OMGF_CACHE_IS_STALE ) );
	}
}
