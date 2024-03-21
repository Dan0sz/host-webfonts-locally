<?php
/**
 * @package OMGF Integration Test - Helper
 */

namespace OMGF\Tests\Integration;

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
		$preloads = OMGF::preloaded_fonts();

		$this->assertEmpty( $preloads );
		$this->assertIsArray( $preloads );
	}

	/**
	 * @see Helper::unloaded_fonts()
	 * @return void
	 */
	public function testUnloadedFonts() {
		$unloads = OMGF::unloaded_fonts();

		$this->assertEmpty( $unloads );
		$this->assertIsArray( $unloads );
	}

	/**
	 * @see Helper::unloaded
	 * @return void
	 */
	public function testUnloadedStylesheets() {
		$unloaded = OMGF::unloaded_stylesheets();

		$this->assertEmpty( $unloaded );
		$this->assertIsArray( $unloaded );
	}

	/**
	 * @see Helper::get_cache_key()
	 * @return void
	 */
	public function testGetCacheKey() {
		$cache_key = OMGF::get_cache_key( 'test' );

		$this->assertEmpty( $cache_key );

		add_filter( 'omgf_setting_cache_keys', [ $this, 'addTestCacheKey' ] );

		$cache_key = OMGF::get_cache_key( 'test' );

		remove_filter( 'omgf_setting_cache_keys', [ $this, 'addTestCacheKey' ] );

		$this->assertEquals( 'test_cache_key', $cache_key );
	}

	/**
	 * @return string
	 */
	public function addTestCacheKey() {
		return 'cache_key,test_cache_key';
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
}
