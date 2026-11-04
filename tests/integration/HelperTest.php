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

	/**
	 * A new handle inherits the settings of the first legacy handle which has them, unless it has its own settings.
	 *
	 * @see Helper::inherit_stylesheet_settings()
	 * @return void
	 */
	public function testInheritStylesheetSettings() {
		OMGF::update_option( Settings::OMGF_OPTIMIZE_SETTING_UNLOAD_FONTS, [ 'legacy' => [ 'lato' => [ '400' ] ] ] );
		// Older isn't optimized anymore, but still has settings.
		OMGF::update_option( Settings::OMGF_OPTIMIZE_SETTING_PRELOAD_FONTS, [ 'new' => [ 'lato' => [ '700' => '700' ] ], 'legacy' => [ 'lato' => [ '400' => '400' ] ], 'older' => [ 'lato' => [ '400' => '400' ] ] ] );
		OMGF::update_option( Settings::OMGF_OPTIMIZE_SETTING_UNLOAD_STYLESHEETS, 'other,legacy' );

		$fonts = function () {
			return [ 'legacy' => [], 'other' => [] ];
		};

		add_filter( 'omgf_filter_optimized_fonts', $fonts );

		try {
			OMGF::inherit_stylesheet_settings( [ 'Older', 'Legacy' ], 'new' );

			// Legacy handles which are optimized or have settings are marked as superseded, so they're cleaned up later.
			$this->assertSame( [ 'legacy', 'older' ], OMGF::superseded_stylesheets() );

			$this->assertSame( [ 'lato' => [ '400' ] ], OMGF::get_option( Settings::OMGF_OPTIMIZE_SETTING_UNLOAD_FONTS )['new'] );
			// Existing settings aren't overwritten.
			$this->assertSame( [ 'lato' => [ '700' => '700' ] ], OMGF::get_option( Settings::OMGF_OPTIMIZE_SETTING_PRELOAD_FONTS )['new'] );
			$this->assertSame( 'other,legacy,new', OMGF::get_option( Settings::OMGF_OPTIMIZE_SETTING_UNLOAD_STYLESHEETS ) );
		} finally {
			remove_filter( 'omgf_filter_optimized_fonts', $fonts );
			delete_option( Settings::OMGF_OPTIMIZE_SETTING_UNLOAD_FONTS );
			delete_option( Settings::OMGF_OPTIMIZE_SETTING_PRELOAD_FONTS );
			delete_option( Settings::OMGF_DB_SUPERSEDED_STYLESHEETS );
			OMGF::update_option( Settings::OMGF_OPTIMIZE_SETTING_UNLOAD_STYLESHEETS, '' );
		}
	}

	/**
	 * Removing a stylesheet removes its files and settings, but leaves other stylesheets alone, even if their handle
	 * starts with the same string.
	 *
	 * @see Helper::remove_stylesheet()
	 * @return void
	 */
	public function testRemoveStylesheet() {
		foreach ( [ 'sheet-1', 'sheet-1-mod-abc', 'sheet-12' ] as $dir ) {
			wp_mkdir_p( OMGF_UPLOAD_DIR . "/$dir" );
			file_put_contents( OMGF_UPLOAD_DIR . "/$dir/$dir.css", '' );
		}

		OMGF::update_option( Settings::OMGF_OPTIMIZE_SETTING_OPTIMIZED_FONTS, [ 'sheet-1' => [], 'sheet-12' => [] ] );
		OMGF::update_option( Settings::OMGF_OPTIMIZE_SETTING_UNLOAD_FONTS, [ 'sheet-1' => [ 'lato' => [ '400' ] ], 'sheet-12' => [ 'lato' => [ '400' ] ] ] );
		OMGF::update_option( Settings::OMGF_OPTIMIZE_SETTING_UNLOAD_STYLESHEETS, 'sheet-1,sheet-12' );
		OMGF::update_option( Settings::OMGF_OPTIMIZE_SETTING_CACHE_KEYS, 'sheet-1-mod-abc,sheet-12' );
		update_option( Settings::OMGF_DB_SUPERSEDED_STYLESHEETS, [ 'sheet-1', 'sheet-12' ] );

		try {
			OMGF::remove_stylesheet( 'sheet-1' );

			$this->assertSame( [ 'sheet-12' ], OMGF::superseded_stylesheets() );

			$this->assertDirectoryDoesNotExist( OMGF_UPLOAD_DIR . '/sheet-1' );
			$this->assertDirectoryDoesNotExist( OMGF_UPLOAD_DIR . '/sheet-1-mod-abc' );
			$this->assertDirectoryExists( OMGF_UPLOAD_DIR . '/sheet-12' );
			$this->assertSame( [ 'sheet-12' => [] ], OMGF::get_option( Settings::OMGF_OPTIMIZE_SETTING_OPTIMIZED_FONTS ) );
			$this->assertArrayNotHasKey( 'sheet-1', OMGF::get_option( Settings::OMGF_OPTIMIZE_SETTING_UNLOAD_FONTS ) );
			$this->assertSame( 'sheet-12', OMGF::get_option( Settings::OMGF_OPTIMIZE_SETTING_UNLOAD_STYLESHEETS ) );
			$this->assertSame( 'sheet-12', OMGF::get_option( Settings::OMGF_OPTIMIZE_SETTING_CACHE_KEYS ) );

			// An empty handle doesn't remove anything.
			OMGF::remove_stylesheet( '' );

			$this->assertDirectoryExists( OMGF_UPLOAD_DIR . '/sheet-12' );
		} finally {
			OMGF::delete( OMGF_UPLOAD_DIR . '/sheet-12' );
			delete_option( Settings::OMGF_OPTIMIZE_SETTING_OPTIMIZED_FONTS );
			delete_option( Settings::OMGF_OPTIMIZE_SETTING_UNLOAD_FONTS );
			OMGF::update_option( Settings::OMGF_OPTIMIZE_SETTING_UNLOAD_STYLESHEETS, '' );
			OMGF::update_option( Settings::OMGF_OPTIMIZE_SETTING_CACHE_KEYS, '' );
			delete_option( Settings::OMGF_DB_SUPERSEDED_STYLESHEETS );
		}
	}

	/**
	 * Unused stylesheets are removed and remembered for the admin notice.
	 *
	 * @see Helper::remove_unused_stylesheets()
	 * @return void
	 */
	public function testRemoveUnusedStylesheets() {
		$old = time() - OMGF::OMGF_UNUSED_STYLESHEET_THRESHOLD - DAY_IN_SECONDS;

		wp_mkdir_p( OMGF_UPLOAD_DIR . '/unused' );
		OMGF::update_option( Settings::OMGF_OPTIMIZE_SETTING_OPTIMIZED_FONTS, [ 'used' => [], 'unused' => [] ] );
		update_option( Settings::OMGF_DB_STYLESHEETS_LAST_SEEN, [ 'used' => time(), 'unused' => $old ] );
		update_option( Settings::OMGF_DB_REMOVED_STYLESHEETS, [ 'earlier' ] );
		// A superseded handle which only has settings left.
		update_option( Settings::OMGF_DB_SUPERSEDED_STYLESHEETS, [ 'settings-only' ] );
		OMGF::update_option( Settings::OMGF_OPTIMIZE_SETTING_UNLOAD_FONTS, [ 'settings-only' => [ 'lato' => [ '400' ] ] ] );

		try {
			// The superseded handle starts being tracked now.
			$this->assertSame( [ 'unused' ], OMGF::remove_unused_stylesheets() );
			$this->assertDirectoryDoesNotExist( OMGF_UPLOAD_DIR . '/unused' );
			$this->assertSame( [ 'used' => [] ], OMGF::get_option( Settings::OMGF_OPTIMIZE_SETTING_OPTIMIZED_FONTS ) );
			$this->assertSame( [ 'earlier', 'unused' ], get_option( Settings::OMGF_DB_REMOVED_STYLESHEETS ) );
			$this->assertSame( [], OMGF::remove_unused_stylesheets() );

			$last_seen                    = OMGF::stylesheets_last_seen();
			$last_seen[ 'settings-only' ] = $old;

			update_option( Settings::OMGF_DB_STYLESHEETS_LAST_SEEN, $last_seen );

			// Its settings are removed, but it wasn't cached, so it isn't added to the notice.
			$this->assertSame( [ 'settings-only' ], OMGF::remove_unused_stylesheets() );
			$this->assertEmpty( OMGF::get_option( Settings::OMGF_OPTIMIZE_SETTING_UNLOAD_FONTS ) );
			$this->assertEmpty( OMGF::superseded_stylesheets() );
			$this->assertSame( [ 'earlier', 'unused' ], get_option( Settings::OMGF_DB_REMOVED_STYLESHEETS ) );
		} finally {
			delete_option( Settings::OMGF_OPTIMIZE_SETTING_UNLOAD_FONTS );
			delete_option( Settings::OMGF_DB_SUPERSEDED_STYLESHEETS );
			delete_option( Settings::OMGF_OPTIMIZE_SETTING_OPTIMIZED_FONTS );
			delete_option( Settings::OMGF_DB_STYLESHEETS_LAST_SEEN );
			delete_option( Settings::OMGF_DB_REMOVED_STYLESHEETS );
		}
	}

	/**
	 * The hash ignores line breaks, tabs and the version parameter, but not other parameters.
	 *
	 * @see Helper::stylesheet_hash()
	 * @return void
	 */
	public function testStylesheetHash() {
		$hash = OMGF::stylesheet_hash( 'https://fonts.googleapis.com/css?family=Lato:400&display=swap' );

		$this->assertMatchesRegularExpression( '/^[0-9a-f]{8}$/', $hash );
		$this->assertSame( $hash, OMGF::stylesheet_hash( "https://fonts.googleapis.com/css?family=Lato:400\n\t&display=swap" ) );
		$this->assertSame( $hash, OMGF::stylesheet_hash( 'https://fonts.googleapis.com/css?family=Lato:400&display=swap&ver=4.6.4' ) );
		$this->assertSame( $hash, OMGF::stylesheet_hash( 'https://fonts.googleapis.com/css?family=Lato:400&display=swap&#038;ver=6.9' ) );
		$this->assertSame( $hash, OMGF::stylesheet_hash( 'https://fonts.googleapis.com/css?family=Lato:400&display=swap&amp;ver=6.9' ) );
		$this->assertSame(
			OMGF::stylesheet_hash( 'https://fonts.googleapis.com/css?family=Lato:400' ),
			OMGF::stylesheet_hash( 'https://fonts.googleapis.com/css?ver=1&family=Lato:400' )
		);
		$this->assertNotSame( $hash, OMGF::stylesheet_hash( 'https://fonts.googleapis.com/css?family=Lora:400&display=swap' ) );
		// Parameters which merely end in "ver" aren't ignored.
		$this->assertNotSame( $hash, OMGF::stylesheet_hash( 'https://fonts.googleapis.com/css?family=Lato:400&display=swap&server=1' ) );
	}

	/**
	 * Only the stylesheets shown in the notice are marked as read.
	 *
	 * @see Helper::dismiss_removed_stylesheets_notice()
	 * @return void
	 */
	public function testDismissRemovedStylesheets() {
		update_option( Settings::OMGF_DB_REMOVED_STYLESHEETS, [ 'shown', 'removed-later' ] );

		try {
			OMGF::dismiss_removed_stylesheets_notice( [ 'shown' ] );

			$this->assertSame( [ 'removed-later' ], get_option( Settings::OMGF_DB_REMOVED_STYLESHEETS ) );

			OMGF::dismiss_removed_stylesheets_notice( [ 'removed-later' ] );

			$this->assertFalse( get_option( Settings::OMGF_DB_REMOVED_STYLESHEETS ) );
		} finally {
			delete_option( Settings::OMGF_DB_REMOVED_STYLESHEETS );
		}
	}
}
