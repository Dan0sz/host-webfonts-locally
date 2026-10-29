<?php
/**
 * @package OMGF integration tests - Admin
 */

namespace OMGF\Tests\Integration\API;

use OMGF\Admin\Dashboard;
use OMGF\Admin\Settings;
use OMGF\API\Dashboard as DashboardAPI;
use OMGF\Download;
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
			OMGF::update_option( Settings::OMGF_DB_PERF_CHECK, [ 'highest_unused_count' => 1, 'highest_unused_path' => '/' ] );

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

			OMGF::delete_option( Settings::OMGF_DB_PERF_CHECK );
			wp_set_current_user( 0 );
		}
	}

	/**
	 * Add-ons can remove Performance Checker results and the multilingual item, e.g. when they don't apply.
	 *
	 * @since v6.3.15
	 * @see   Dashboard::render_notices()
	 * @return void
	 */
	public function testPerformanceCheckerFilters() {
		$current_user = get_current_user_id();
		$only_cls     = function ( $results ) {
			unset( $results['highest_unused_count'], $results['highest_unused_path'] );

			return $results;
		};
		$none         = function () {
			return [];
		};
		$render       = function () {
			ob_start();
			Dashboard::render_notices();

			return ob_get_clean();
		};

		try {
			wp_set_current_user( 0 );
			add_filter( 'omgf_has_multilang_plugin', '__return_true' );
			add_filter( 'omgf_setting_subsets', [ $this, 'return_subsets' ] );
			OMGF::update_option(
				Settings::OMGF_DB_PERF_CHECK,
				[ 'highest_unused_count' => 2, 'highest_unused_path' => '/', 'highest_cls' => 0.2, 'highest_cls_path' => '/' ]
			);

			$html = $render();

			$this->assertStringContainsString( 'font faces it doesn', $html );
			$this->assertStringContainsString( 'CLS score: 0.2', $html );
			$this->assertStringContainsString( 'loads only the right font subsets', $html );

			add_filter( 'omgf_performance_checker_results', $only_cls );
			add_filter( 'omgf_performance_checker_show_multilang', '__return_false' );

			$html = $render();

			$this->assertStringContainsString( 'id="omgf-performance-checker-notice"', $html );
			$this->assertStringNotContainsString( 'font faces it doesn', $html );
			$this->assertStringNotContainsString( 'loads only the right font subsets', $html );
			$this->assertStringContainsString( 'CLS score: 0.2', $html );

			// No results left, no notice.
			add_filter( 'omgf_performance_checker_results', $none, 11 );

			$this->assertStringNotContainsString( 'id="omgf-performance-checker-notice"', $render() );
		} finally {
			remove_filter( 'omgf_has_multilang_plugin', '__return_true' );
			remove_filter( 'omgf_setting_subsets', [ $this, 'return_subsets' ] );
			remove_filter( 'omgf_performance_checker_results', $only_cls );
			remove_filter( 'omgf_performance_checker_results', $none, 11 );
			remove_filter( 'omgf_performance_checker_show_multilang', '__return_false' );
			OMGF::delete_option( Settings::OMGF_DB_PERF_CHECK );
			wp_set_current_user( $current_user );
		}
	}

	/**
	 * @return array
	 */
	public function return_subsets() {
		return [ 'latin', 'latin-ext' ];
	}

	/**
	 * @see DashboardAPI::dismiss_download_failures()
	 * @see DashboardAPI::get_permission()
	 * @return void
	 */
	public function testDismissDownloadFailures() {
		$current_user = get_current_user_id();

		try {
			Download::add_failure( 'https://fonts.example/font.woff2', Download::FAILURE_EMPTY );

			$api = new DashboardAPI();

			// Visitors aren't allowed to use the API.
			wp_set_current_user( 0 );

			$this->assertFalse( $api->get_permission() );

			$response = $api->dismiss_download_failures();

			$this->assertEquals( 200, $response->get_status() );
			$this->assertSame( [], Download::get_failures() );
		} finally {
			wp_set_current_user( $current_user );
			delete_option( Settings::OMGF_DB_DOWNLOAD_FAILURES );
		}
	}

	/**
	 * @see Dashboard::render_download_failures()
	 * @return void
	 */
	public function testRenderDownloadFailures() {
		try {
			ob_start();
			Dashboard::render_download_failures();
			$this->assertSame( '', ob_get_clean() );

			Download::add_failure( 'https://fonts.example/large.woff2', Download::FAILURE_TOO_LARGE );
			Download::add_failure( 'https://fonts.example/<script>.woff2', Download::FAILURE_INVALID );

			ob_start();
			Dashboard::render_download_failures();
			$html = ob_get_clean();

			$this->assertStringContainsString( 'task-manager-notice alert', $html );
			$this->assertStringContainsString( "couldn't download 2 font files correctly", html_entity_decode( $html, ENT_QUOTES ) );
			$this->assertStringContainsString( 'https://fonts.example/large.woff2', $html );
			$this->assertStringContainsString( '&lt;script&gt;', $html );
			$this->assertStringNotContainsString( '<script>', $html );
			$this->assertStringContainsString( 'id="omgf-dismiss-download-failures"', $html );
		} finally {
			delete_option( Settings::OMGF_DB_DOWNLOAD_FAILURES );
		}
	}
}
