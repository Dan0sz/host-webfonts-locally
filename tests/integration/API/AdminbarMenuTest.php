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
	public function setUp(): void {
		parent::setUp();
		// Ensure we start with clean options
		OMGF::delete_option( Settings::OMGF_DB_GOOGLE_FONTS_CHECKER_RESULTS );
		OMGF::delete_option( Settings::OMGF_DB_PERF_CHECK );
	}

	/**
	 * @return void
	 * @throws \ReflectionException
	 */
	public function testDecodeJsonArrayEdgeCases() {
		$api = new AdminbarMenu();

		// Use reflection to test the private method decode_json_array
		$reflection = new \ReflectionClass( $api );
		$method     = $reflection->getMethod( 'decode_json_array' );
		$method->setAccessible( true );

		// Case: input is not a string or is empty (covers line 278)
		$this->assertEquals( [], $method->invoke( $api, null ) );
		$this->assertEquals( [], $method->invoke( $api, '' ) );
		$this->assertEquals( [], $method->invoke( $api, 123 ) );

		// Case: input is valid array
		$this->assertEquals( [ 'test' ], $method->invoke( $api, [ 'test' ] ) );

		// Case: input is valid JSON string
		$this->assertEquals( [ 'test' => 1 ], $method->invoke( $api, '{"test":1}' ) );

		// Case: input is an invalid JSON string
		$this->assertEquals( [], $method->invoke( $api, '{invalid' ) );
	}

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

			$results = OMGF::get_option( Settings::OMGF_DB_GOOGLE_FONTS_CHECKER_RESULTS );

			$this->assertArrayHasKey( 'https://fonts.googleapis.com/css?family=Roboto:400,700', $results );
		} finally {
			$request->set_param( 'urls', [] );
		}

		// We send over no URLs and no URLs should be saved.
		try {
			add_filter( 'omgf_is_running_optimize', '__return_true' );

			$api = new AdminbarMenu();

			$api->get_admin_bar_status( $request );

			$results = OMGF::get_option( Settings::OMGF_DB_GOOGLE_FONTS_CHECKER_RESULTS );

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

			$results = OMGF::get_option( Settings::OMGF_DB_GOOGLE_FONTS_CHECKER_RESULTS );

			$this->assertCount( 5, $results );
		} finally {
			remove_filter( 'omgf_is_running_optimize', '__return_true' );

			OMGF::delete_option( Settings::OMGF_DB_GOOGLE_FONTS_CHECKER_RESULTS );
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

		$this->assertEquals( 'info', $response['status'] );
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
		try {
			$original_home    = get_option( 'home' );
			$original_siteurl = get_option( 'siteurl' );

			update_option( 'home', 'https://example.com' );
			update_option( 'siteurl', 'https://example.com' );

			$request = new \WP_REST_Request( 'POST', '/omgf/v1/adminbar-menu/status' );
			$request->set_param( 'path', '/performance-test' );
			$unused_fonts_analysis = [
				'count'  => 10,
				'impact' => 'High',
			];
			$preload_analysis      = [
				'potential_delay_ms' => 80,
				'impact'             => 'Medium',
			];
			$request->set_param( 'unused_fonts_analysis', json_encode( $unused_fonts_analysis ) );
			$request->set_param( 'preload_analysis', json_encode( $preload_analysis ) );

			$response = $api->get_admin_bar_status( $request );
			$metrics  = OMGF::get_option( Settings::OMGF_DB_PERF_CHECK );

			$this->assertEquals( 'info', $response['status'] );
			$this->assertEquals( '/performance-test', $metrics['highest_unused_path'] );
			$this->assertEquals( 'High', $metrics['highest_unused_impact'] );
			$this->assertEquals( 80, $metrics['highest_delay_ms'] );
			$this->assertEquals( '/performance-test', $metrics['highest_delay_path'] );
			$this->assertEquals( 'Medium', $metrics['highest_delay_impact'] );
			$this->assertNotNull( $metrics['highest_unused_timestamp'] );
			$this->assertNotNull( $metrics['highest_delay_timestamp'] );

			// Case 2: Send lower performance data, metrics should NOT be updated.
			$request_lower = new \WP_REST_Request( 'POST', '/omgf/v1/adminbar-menu/status' );
			$request_lower->set_param( 'path', '/performance-test-lower' );
			$unused_fonts_analysis_lower = [
				'count'  => 2,
				'impact' => 'Low',
			];
			$preload_analysis_lower      = [
				'potential_delay_ms' => 15,
				'impact'             => 'Low',
			];
			$request_lower->set_param( 'unused_fonts_analysis', json_encode( $unused_fonts_analysis_lower ) );
			$request_lower->set_param( 'preload_analysis', json_encode( $preload_analysis_lower ) );

			$api->get_admin_bar_status( $request_lower );

			$metrics = OMGF::get_option( Settings::OMGF_DB_PERF_CHECK );

			$this->assertEquals( '/performance-test', $metrics['highest_unused_path'] );
			$this->assertEquals( 80, $metrics['highest_delay_ms'] );

			// Case 3: Send higher performance data, metrics SHOULD be updated.
			$request_higher = new \WP_REST_Request( 'POST', '/omgf/v1/adminbar-menu/status' );
			$request_higher->set_param( 'path', '/performance-test-higher' );
			$unused_fonts_analysis_higher = [
				'count'  => 15,
				'impact' => 'High',
			];
			$preload_analysis_higher      = [
				'potential_delay_ms' => 150,
				'impact'             => 'High',
			];
			$request_higher->set_param( 'unused_fonts_analysis', json_encode( $unused_fonts_analysis_higher ) );
			$request_higher->set_param( 'preload_analysis', json_encode( $preload_analysis_higher ) );

			$api->get_admin_bar_status( $request_higher );

			$metrics = OMGF::get_option( Settings::OMGF_DB_PERF_CHECK );

			$this->assertEquals( '/performance-test-higher', $metrics['highest_unused_path'] );
			$this->assertEquals( 150, $metrics['highest_delay_ms'] );
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
			try {
				OMGF::delete_option( Settings::OMGF_DB_GOOGLE_FONTS_CHECKER_RESULTS ); // Clear results to avoid 'alert'
				// To trigger 'notice' instead of 'info' (which is triggered by multilang plugin), we need has_warnings() to be true.
				// has_warnings() checks if any of the warning options are not empty.
				OMGF::update_option( Settings::OMGF_DB_FOUND_IFRAMES, [ 'https://example.com' ] );

				$request_notice = new \WP_REST_Request( 'POST', '/omgf/v1/adminbar-menu/status' );
				$request_notice->set_param( 'path', '/notice-test' );
				$request_notice->set_param( 'urls', [] );
				$request_notice->set_param( 'unused_fonts_analysis', json_encode( $unused_fonts_analysis_higher ) );

				$response_notice = $api->get_admin_bar_status( $request_notice );
			} finally {
				OMGF::delete_option( Settings::OMGF_DB_FOUND_IFRAMES );
			}

			$this->assertEquals( 'notice', $response_notice['status'] );

			// Case 6: Empty values in analysis shouldn't overwrite existing metrics.
			try {
				$request_empty = new \WP_REST_Request( 'POST', '/omgf/v1/adminbar-menu/status' );
				$request_empty->set_param( 'path', '/empty-test' );
				$request_empty->set_param( 'unused_fonts_analysis', json_encode( [ 'count' => 0 ] ) );
				$request_empty->set_param( 'preload_analysis', json_encode( [ 'potential_delay_ms' => 0 ] ) );

				$api->get_admin_bar_status( $request_empty );

				$metrics = OMGF::get_option( Settings::OMGF_DB_PERF_CHECK );

				$this->assertEquals( 150, $metrics['highest_delay_ms'] );
			} finally {
				OMGF::delete_option( Settings::OMGF_DB_PERF_CHECK );
				OMGF::delete_option( Settings::OMGF_DB_GOOGLE_FONTS_CHECKER_RESULTS );
			}
		} finally {
			update_option( 'home', $original_home );
			update_option( 'siteurl', $original_siteurl );
		}
	}

	/**
	 * @return void
	 * @throws \ReflectionException
	 */
	public function testPerformanceMetricsCLS() {
		$api = new AdminbarMenu();

		// Case 1: Initial CLS metric update.
		try {
			$request = new \WP_REST_Request( 'POST', '/omgf/v1/adminbar-menu/status' );
			$request->set_param( 'path', '/cls-test' );
			$cls_analysis = [
				'cls'    => 0.1234,
				'impact' => 'Medium',
			];
			$request->set_param( 'cls_analysis', json_encode( $cls_analysis ) );

			$api->get_admin_bar_status( $request );
			$metrics = OMGF::get_option( Settings::OMGF_DB_PERF_CHECK );

			$this->assertEquals( 0.123, $metrics['highest_cls'] );
			$this->assertEquals( '/cls-test', $metrics['highest_cls_path'] );
			$this->assertEquals( 'Medium', $metrics['highest_cls_impact'] );
			$this->assertNotNull( $metrics['highest_cls_timestamp'] );

			// Case 2: Update with higher CLS value (should update).
			$request_higher = new \WP_REST_Request( 'POST', '/omgf/v1/adminbar-menu/status' );
			$request_higher->set_param( 'path', '/cls-test-higher' );
			$cls_analysis_higher = [
				'cls'    => 0.2345,
				'impact' => 'High',
			];
			$request_higher->set_param( 'cls_analysis', json_encode( $cls_analysis_higher ) );

			$api->get_admin_bar_status( $request_higher );
			$metrics = OMGF::get_option( Settings::OMGF_DB_PERF_CHECK );

			$this->assertEquals( 0.235, $metrics['highest_cls'] );
			$this->assertEquals( '/cls-test-higher', $metrics['highest_cls_path'] );
			$this->assertEquals( 'High', $metrics['highest_cls_impact'] );

			// Case 3: Attempt update with lower CLS value (should NOT update).
			$request_lower = new \WP_REST_Request( 'POST', '/omgf/v1/adminbar-menu/status' );
			$request_lower->set_param( 'path', '/cls-test-lower' );
			$cls_analysis_lower = [
				'cls'    => 0.05,
				'impact' => 'Low',
			];
			$request_lower->set_param( 'cls_analysis', json_encode( $cls_analysis_lower ) );

			$api->get_admin_bar_status( $request_lower );
			$metrics = OMGF::get_option( Settings::OMGF_DB_PERF_CHECK );

			$this->assertEquals( 0.235, $metrics['highest_cls'] );
			$this->assertEquals( '/cls-test-higher', $metrics['highest_cls_path'] );

			// Case 4: Exercise CLS boundary around 0.01 (e.g. cls = 0.0104).
			$request_boundary = new \WP_REST_Request( 'POST', '/omgf/v1/adminbar-menu/status' );
			$request_boundary->set_param( 'path', '/cls-test-boundary' );
			// 0.0104 should be rounded to 0.01 and thus pass the >= 0.01 threshold.
			$cls_analysis_boundary = [
				'cls'    => 0.0104,
				'impact' => 'Low',
			];
			$request_boundary->set_param( 'cls_analysis', json_encode( $cls_analysis_boundary ) );

			$api->get_admin_bar_status( $request_boundary );
			$metrics = OMGF::get_option( Settings::OMGF_DB_PERF_CHECK );

			// Since 0.0104 < 0.235, it should NOT update highest_cls, but we've exercised the API logic with this value.
			$this->assertEquals( 0.235, $metrics['highest_cls'] );
			$this->assertEquals( '/cls-test-higher', $metrics['highest_cls_path'] );
			$this->assertEquals( 'High', $metrics['highest_cls_impact'] );
		} finally {
			OMGF::delete_option( Settings::OMGF_DB_PERF_CHECK );
		}
	}

	/**
	 * @return void
	 * @throws \ReflectionException
	 */
	public function testUpdateGoogleFontsCheckerResultsEdgeCases() {
		// Case 1: urls is a JSON string (covers lines 195-196)
		try {
			$api = new AdminbarMenu();

			// Use reflection to test private method update_google_fonts_checker_results
			$reflection = new \ReflectionClass( $api );
			$method     = $reflection->getMethod( 'update_google_fonts_checker_results' );
			$method->setAccessible( true );

			$urls = [ 'https://fonts.googleapis.com/css?family=Open+Sans' ];
			$post = [
				'path' => '/edge-case-test',
				'urls' => json_encode( $urls ),
			];
			$method->invoke( $api, $post );

			$results = OMGF::get_option( Settings::OMGF_DB_GOOGLE_FONTS_CHECKER_RESULTS );
		} finally {
			OMGF::delete_option( Settings::OMGF_DB_GOOGLE_FONTS_CHECKER_RESULTS );
		}

		$this->assertArrayHasKey( $urls[0], $results );

		// Case 2: urls is not a string or array (covers line 200)
		try {
			$post = [
				'path' => '/edge-case-test',
				'urls' => 123, // Invalid type
			];
			$method->invoke( $api, $post );

			$results = OMGF::get_option( Settings::OMGF_DB_GOOGLE_FONTS_CHECKER_RESULTS );
		} finally {
			OMGF::delete_option( Settings::OMGF_DB_GOOGLE_FONTS_CHECKER_RESULTS );
		}

		$this->assertEmpty( $results );
	}

	/**
	 * @return void
	 * @throws \ReflectionException
	 */
	public function testUpdateGoogleFontsCheckerResultsParamsEdgeCases() {
		$api = new AdminbarMenu();

		// Use reflection to test private method update_google_fonts_checker_results
		$reflection = new \ReflectionClass( $api );
		$method     = $reflection->getMethod( 'update_google_fonts_checker_results' );
		$method->setAccessible( true );

		// Case: params is a JSON string (covers lines 192-193)
		try {
			$params = [ 'test' => 1 ];
			$post   = [
				'path'   => '/params-json-test',
				'params' => json_encode( $params ),
				'urls'   => [ 'https://fonts.googleapis.com/css?family=Open+Sans' ],
			];

			$method->invoke( $api, $post );

			$results = OMGF::get_option( Settings::OMGF_DB_GOOGLE_FONTS_CHECKER_RESULTS );
			$this->assertArrayHasKey( 'https://fonts.googleapis.com/css?family=Open+Sans', $results );
		} finally {
			OMGF::delete_option( Settings::OMGF_DB_GOOGLE_FONTS_CHECKER_RESULTS );
		}

		// Case: params is neither string nor array (covers line 197)
		try {
			$post = [
				'path'   => '/params-invalid-test',
				'params' => 123, // Invalid type
				'urls'   => [ 'https://fonts.googleapis.com/css?family=Roboto' ],
			];

			$method->invoke( $api, $post );

			$results = OMGF::get_option( Settings::OMGF_DB_GOOGLE_FONTS_CHECKER_RESULTS );
			$this->assertArrayHasKey( 'https://fonts.googleapis.com/css?family=Roboto', $results );
		} finally {
			OMGF::delete_option( Settings::OMGF_DB_GOOGLE_FONTS_CHECKER_RESULTS );
		}
	}
}
