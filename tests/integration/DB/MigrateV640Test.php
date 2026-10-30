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

			$this->assertTrue( $migration->set_options_autoload_legacy( [] ) );

			$this->assertContains( $this->get_autoload( 'omgf_test_legacy' ), [ 'on', 'yes' ] );

			$this->assertTrue( $migration->set_options_autoload_legacy( [ 'omgf_test_legacy' ] ) );

			$this->assertSame( 'no', $this->get_autoload( 'omgf_test_legacy' ) );
			$this->assertArrayNotHasKey( 'omgf_test_legacy', wp_load_alloptions() );
			$this->assertSame( 'test', get_option( 'omgf_test_legacy' ) );
		} finally {
			delete_option( 'omgf_test_legacy' );
			update_option( Settings::OMGF_CURRENT_DB_VERSION, $db_version );
		}
	}

	/**
	 * If autoload can't be disabled, the version isn't stored, so the migration runs again on the next load.
	 *
	 * @see V640::init()
	 * @see V640::has_autoloaded_options()
	 * @return void
	 */
	public function testMigrationRetriesIfAutoloadCantBeDisabled() {
		global $wpdb;

		$db_version = get_option( Settings::OMGF_CURRENT_DB_VERSION );
		$options    = OMGF::non_autoloaded_options();
		$fail       = function ( $query ) {
			return strpos( $query, 'UPDATE' ) === 0 && strpos( $query, 'autoload' ) !== false ? 'UPDATE `omgf_v640_nonexistent_table` SET x = 1' : $query;
		};

		try {
			update_option( Settings::OMGF_CURRENT_DB_VERSION, '6.3.1' );

			foreach ( $options as $option ) {
				delete_option( $option );
				add_option( $option, 'test', '', true );
			}

			add_filter( 'query', $fail );
			$suppress = $wpdb->suppress_errors( true );

			$migration = new V640();

			$this->assertFalse( $migration->set_options_autoload_legacy( $options ) );

			$wpdb->suppress_errors( $suppress );
			remove_filter( 'query', $fail );

			$this->assertTrue( $migration->has_autoloaded_options( $options ) );
			$this->assertSame( '6.3.1', get_option( Settings::OMGF_CURRENT_DB_VERSION ) );
		} finally {
			remove_filter( 'query', $fail );

			foreach ( $options as $option ) {
				delete_option( $option );
			}

			update_option( Settings::OMGF_CURRENT_DB_VERSION, $db_version );
		}
	}

	/**
	 * Migrations only run if the stored version is lower than OMGF_DB_VERSION, so it must cover the latest migration.
	 *
	 * @see \OMGF\Plugin::init()
	 * @return void
	 */
	public function testDbVersionCoversAllMigrations() {
		preg_match_all( "/should_run_migration\\( '([0-9.]+)' \\)/", file_get_contents( OMGF_PLUGIN_DIR . 'src/DB/Migrate.php' ), $matches );

		$this->assertNotEmpty( $matches[1] );

		foreach ( $matches[1] as $version ) {
			$this->assertGreaterThanOrEqual( 0, version_compare( OMGF_DB_VERSION, $version ), $version );
		}
	}
}
