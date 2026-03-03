<?php
/**
 * @package OMGF integration tests - Admin
 */

namespace OMGF\Tests\Integration\API;

use OMGF\Admin\Settings;
use OMGF\API\AdminbarMenu;
use OMGF\Helper as OMGF;
use OMGF\Tests\TestCase;

class AdminbarMenuTest extends TestCase {
	/**
	 * @see AdminbarMenu::get_admin_bar_status()
	 * @return void
	 */
	public function testGetAdminBarStatus() {
		// We send over 1 URL, and it should be saved.
		$request = new \WP_REST_Request( 'POST', '/omgf/v1/adminbar-menu/status' );

		try {
			$request->set_param( 'path', '/test' );
			$request->set_param( 'urls', [ 'https://fonts.googleapis.com/css?family=Roboto:400,700' ] );
			$api = new AdminbarMenu();

			$api->get_admin_bar_status( $request );

			$results = OMGF::get_option( Settings::OMGF_GOOGLE_FONTS_CHECKER_RESULTS );

			$this->assertArrayHasKey( 'https://fonts.googleapis.com/css?family=Roboto:400,700', $results );
		} finally {
			$request->set_param( 'urls', [] );
		}

		// We send over no URLs and no URLs should be saved.
		try {
			add_filter( 'omgf_is_running_optimize', '__return_true' );

			$api = new AdminbarMenu();

			$api->get_admin_bar_status( $request );

			$results = OMGF::get_option( Settings::OMGF_GOOGLE_FONTS_CHECKER_RESULTS );

			$this->assertArrayNotHasKey( 'https://fonts.googleapis.com/css?family=Roboto:400,700', $results );
		} finally {
			remove_filter( 'omgf_is_running_optimize', '__return_true' );
		}

		// We send over 6 URLs, but only 5 should be saved.
		try {
			add_filter( 'omgf_is_running_optimize', '__return_true' );

			$request->set_param(
				'urls',
				[
					'https://fonts.googleapis.com/css?family=Test',
					'https://fonts.googleapis.com/css?family=Test2',
					'https://fonts.googleapis.com/css?family=Test3',
					'https://fonts.googleapis.com/css?family=Test4',
					'https://fonts.googleapis.com/css?family=Test5',
					'https://fonts.googleapis.com/css?family=Test6',
				]
			);

			$api = new AdminbarMenu();

			$api->get_admin_bar_status( $request );

			$results = OMGF::get_option( Settings::OMGF_GOOGLE_FONTS_CHECKER_RESULTS );

			$this->assertCount( 5, $results );
		} finally {
			remove_filter( 'omgf_is_running_optimize', '__return_true' );

			OMGF::delete_option( Settings::OMGF_GOOGLE_FONTS_CHECKER_RESULTS );
		}
	}

	/**
	 * @return void
	 */
	public function testMultilingualPluginDetection() {
		$original_home    = get_option( 'home' );
		$original_siteurl = get_option( 'siteurl' );
		try {
			add_filter( 'omgf_has_multilang_plugin', '__return_true' );
			update_option( 'home', preg_replace( '#^http://#', 'https://', (string) $original_home ) );
			update_option( 'siteurl', preg_replace( '#^http://#', 'https://', (string) $original_siteurl ) );

			$api     = new AdminbarMenu();
			$request = new \WP_REST_Request( 'POST', '/omgf/v1/adminbar-menu/status' );
			$request->set_param( 'path', '/' );
			$request->set_param( 'urls', [] );

			$response = $api->get_admin_bar_status( $request );
		} finally {
			update_option( 'home', $original_home );
			update_option( 'siteurl', $original_siteurl );
			remove_filter( 'omgf_has_multilang_plugin', '__return_true' );
		}

		$this->assertEquals( 'notice', $response['status'] );
	}

	/**
	 * @return void
	 */
	public function testNoticeStatusWhenSSLWarningExists() {
		$api     = new AdminbarMenu();
		$request = new \WP_REST_Request( 'POST', '/omgf/v1/adminbar-menu/status' );
		$request->set_param( 'path', '/' );
		$request->set_param( 'urls', [] );

		$response = $api->get_admin_bar_status( $request );

		// Test env doesn't have SSL configured, which triggers the 'no_ssl' warning.
		$this->assertEquals( 'notice', $response['status'] );
	}

	/**
	 * @see AdminbarMenu::get_admin_bar_status()
	 * @return void
	 */
	public function testPerformanceMetrics() {
		$api = new AdminbarMenu();

		// Case 1: Send performance data, status should become 'info' (assuming no other notices/alerts)
		// We need to bypass the SSL warning which defaults to 'notice' in this test environment.
		$original_home    = get_option( 'home' );
		$original_siteurl = get_option( 'siteurl' );
		update_option( 'home', 'https://example.com' );
		update_option( 'siteurl', 'https://example.com' );

		try {
			$request = new \WP_REST_Request( 'POST', '/omgf/v1/adminbar-menu/status' );
			$request->set_param( 'path', '/performance-test' );
			$unused_fonts_analysis = [
				'total_kb' => 100,
				'impact'   => 'High',
			];
			$preload_analysis      = [
				'potential_delay_ms' => 500,
				'impact'             => 'Medium',
			];
			$request->set_param( 'unused_fonts_analysis', json_encode( $unused_fonts_analysis ) );
			$request->set_param( 'preload_analysis', json_encode( $preload_analysis ) );

			$response = $api->get_admin_bar_status( $request );

			$this->assertEquals( 'info', $response['status'] );

			$metrics = OMGF::get_option( Settings::OMGF_PERF_CHECK );
			$this->assertEquals( 100, $metrics['highest_unused_kb'] );
			$this->assertEquals( '/performance-test', $metrics['highest_unused_path'] );
			$this->assertEquals( 'High', $metrics['highest_unused_impact'] );
			$this->assertEquals( 500, $metrics['highest_delay_ms'] );
			$this->assertEquals( '/performance-test', $metrics['highest_delay_path'] );
			$this->assertEquals( 'Medium', $metrics['highest_delay_impact'] );
			$this->assertNotNull( $metrics['highest_unused_timestamp'] );
			$this->assertNotNull( $metrics['highest_delay_timestamp'] );

			// Case 2: Send lower performance data, metrics should NOT be updated.
			$request_lower = new \WP_REST_Request( 'POST', '/omgf/v1/adminbar-menu/status' );
			$request_lower->set_param( 'path', '/performance-test-lower' );
			$unused_fonts_analysis_lower = [
				'total_kb' => 50,
				'impact'   => 'Low',
			];
			$preload_analysis_lower      = [
				'potential_delay_ms' => 200,
				'impact'             => 'Low',
			];
			$request_lower->set_param( 'unused_fonts_analysis', json_encode( $unused_fonts_analysis_lower ) );
			$request_lower->set_param( 'preload_analysis', json_encode( $preload_analysis_lower ) );

			$api->get_admin_bar_status( $request_lower );

			$metrics = OMGF::get_option( Settings::OMGF_PERF_CHECK );
			$this->assertEquals( 100, $metrics['highest_unused_kb'] );
			$this->assertEquals( '/performance-test', $metrics['highest_unused_path'] );

			// Case 3: Send higher performance data, metrics SHOULD be updated.
			$request_higher = new \WP_REST_Request( 'POST', '/omgf/v1/adminbar-menu/status' );
			$request_higher->set_param( 'path', '/performance-test-higher' );
			$unused_fonts_analysis_higher = [
				'total_kb' => 200,
				'impact'   => 'High',
			];
			$preload_analysis_higher      = [
				'potential_delay_ms' => 1000,
				'impact'             => 'High',
			];
			$request_higher->set_param( 'unused_fonts_analysis', json_encode( $unused_fonts_analysis_higher ) );
			$request_higher->set_param( 'preload_analysis', json_encode( $preload_analysis_higher ) );

			$api->get_admin_bar_status( $request_higher );

			$metrics = OMGF::get_option( Settings::OMGF_PERF_CHECK );
			$this->assertEquals( 200, $metrics['highest_unused_kb'] );
			$this->assertEquals( '/performance-test-higher', $metrics['highest_unused_path'] );
			$this->assertEquals( 1000, $metrics['highest_delay_ms'] );
			$this->assertEquals( '/performance-test-higher', $metrics['highest_delay_path'] );

			// Case 4: Test precedence of alert/notice over info.
			// Trigger an alert by sending a URL.
			$request_alert = new \WP_REST_Request( 'POST', '/omgf/v1/adminbar-menu/status' );
			$request_alert->set_param( 'path', '/alert-test' );
			$request_alert->set_param( 'urls', [ 'https://fonts.googleapis.com/css?family=Roboto' ] );
			$request_alert->set_param( 'unused_fonts_analysis', json_encode( $unused_fonts_analysis_higher ) );

			$response_alert = $api->get_admin_bar_status( $request_alert );
			$this->assertEquals( 'alert', $response_alert['status'] );

			// Case 5: Test precedence of notice over info.
			// Bypassing SSL for this part too, and triggering notice via filter.
			add_filter( 'omgf_has_multilang_plugin', '__return_true' );
			OMGF::delete_option( Settings::OMGF_GOOGLE_FONTS_CHECKER_RESULTS ); // Clear results to avoid 'alert'
			$request_notice = new \WP_REST_Request( 'POST', '/omgf/v1/adminbar-menu/status' );
			$request_notice->set_param( 'path', '/notice-test' );
			$request_notice->set_param( 'urls', [] );
			$request_notice->set_param( 'unused_fonts_analysis', json_encode( $unused_fonts_analysis_higher ) );

			$response_notice = $api->get_admin_bar_status( $request_notice );
			$this->assertEquals( 'notice', $response_notice['status'] );
			remove_filter( 'omgf_has_multilang_plugin', '__return_true' );

			// Case 6: Empty values in analysis shouldn't overwrite existing metrics.
			$request_empty = new \WP_REST_Request( 'POST', '/omgf/v1/adminbar-menu/status' );
			$request_empty->set_param( 'path', '/empty-test' );
			$request_empty->set_param( 'unused_fonts_analysis', json_encode( [ 'total_kb' => 0 ] ) );
			$request_empty->set_param( 'preload_analysis', json_encode( [ 'potential_delay_ms' => 0 ] ) );

			$api->get_admin_bar_status( $request_empty );

			$metrics = OMGF::get_option( Settings::OMGF_PERF_CHECK );
			$this->assertEquals( 200, $metrics['highest_unused_kb'] );
			$this->assertEquals( 1000, $metrics['highest_delay_ms'] );

		} finally {
			update_option( 'home', $original_home );
			update_option( 'siteurl', $original_siteurl );
			OMGF::delete_option( Settings::OMGF_PERF_CHECK );
			OMGF::delete_option( Settings::OMGF_GOOGLE_FONTS_CHECKER_RESULTS );
		}
	}
}
