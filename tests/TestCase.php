<?php
/**
 * Tests
 *
 * @package OMGF
 * @author  Daan van den Bergh
 */

namespace OMGF\Tests;

use Yoast\WPTestUtils\BrainMonkey\TestCase as YoastTestCase;

class TestCase extends YoastTestCase {
	/**
	 * Build class.
	 */
	public function __construct() {
		/**
		 * During local unit testing this constant is required.
		 */
		if ( ! defined( 'ABSPATH' ) ) {
			define( 'ABSPATH', true );
		}

		/**
		 * Required for loading assets.
		 */
		if ( ! defined( 'OMGF_TESTS_ROOT' ) ) {
			define( 'OMGF_TESTS_ROOT', __DIR__ . '/' );
		}

		parent::__construct();
	}

	/**
	 * Add manage_options cap.
	 *
	 * @param $allcaps
	 *
	 * @return true[]
	 */
	public function addManageOptionsCap( $allcaps ) {
		return array_merge( $allcaps, [ 'manage_options' => true ] );
	}

	/**
	 * @return string
	 */
	public function addTestCacheKey() {
		return 'cache_key,test_cache_key';
	}

	public function returnOn() {
		return 'on';
	}
	
	public function addPreloadFonts() {
		return unserialize(
			'a:1:{s:14:"test_cache_key";a:1:{s:15:"source-sans-pro";a:7:{s:9:"300italic";s:1:"0";s:9:"400italic";s:1:"0";i:300;s:1:"0";i:400;s:3:"400";i:600;s:3:"600";i:700;s:3:"700";i:900;s:1:"0";}}}'
		);
	}
}
