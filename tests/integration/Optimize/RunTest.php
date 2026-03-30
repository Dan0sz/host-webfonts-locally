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
		$before = did_action( 'omgf_optimize_succeeded' );

		new Run();

		$this->assertSame( $before + 1, did_action( 'omgf_optimize_succeeded' ) );

		global $wp_settings_errors;

		$wp_settings_errors = [];

		try {
			$filter_home_url = function () {
				return 'http://example.invalid';
			};
			add_filter( 'omgf_filter_optimize_url', $filter_home_url );

			new Run();

			global $wp_settings_errors;

			$this->assertNotEmpty( $wp_settings_errors );
			$this->assertArrayHasKey( 'code', $wp_settings_errors[0] );
			$this->assertEquals( 'omgf_frontend_fetch_failed', $wp_settings_errors[0]['code'] );
		} finally {
			remove_filter( 'omgf_filter_optimize_url', $filter_home_url );
			delete_transient( Notice::OMGF_ADMIN_NOTICE_TRANSIENT );
		}
	}
}
