<?php
/**
 * @package OMGF integration tests - Admin
 */

namespace OMGF\Tests\Integration\API;

use OMGF\Admin\Dashboard;
use OMGF\Admin\Settings;
use OMGF\API\Dashboard as DashboardAPI;
use OMGF\Helper as OMGF;
use OMGF\Tests\TestCase;

class DashboardTest extends TestCase {
	/**
	 * @return void
	 */
	public function testDismissNotice() {
		try {
			$user_id = wp_insert_user(
				[
					'user_login' => 'test_user',
					'user_pass'  => 'test_pass',
					'role'       => 'administrator',
				]
			);
			wp_set_current_user( $user_id );

			$api      = new DashboardAPI();
			$response = $api->dismiss_notice();
		} finally {
			// Do nothing.
		}

		$this->assertEquals( 200, $response->get_status() );
		$this->assertTrue( (bool) get_transient( Settings::OMGF_DISMISS_NOTICE_TRANSIENT . $user_id ) );

		// Test user specific
		try {
			$other_user_id = wp_insert_user(
				[
					'user_login' => 'other_test_user',
					'user_pass'  => 'other_test_pass',
					'role'       => 'administrator',
				]
			);
		} finally {
			// Do nothing.
		}

		$this->assertFalse( (bool) get_transient( Settings::OMGF_DISMISS_NOTICE_TRANSIENT . $other_user_id ) );

		// Test Dashboard visibility
		try {
			OMGF::update_option( Settings::OMGF_PERF_CHECK, [ 'highest_unused_count' => 1, 'highest_unused_path' => '/' ] );

			ob_start();
			Dashboard::render_notices();
			$output = ob_get_clean();
		} finally {

		}

		$this->assertStringNotContainsString( 'id="omgf-performance-checker-notice"', $output );

		try {
			// Switch to other user
			wp_set_current_user( $other_user_id );
			ob_start();
			Dashboard::render_notices();
			$output = ob_get_clean();
		} finally {
			// Cleanup
			delete_transient( Settings::OMGF_DISMISS_NOTICE_TRANSIENT . $user_id );
			wp_delete_user( $user_id );
			wp_delete_user( $other_user_id );
			OMGF::delete_option( Settings::OMGF_PERF_CHECK );
		}

		$this->assertStringContainsString( 'id="omgf-performance-checker-notice"', $output );
	}
}
