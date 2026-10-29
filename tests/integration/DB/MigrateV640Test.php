<?php
/**
 * @package OMGF Integration Test - DB Migration V6.4.0
 */

namespace OMGF\Tests\Integration\DB;

use OMGF\Admin\Settings;
use OMGF\DB\Migrate\V640;
use OMGF\Helper as OMGF;
use OMGF\Tests\TestCase;

class MigrateV640Test extends TestCase {
	/**
	 * Get the raw autoload value from the database.
	 *
	 * @param string $option
	 *
	 * @return string|null
	 */
	private function get_autoload( $option ) {
		global $wpdb;

		return $wpdb->get_var( $wpdb->prepare( "SELECT autoload FROM $wpdb->options WHERE option_name = %s", $option ) );
	}

	/**
	 * Existing options are switched to non-autoloaded, and frontend options are left alone.
	 *
	 * @see V640::init()
	 * @return void
	 */
	public function testMigrationDisablesAutoload() {
		$db_version = get_option( Settings::OMGF_CURRENT_DB_VERSION );
		$options    = OMGF::non_autoloaded_options();

		try {
			foreach ( $options as $option ) {
				delete_option( $option );
				add_option( $option, 'test', '', true );
			}

			add_option( 'omgf_test_autoloaded', 'test', '', true );

			new V640();

			foreach ( $options as $option ) {
				$this->assertContains( $this->get_autoload( $option ), [ 'off', 'no' ], $option );
				$this->assertSame( 'test', get_option( $option ) );
			}

			$this->assertContains( $this->get_autoload( 'omgf_test_autoloaded' ), [ 'on', 'yes' ] );
			$this->assertSame( '6.4.0', get_option( Settings::OMGF_CURRENT_DB_VERSION ) );
		} finally {
			foreach ( $options as $option ) {
				delete_option( $option );
			}

			delete_option( 'omgf_test_autoloaded' );
			update_option( Settings::OMGF_CURRENT_DB_VERSION, $db_version );
		}
	}

	/**
	 * The fallback for WordPress < 6.4 sets autoload to 'no'.
	 *
	 * @see V640::set_options_autoload_legacy()
	 * @return void
	 */
	public function testSetOptionsAutoloadLegacy() {
		$db_version = get_option( Settings::OMGF_CURRENT_DB_VERSION );

		try {
			add_option( 'omgf_test_legacy', 'test', '', true );

			$migration = new V640();
			$migration->set_options_autoload_legacy( [] );

			$this->assertContains( $this->get_autoload( 'omgf_test_legacy' ), [ 'on', 'yes' ] );

			$migration->set_options_autoload_legacy( [ 'omgf_test_legacy' ] );

			$this->assertSame( 'no', $this->get_autoload( 'omgf_test_legacy' ) );
			$this->assertArrayNotHasKey( 'omgf_test_legacy', wp_load_alloptions() );
			$this->assertSame( 'test', get_option( 'omgf_test_legacy' ) );
		} finally {
			delete_option( 'omgf_test_legacy' );
			update_option( Settings::OMGF_CURRENT_DB_VERSION, $db_version );
		}
	}
}
