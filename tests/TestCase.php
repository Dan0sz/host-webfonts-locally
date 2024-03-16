<?php
/**
 * Tests
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
}
