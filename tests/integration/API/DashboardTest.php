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
		$user_id       = 0;
		$other_user_id = 0;

		try {
			$user_id = wp_insert_user(
				[
					'user_login' => 'test_user',
					'user_pass'  => 'test_pass',
					'role'       => 'administrator',
				]
			);

			if ( is_wp_error( $user_id ) ) {
				$this->fail( $user_id->get_error_message() );
			}

			wp_set_current_user( $user_id );

			$api      = new DashboardAPI();
			$response = $api->dismiss_notice();

			$this->assertEquals( 200, $response->get_status() );
			$this->assertTrue( (bool) get_transient( Settings::OMGF_DISMISS_NOTICE_TRANSIENT . $user_id ) );

			$other_user_id = wp_insert_user(
				[
					'user_login' => 'other_test_user',
					'user_pass'  => 'other_test_pass',
					'role'       => 'administrator',
				]
			);

			if ( is_wp_error( $other_user_id ) ) {
				$this->fail( $other_user_id->get_error_message() );
			}

			$this->assertFalse( (bool) get_transient( Settings::OMGF_DISMISS_NOTICE_TRANSIENT . $other_user_id ) );

			// Test Dashboard visibility
			OMGF::update_option( Settings::OMGF_PERF_CHECK, [ 'highest_unused_count' => 1, 'highest_unused_path' => '/' ] );

			ob_start();
			Dashboard::render_notices();
			$output = ob_get_clean();

			$this->assertStringNotContainsString( 'id="omgf-performance-checker-notice"', $output );

			// Switch to another user
			wp_set_current_user( $other_user_id );
			ob_start();
			Dashboard::render_notices();
			$output = ob_get_clean();

			$this->assertStringContainsString( 'id="omgf-performance-checker-notice"', $output );
		} finally {
			if ( is_int( $user_id ) && $user_id > 0 ) {
				delete_transient( Settings::OMGF_DISMISS_NOTICE_TRANSIENT . $user_id );
				wp_delete_user( $user_id );
			}

			if ( is_int( $other_user_id ) && $other_user_id > 0 ) {
				wp_delete_user( $other_user_id );
			}

			OMGF::delete_option( Settings::OMGF_PERF_CHECK );
			wp_set_current_user( 0 );
		}
	}
}
