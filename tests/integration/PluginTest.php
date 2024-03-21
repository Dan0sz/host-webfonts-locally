<?php
/**
 * OMGF: Testing Plugin Main initialization.
 */

namespace OMGF\Tests\Integration;

use OMGF\Plugin;
use OMGF\Tests\TestCase;

class PluginTest extends TestCase {
	/**
	 * This test isn't very meaningful, besides the fact that it executes the Plugin's init code.
	 * @return void
	 */
	public function testInit() {
		require_once( OMGF_TESTS_ROOT . '../host-webfonts-local.php' );

		new Plugin();

		$this->assertTrue( class_exists( '\OMGF\Filters' ) );
	}

	/**
	 * This test isn't very meaningful, besides the fact that it executes the Plugin's Admin init code.
	 * @return void
	 */
	public function testInitAdmin() {
		if ( ! defined( 'WP_ADMIN' ) ) {
			define( 'WP_ADMIN', true );
		}

		new Plugin();

		$this->assertTrue( class_exists( '\OMGF\Admin\Actions' ) );
	}

	/**
	 * @see  Plugin::do_migrate_db()
	 * @return void
	 * @todo Make this test meaningful.
	 */
	public function testMigrateDb() {
		new Plugin();

		add_filter( 'omgf_setting_omgf_current_db_version', '__return_false' );

		do_action( 'plugins_loaded' );

		add_filter( 'omgf_setting_omgf_current_db_version', '__return_false' );

		$this->assertTrue( true );
	}
}
