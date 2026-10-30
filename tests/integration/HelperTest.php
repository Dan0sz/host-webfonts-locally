<?php
/**
 * @package OMGF Integration Test - Helper
 */

namespace OMGF\Tests\Integration;

use OMGF\Admin\Settings;
use OMGF\Helper as OMGF;
use OMGF\Tests\TestCase;

class HelperTest extends TestCase {
	/**
	 * @see Helper::update_option()
	 * @return void
	 */
	public function testUpdateOption() {
		OMGF::update_option( 'omgf_test', true );

		$this->assertTrue( OMGF::get_option( 'omgf_test' ) );

		OMGF::update_option( 'test', true );

		$this->assertTrue( OMGF::get_option( 'test' ) );
	}

	/**
	 * Options which aren't needed in the frontend aren't autoloaded, other options are.
	 *
	 * @see Helper::update_option()
	 * @see Helper::non_autoloaded_options()
	 * @return void
	 */
	public function testUpdateOptionAutoload() {
		global $wpdb;

		$get_autoload = function ( $option ) use ( $wpdb ) {
			return $wpdb->get_var( $wpdb->prepare( "SELECT autoload FROM $wpdb->options WHERE option_name = %s", $option ) );
		};
		$filter       = function ( $options ) {
			$options[] = 'omgf_test_filtered';

			return $options;
		};

		try {
			delete_option( Settings::OMGF_DB_PERF_CHECK );

			// New row.
			OMGF::update_option( Settings::OMGF_DB_PERF_CHECK, [ 'highest_cls' => 1 ] );
			$this->assertContains( $get_autoload( Settings::OMGF_DB_PERF_CHECK ), [ 'off', 'no' ] );

			// Existing row, which was autoloaded before.
			delete_option( Settings::OMGF_DB_PERF_CHECK );
			add_option( Settings::OMGF_DB_PERF_CHECK, [ 'highest_cls' => 1 ], '', true );
			OMGF::update_option( Settings::OMGF_DB_PERF_CHECK, [ 'highest_cls' => 2 ] );
			$this->assertContains( $get_autoload( Settings::OMGF_DB_PERF_CHECK ), [ 'off', 'no' ] );
			$this->assertArrayNotHasKey( Settings::OMGF_DB_PERF_CHECK, wp_load_alloptions() );

			// An explicit $autoload still wins.
			OMGF::update_option( Settings::OMGF_DB_PERF_CHECK, [ 'highest_cls' => 3 ], true );
			$this->assertContains( $get_autoload( Settings::OMGF_DB_PERF_CHECK ), [ 'on', 'yes' ] );

			// Options read in the frontend are still autoloaded.
			OMGF::update_option( 'omgf_test_autoload', 'test' );
			$this->assertContains( $get_autoload( 'omgf_test_autoload' ), [ 'on', 'yes', 'auto' ] );

			// Filterable.
			add_filter( 'omgf_non_autoloaded_options', $filter );
			$this->assertContains( 'omgf_test_filtered', OMGF::non_autoloaded_options() );
			OMGF::update_option( 'omgf_test_filtered', 'test' );
			$this->assertContains( $get_autoload( 'omgf_test_filtered' ), [ 'off', 'no' ] );
		} finally {
			remove_filter( 'omgf_non_autoloaded_options', $filter );
			delete_option( Settings::OMGF_DB_PERF_CHECK );
			delete_option( 'omgf_test_autoload' );
			delete_option( 'omgf_test_filtered' );
		}
	}

	/**
	 * Serialized strings stored in OMGF's options may only contain stdClass objects.
	 *
	 * @see Helper::maybe_unserialize()
	 * @see Helper::get_option()
	 * @return void
	 */
	public function testMaybeUnserializeOnlyAllowsStdClass() {
		$fonts = [ 'handle' => (object) [ 'id' => 'open-sans' ] ];

		$this->assertEquals( $fonts, OMGF::maybe_unserialize( serialize( $fonts ) ) );
		$this->assertSame( 'not serialized', OMGF::maybe_unserialize( 'not serialized' ) );
		$this->assertSame( [ 'array' ], OMGF::maybe_unserialize( [ 'array' ] ) );
		$this->assertInstanceOf( '__PHP_Incomplete_Class', OMGF::maybe_unserialize( serialize( new \ArrayObject( [] ) ) ) );

		try {
			// A serialized string, i.e. stored double serialized by WordPress.
			update_option( 'omgf_test_serialized', serialize( new \ArrayObject( [] ) ) );

			$this->assertNotInstanceOf( \ArrayObject::class, OMGF::get_option( 'omgf_test_serialized' ) );

			update_option( 'omgf_test_serialized', serialize( $fonts ) );

			$this->assertEquals( $fonts, OMGF::get_option( 'omgf_test_serialized' ) );
		} finally {
			delete_option( 'omgf_test_serialized' );
		}
	}

	/**
	 * A corrupted Optimized Fonts option (e.g. a string which can't be unserialized) shouldn't cause a fatal error.
	 *
	 * @see Helper::admin_optimized_fonts()
	 * @see Helper::optimized_fonts()
	 * @return void
	 */
	public function testOptimizedFontsAreAlwaysAnArray() {
		$fonts = [ 'handle' => (object) [ 'id' => 'open-sans' ] ];

		try {
			foreach ( [ 'not serialized', 'a:1:{s:6:"handle";corrupt', serialize( new \ArrayObject( [] ) ) ] as $corrupt ) {
				update_option( Settings::OMGF_OPTIMIZE_SETTING_OPTIMIZED_FONTS, $corrupt );
				update_option( Settings::OMGF_OPTIMIZE_SETTING_OPTIMIZED_FONTS_FRONTEND, $corrupt );
				OMGF::reset_cache();

				$this->assertSame( [], OMGF::admin_optimized_fonts() );
				$this->assertEquals( $fonts, OMGF::admin_optimized_fonts( $fonts, true ) );

				OMGF::reset_cache();

				$this->assertEquals( $fonts, OMGF::optimized_fonts( $fonts, true ) );

				// A corrupted frontend option should fall back to the (valid) admin Optimized Fonts.
				update_option( Settings::OMGF_OPTIMIZE_SETTING_OPTIMIZED_FONTS, $fonts );
				OMGF::reset_cache();

				$this->assertEquals( $fonts, OMGF::optimized_fonts() );
			}
		} finally {
			delete_option( Settings::OMGF_OPTIMIZE_SETTING_OPTIMIZED_FONTS );
			delete_option( Settings::OMGF_OPTIMIZE_SETTING_OPTIMIZED_FONTS_FRONTEND );
			OMGF::reset_cache();
		}
	}

	/**
	 * @see Helper::delete_option()
	 * @return void
	 */
	public function testDeleteOption() {
		OMGF::delete_option( 'omgf_test' );

		$this->assertEmpty( OMGF::get_option( 'omgf_test' ) );

		OMGF::delete_option( 'test' );

		$this->assertEmpty( OMGF::get_option( 'test' ) );
	}

	/**
	 * @see Helper::get_cache_key()
	 * @return void
	 */
	public function testGetCacheKey() {
		add_filter( 'omgf_setting_cache_keys', [ $this, 'addTestCacheKey' ] );

		$cache_key = OMGF::get_cache_key( 'test' );

		remove_filter( 'omgf_setting_cache_keys', [ $this, 'addTestCacheKey' ] );

		$this->assertEquals( 'test_cache_key', $cache_key );
	}

	/**
	 * @see Helper::delete()
	 * @return void
	 */
	public function testDelete() {
		wp_mkdir_p( OMGF_UPLOAD_DIR . '/test/' );
		file_put_contents( OMGF_UPLOAD_DIR . '/test/test.log', 'test' );

		$this->assertFileExists( OMGF_UPLOAD_DIR . '/test/test.log' );

		OMGF::delete( OMGF_UPLOAD_DIR . '/test' );

		$this->assertFileDoesNotExist( OMGF_UPLOAD_DIR . '/test/test.log' );
		$this->assertDirectoryDoesNotExist( OMGF_UPLOAD_DIR . '/test/' );
	}

	/**
	 * @see Helper::get_settings()
	 * @return void
	 */
	public function testGetDefaultSettings() {
		OMGF::delete_option( Settings::OMGF_ADV_SETTING_SUBSETS );

		$subsets = OMGF::get_option( Settings::OMGF_ADV_SETTING_SUBSETS );

		$this->assertEquals( [ 'latin', 'latin-ext' ], $subsets );

		$font_display = OMGF::get_option( Settings::OMGF_OPTIMIZE_SETTING_DISPLAY_OPTION );

		$this->assertEquals( 'swap', $font_display );
	}

	/**
	 * Values which are a complete font-family declaration value, a CSS-wide keyword or a
	 * generic family are never the name of a font we can download.
	 *
	 * @see Helper::is_valid_font_family()
	 * @return void
	 */
	public function testIsValidFontFamilyRejectsInvalidValues() {
		$invalid = [
			'Arial,Helvetica,sans-serif !important',
			'var(--tve-font-family,Arial,Helvetica,sans-serif)',
			'var(--wp--preset--font-family--nunito-sans-12-pt-extralight-12-pt) !important',
			'Courier New",Courier,monospace',
			'Times New Roman", Times, serif',
			'Open Sans" !important',
			'Mulish !important',
			'inherit',
			'INITIAL',
			' unset ',
			'sans-serif',
			'monospace',
			'--tve-font-family',
			'',
			'   ',
		];

		foreach ( $invalid as $family ) {
			$this->assertFalse( OMGF::is_valid_font_family( $family ), "'$family' should be invalid" );
		}

		$this->assertFalse( OMGF::is_valid_font_family( null ) );
		$this->assertFalse( OMGF::is_valid_font_family( [ 'Roboto' ] ) );
	}

	/**
	 * @see Helper::is_valid_font_family()
	 * @return void
	 */
	public function testIsValidFontFamilyAcceptsFontFamilyNames() {
		$valid = [
			'Roboto',
			'Open Sans',
			'Nunito Sans 12pt ExtraLight 12pt',
			'Source Sans 3',
			'Material Symbols Outlined',
			'Helvetica Neue',
			'思源黑體',
		];

		foreach ( $valid as $family ) {
			$this->assertTrue( OMGF::is_valid_font_family( $family ), "'$family' should be valid" );
		}
	}

	/**
	 * @see Helper::get_db_rows_by()
	 * @return void
	 */
	public function testGetDbRowsBy() {
		$db_rows = OMGF::get_db_rows_by( [ 'OMGF_FLAG' ] );

		$this->assertArrayHasKey( 'OMGF_FLAG_OPTIMIZE_HAS_RUN', $db_rows );
		$this->assertArrayHasKey( 'OMGF_FLAG_CACHE_IS_STALE', $db_rows );

		$db_rows = OMGF::get_db_rows_by( [ 'OMGF_FLAG' ], [ Settings::OMGF_FLAG_OPTIMIZE_HAS_RUN ] );

		$this->assertArrayNotHasKey( 'OMGF_FLAG_OPTIMIZE_HAS_RUN', $db_rows );
		$this->assertArrayHasKey( 'OMGF_FLAG_CACHE_IS_STALE', $db_rows );
	}

	/**
	 * A stylesheet's last seen timestamp is stored, but updated at most once a day.
	 *
	 * @see Helper::mark_stylesheets_seen()
	 * @return void
	 */
	public function testMarkStylesheetsSeen() {
		delete_option( Settings::OMGF_DB_STYLESHEETS_LAST_SEEN );

		try {
			OMGF::mark_stylesheets_seen( [ 'New-Handle', '' ] );

			$last_seen = OMGF::stylesheets_last_seen();

			$this->assertArrayHasKey( 'new-handle', $last_seen );
			$this->assertCount( 1, $last_seen );
			$this->assertEqualsWithDelta( time(), $last_seen[ 'new-handle' ], 5 );

			// Seen less than a day ago: left alone.
			$hour_ago = time() - HOUR_IN_SECONDS;
			$days_ago = time() - 2 * DAY_IN_SECONDS;

			update_option( Settings::OMGF_DB_STYLESHEETS_LAST_SEEN, [ 'recent' => $hour_ago, 'old' => $days_ago ] );

			OMGF::mark_stylesheets_seen( [ 'recent', 'old' ] );

			$last_seen = OMGF::stylesheets_last_seen();

			$this->assertSame( $hour_ago, $last_seen[ 'recent' ] );
			$this->assertGreaterThan( $days_ago, $last_seen[ 'old' ] );
		} finally {
			delete_option( Settings::OMGF_DB_STYLESHEETS_LAST_SEEN );
		}
	}

	/**
	 * Stylesheets not seen for longer than the threshold are unused. Untracked stylesheets start being tracked and
	 * timestamps of stylesheets which are no longer optimized are removed.
	 *
	 * @see Helper::unused_stylesheets()
	 * @return void
	 */
	public function testUnusedStylesheets() {
		$fonts = function () {
			return [ 'used' => [], 'unused' => [], 'untracked' => [] ];
		};

		add_filter( 'omgf_filter_admin_optimized_fonts', $fonts );

		try {
			$old = time() - OMGF::OMGF_UNUSED_STYLESHEET_THRESHOLD - DAY_IN_SECONDS;

			update_option( Settings::OMGF_DB_STYLESHEETS_LAST_SEEN, [ 'used' => time(), 'unused' => $old, 'removed' => $old ] );

			$this->assertSame( [ 'unused' ], OMGF::unused_stylesheets() );

			$last_seen = OMGF::stylesheets_last_seen();

			$this->assertArrayNotHasKey( 'removed', $last_seen );
			$this->assertArrayHasKey( 'untracked', $last_seen );
			$this->assertSame( $old, $last_seen[ 'unused' ] );

			// The threshold is filterable.
			$threshold = function () {
				return 10 * YEAR_IN_SECONDS;
			};

			add_filter( 'omgf_unused_stylesheet_threshold', $threshold );

			$this->assertEmpty( OMGF::unused_stylesheets() );

			remove_filter( 'omgf_unused_stylesheet_threshold', $threshold );
		} finally {
			remove_filter( 'omgf_filter_admin_optimized_fonts', $fonts );
			delete_option( Settings::OMGF_DB_STYLESHEETS_LAST_SEEN );
		}
	}
}
