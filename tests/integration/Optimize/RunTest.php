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
		add_filter( 'omgf_setting_auto_subsets', [ $this, 'returnOn' ] );
		add_filter( 'omgf_setting_subsets', [ $this, 'returnExoticSubsets' ] );
		add_filter( 'omgf_available_filtered_subsets', [ $this, 'returnLatinExt' ] );

		new Run();

		remove_filter( 'omgf_setting_subsets', [ $this, 'returnExoticSubsets' ] );
		remove_filter( 'omgf_available_filtered_subsets', [ $this, 'returnLatinExt' ] );

		$this->assertCount( 2, OMGF::get_option( Settings::OMGF_ADV_SETTING_SUBSETS ) );

		add_filter( 'omgf_setting_subsets', [ $this, 'returnExoticSubsetsOnly' ] );
		add_filter( 'omgf_available_filtered_subsets', '__return_empty_array' );

		new Run();

		remove_filter( 'omgf_setting_subsets', [ $this, 'returnExoticSubsetsOnly' ] );
		remove_filter( 'omgf_available_filtered_subsets', '__return_empty_array' );
		remove_filter( 'omgf_setting_auto_subsets', [ $this, 'returnOn' ] );

		$this->assertCount( 1, OMGF::get_option( Settings::OMGF_ADV_SETTING_SUBSETS ) );
	}

	public function returnLatinExt() {
		return [ 'latin', 'latin-ext' ];
	}

	public function returnExoticSubsets() {
		return [ 'latin', 'latin-ext', 'devanagari', 'gurmukhi' ];
	}

	public function returnExoticSubsetsOnly() {
		return [ 'devanagari', 'gurmukhi' ];
	}
}
