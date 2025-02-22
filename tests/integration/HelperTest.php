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
	 * @see Helper::preloaded_fonts()
	 * @return void
	 */
	public function testPreloadedFonts() {
		global $wp_filter;

		// For some reason this fails on Github.
		if ( isset( $wp_filter[ 'omgf_setting_preload_fonts' ] ) ) {
			unset( $wp_filter[ 'omgf_setting_preload_fonts' ] );
		}

		$preloads = OMGF::preloaded_fonts();

		$this->assertEmpty( $preloads );
		$this->assertIsArray( $preloads );
	}

	/**
	 * @see Helper::unloaded_fonts()
	 * @return void
	 */
	public function testUnloadedFonts() {
		OMGF::delete_option( Settings::OMGF_OPTIMIZE_SETTING_UNLOAD_FONTS );

		$unloads = OMGF::unloaded_fonts();

		$this->assertEmpty( $unloads );
		$this->assertIsArray( $unloads );
	}

	/**
	 * @see Helper::unloaded
	 * @return void
	 */
	public function testUnloadedStylesheets() {
		OMGF::delete_option( Settings::OMGF_OPTIMIZE_SETTING_UNLOAD_STYLESHEETS );

		$unloaded = OMGF::unloaded_stylesheets();

		$this->assertEmpty( $unloaded );
		$this->assertIsArray( $unloaded );
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
}
