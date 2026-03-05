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
		$user_id = $this->factory->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $user_id );

		$api      = new DashboardAPI();
		$response = $api->dismiss_notice();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertTrue( (bool) get_transient( Settings::OMGF_DISMISS_NOTICE_TRANSIENT . $user_id ) );

		// Test user specific
		$other_user_id = $this->factory->user->create( [ 'role' => 'administrator' ] );
		$this->assertFalse( (bool) get_transient( Settings::OMGF_DISMISS_NOTICE_TRANSIENT . $other_user_id ) );

		// Test Dashboard visibility
		OMGF::update_option( Settings::OMGF_PERF_CHECK, [ 'highest_unused_count' => 1, 'highest_unused_path' => '/' ] );

		ob_start();
		Dashboard::render_notices();
		$output = ob_get_clean();

		$this->assertStringNotContainsString( 'id="omgf-performance-checker-notice"', $output );

		// Switch to other user
		wp_set_current_user( $other_user_id );
		ob_start();
		Dashboard::render_notices();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'id="omgf-performance-checker-notice"', $output );

		// Cleanup
		delete_transient( Settings::OMGF_DISMISS_NOTICE_TRANSIENT . $user_id );
		OMGF::delete_option( Settings::OMGF_PERF_CHECK );
	}
}
