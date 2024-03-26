<?php
/**
 * @package OMGF Integration Tests - Run
 */

namespace OMGF\Tests\Integration\Optimize;

use OMGF\Admin\Notice;
use OMGF\Admin\Settings;
use OMGF\Helper;
use OMGF\Optimize\Run;
use OMGF\Tests\TestCase;

class RunTest extends TestCase {
	/**
	 * @see Run::run()
	 * @return void
	 */
	public function testRun() {
		new Run();

		$transient = get_transient( Notice::OMGF_ADMIN_NOTICE_TRANSIENT );

		$this->assertNotEmpty( $transient[ 'all' ][ 'warning' ][ 'omgf-cache-notice' ] );
	}

	/**
	 * @see Run::optimization_succeeded()
	 * @return void
	 */
	public function testAutoConfigSubsets() {
		Helper::update_option( Settings::OMGF_ADV_SETTING_SUBSETS, [ 'latin', 'latin-ext', 'devanagari', 'gurmukhi' ] );
		Helper::update_option( Settings::OMGF_OPTIMIZE_SETTING_AUTO_SUBSETS, 'on' );

		new Run();

		$this->assertCount( 2, Helper::get_option( Settings::OMGF_ADV_SETTING_SUBSETS ) );

		Helper::update_option( Settings::OMGF_ADV_SETTING_SUBSETS, [ 'devanagari', 'gurmukhi' ] );
		add_filter( 'omgf_available_filtered_subsets', '__return_empty_array' );

		new Run();

		remove_filter( 'omgf_available_filtered_subsets', '__return_empty_array' );

		$this->assertCount( 1, Helper::get_option( Settings::OMGF_ADV_SETTING_SUBSETS ) );
	}
}
