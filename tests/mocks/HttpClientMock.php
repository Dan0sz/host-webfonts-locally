<?php
/**
 * HTTP Client Mock for Testing
 * @package OMGF
 * @author  Daan van den Bergh
 */

namespace OMGF\Tests\Mocks;

class HttpClientMock {
	private static $responses = [];

	private static $is_active = false;

	/**
	 * Set a mock response for a specific URL
	 */
	public static function mockResponse( $url, $body, $code = 200 ) {
		self::$responses[ $url ] = [
			'body'     => $body,
			'response' => [ 'code' => $code ],
		];
	}

	/**
	 * Set mock CSS content that will be returned for any URL
	 */
	public static function mockCssContent( $content ) {
		self::$responses[ '*' ] = [
			'body'     => $content,
			'response' => [ 'code' => 200 ],
		];
	}

	/**
	 * Activate mocking - this replaces wp_remote_get behavior
	 */
	public static function activate() {
		if ( ! self::$is_active ) {
			add_filter( 'pre_http_request', [ self::class, 'interceptRequest' ], 10, 3 );
			self::$is_active = true;
		}
	}

	/**
	 * Intercept HTTP requests and return mock responses
	 */
	public static function interceptRequest( $preempt, $parsed_args, $url ) {
		// Check for exact URL match first
		if ( isset( self::$responses[ $url ] ) ) {
			return self::$responses[ $url ];
		}

		// Check for wildcard match
		if ( isset( self::$responses[ '*' ] ) ) {
			return self::$responses[ '*' ];
		}

		// Let the request proceed normally if no mock is set
		return false;
	}

	/**
	 * Reset all mocks
	 */
	public static function reset() {
		self::deactivate();
	}

	/**
	 * Deactivate mocking
	 */
	public static function deactivate() {
		remove_filter( 'pre_http_request', [ self::class, 'interceptRequest' ], 10 );
		self::$is_active = false;
		self::$responses = [];
	}
}
