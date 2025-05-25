<?php
/**
 * @package OMGF Integration Tests - Run
 */

namespace OMGF\Tests\Integration\Optimize;

use OMGF\Admin\Notice;
use OMGF\Admin\Settings;
use OMGF\Helper as OMGF;
use OMGF\Optimize\Run;
use OMGF\Tests\TestCase;

class RunTest extends TestCase {
	/**
	 * @see Run::run()
	 * @return void
	 */
	public function testRun() {
		add_filter( 'omgf_setting_auto_subsets', '__return_empty_string' );

		new Run();

		remove_filter( 'omgf_setting_auto_subsets', '__return_empty_string' );

		$transient = get_transient( Notice::OMGF_ADMIN_NOTICE_TRANSIENT );

		$this->assertNotEmpty( $transient[ 'all' ][ 'warning' ][ 'omgf-cache-notice' ] );
	}

	/**
	 * @see Run::optimization_succeeded()
	 * @return void
	 */
	public function testAutoConfigSubsets() {
		try {
			add_filter( 'omgf_setting_auto_subsets', [ $this, 'returnOn' ] );
			add_filter( 'omgf_setting_subsets', [ $this, 'returnExoticSubsets' ] );
			add_filter( 'omgf_available_filtered_subsets', [ $this, 'returnFilteredSubsets' ] );

			new Run();
		} finally {
			remove_filter( 'omgf_setting_subsets', [ $this, 'returnExoticSubsets' ] );
			remove_filter( 'omgf_available_filtered_subsets', [ $this, 'returnFilteredSubsets' ] );
		}

		/**
		 * Should have filtered the exotic subsets and only contain latin and latin-ext.
		 */
		$this->assertCount( 2, OMGF::get_option( Settings::OMGF_ADV_SETTING_SUBSETS ) );

		OMGF::delete_option( Settings::OMGF_ADV_SETTING_SUBSETS );

		try {
			add_filter( 'omgf_setting_subsets', [ $this, 'returnExoticSubsetsOnly' ] );
			add_filter( 'omgf_available_filtered_subsets', '__return_empty_array' );

			new Run();
		} finally {
			remove_filter( 'omgf_setting_subsets', [ $this, 'returnExoticSubsetsOnly' ] );
			remove_filter( 'omgf_available_filtered_subsets', '__return_empty_array' );
			remove_filter( 'omgf_setting_auto_subsets', [ $this, 'returnOn' ] );
		}

		/**
		 * Should have detected that none of the subsets were available, so it fallback to Latin.
		 */
		$this->assertCount( 1, OMGF::get_option( Settings::OMGF_ADV_SETTING_SUBSETS ) );

		OMGF::delete_option( Settings::OMGF_ADV_SETTING_SUBSETS );
	}

	public function returnFilteredSubsets() {
		return [ 'handle' => [ 'latin', 'latin-ext' ] ];
	}

	public function returnExoticSubsets() {
		return [ 'latin', 'latin-ext', 'devanagari', 'gurmukhi' ];
	}

	public function returnExoticSubsetsOnly() {
		return [ 'devanagari', 'gurmukhi' ];
	}
}
