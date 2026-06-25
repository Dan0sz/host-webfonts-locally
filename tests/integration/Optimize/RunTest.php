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
	public function return_https_home_url() {
		return 'https://example.org';
	}

	/**
	 * @see Run::get_front_html()
	 */
	public function testCookiesAreAddedWhenConditionsAreMet() {
		if ( ! defined( 'LOGGED_IN_COOKIE' ) ) {
			define( 'LOGGED_IN_COOKIE', 'wordpress_logged_in_abc' );
		}

		$_COOKIE[ LOGGED_IN_COOKIE ] = 'test_value';

		$captured_args = null;
		add_filter(
			'pre_http_request',
			function ( $pre, $args, $url ) use ( &$captured_args ) {
				$captured_args = $args;

				return [
					'response' => [ 'code' => 200 ],
					'body'     => '<html></html>',
				];
			},
			9,
			3
		);

		add_filter( 'home_url', [ $this, 'return_https_home_url' ] );

		new Run();

		$this->assertNotEmpty( $captured_args['cookies'], 'Cookies should not be empty' );
		$this->assertInstanceOf( \WP_Http_Cookie::class, $captured_args['cookies'][0] );
		$this->assertEquals( LOGGED_IN_COOKIE, $captured_args['cookies'][0]->name );
		$this->assertEquals( 'test_value', $captured_args['cookies'][0]->value );

		unset( $_COOKIE[ LOGGED_IN_COOKIE ] );
		remove_filter( 'home_url', [ $this, 'return_https_home_url' ] );
	}

	/**
	 * @see Run::get_front_html()
	 */
	public function testCookiesAreAddedWhenNotHttpsButRequirementDisabled() {
		if ( ! defined( 'LOGGED_IN_COOKIE' ) ) {
			define( 'LOGGED_IN_COOKIE', 'wordpress_logged_in_abc' );
		}

		$_COOKIE[ LOGGED_IN_COOKIE ] = 'test_value';

		$captured_args = null;
		add_filter(
			'pre_http_request',
			function ( $pre, $args, $url ) use ( &$captured_args ) {
				$captured_args = $args;

				return [
					'response' => [ 'code' => 200 ],
					'body'     => '<html></html>',
				];
			},
			9,
			3
		);

		add_filter( 'home_url', function () {
			return 'http://example.org';
		} );
		add_filter( 'omgf_optimize_run_require_https', '__return_false' );

		new Run();

		$this->assertNotEmpty( $captured_args['cookies'], 'Cookies should NOT be empty when HTTPS requirement is disabled' );

		unset( $_COOKIE[ LOGGED_IN_COOKIE ] );
		remove_all_filters( 'home_url' );
		remove_filter( 'omgf_optimize_run_require_https', '__return_false' );
	}

	/**
	 * @see Run::get_front_html()
	 */
	public function testCookiesAreNotAddedWhenHostDoesNotMatch() {
		if ( ! defined( 'LOGGED_IN_COOKIE' ) ) {
			define( 'LOGGED_IN_COOKIE', 'wordpress_logged_in_abc' );
		}

		$_COOKIE[ LOGGED_IN_COOKIE ] = 'test_value';

		$captured_args = null;
		add_filter(
			'pre_http_request',
			function ( $pre, $args, $url ) use ( &$captured_args ) {
				$captured_args = $args;

				return [
					'response' => [ 'code' => 200 ],
					'body'     => '<html></html>',
				];
			},
			9,
			3
		);

		add_filter( 'home_url', [ $this, 'return_https_home_url' ] );

		// We need to make Run::run() call get_front_html with a DIFFERENT URL than home_url.
		// Run::run() uses get_home_url().
		// If we want it to use a different URL, we need to filter get_home_url() inside Run::run() or...
		// Wait, Run::run() calls $this->get_front_html( get_home_url() ).
		// get_front_html($url) calls get_home_url() AGAIN to compare.

		// If we filter 'home_url' to return 'https://example.org'
		// then $url is 'https://example.org'.
		// And $home_url is 'https://example.org'.
		// They MATCH.

		// To make them NOT match, we need 'home_url' to return different things at different times,
		// or we need to filter something else.

		$home_url_count = 0;
		add_filter( 'home_url', function () use ( &$home_url_count ) {
			$home_url_count ++;
			if ( $home_url_count === 1 ) {
				return 'https://requested-domain.com';
			}

			return 'https://actual-home-domain.com';
		}, 20 );

		new Run();

		$this->assertEmpty( $captured_args['cookies'], 'Cookies should be empty when host does not match' );

		unset( $_COOKIE[ LOGGED_IN_COOKIE ] );
		remove_filter( 'home_url', [ $this, 'return_https_home_url' ] );
		remove_all_filters( 'home_url' );
	}

	/**
	 * @see Run::get_front_html()
	 */
	public function testCookiesAreNotAddedWhenNotHttps() {
		if ( ! defined( 'LOGGED_IN_COOKIE' ) ) {
			define( 'LOGGED_IN_COOKIE', 'wordpress_logged_in_abc' );
		}

		$_COOKIE[ LOGGED_IN_COOKIE ] = 'test_value';

		$captured_args = null;
		add_filter(
			'pre_http_request',
			function ( $pre, $args, $url ) use ( &$captured_args ) {
				$captured_args = $args;

				return [
					'response' => [ 'code' => 200 ],
					'body'     => '<html></html>',
				];
			},
			9,
			3
		);

		add_filter( 'home_url', function () {
			return 'http://example.org';
		} );

		new Run();

		$this->assertEmpty( $captured_args['cookies'], 'Cookies should be empty when not HTTPS' );

		unset( $_COOKIE[ LOGGED_IN_COOKIE ] );
		remove_all_filters( 'home_url' );
	}

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

			add_filter(
				'pre_http_request',
				$fail_http_request = function () {
					return new \WP_Error( 'http_request_failed', 'A valid URL was not provided.' );
				}
			);

			new Run();

			global $wp_settings_errors;

			$this->assertNotEmpty( $wp_settings_errors );
			$this->assertArrayHasKey( 'code', $wp_settings_errors[0] );
			$this->assertEquals( 'omgf_frontend_fetch_failed', $wp_settings_errors[0]['code'] );
		} finally {
			remove_filter( 'omgf_filter_optimize_url', $filter_home_url );
			remove_filter( 'pre_http_request', $fail_http_request );
			delete_transient( Notice::OMGF_ADMIN_NOTICE_TRANSIENT );
		}
	}
}
