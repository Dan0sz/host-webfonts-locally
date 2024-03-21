<?php
/**
 * @package OMGF integration tests - Actions
 */

namespace OMGF\Tests\Integration\Admin;

use OMGF\Admin\Actions;
use OMGF\Helper as OMGF;
use OMGF\Tests\TestCase;

class ActionsTest extends TestCase {
	/**
	 * @see Actions::init_admin()
	 * @return void
	 */
	public function testInitAdmin() {
		new Actions();

		do_action( '_admin_menu' );

		$this->assertTrue( class_exists( '\OMGF\Admin\Settings' ) );
	}

	/**
	 * @see Actions::do_optimize()
	 * @return void
	 */
	public function testDoOptimize() {
		new Actions();

		$this->assertTrue( class_exists( '\OMGF\Admin\Optimize' ) );
	}

	/**
	 * @see Actions::update_settings()
	 * @return void
	 */
	public function testUpdateSettings() {
		$_POST[ '_wpnonce' ] = wp_create_nonce( 'omgf-optimize-settings-options' );
		$_POST[ 'action' ]   = 'omgf-update';

		$_POST[ 'omgf_test' ]       = 'test';
		$_POST[ 'omgf_test_array' ] = [ 'test' ];
		$_POST[ 'omgf_test_zero' ]  = 0;

		$class = new Actions();
		$class->update_settings();;

		$this->assertEquals( 'test', OMGF::get_option( 'omgf_test' ) );
		$this->assertEquals( [ 'test' ], OMGF::get_option( 'omgf_test_array' ) );
		$this->assertEquals( [], OMGF::get_option( 'omgf_test_zero' ) );
	}

	/**
	 * @see Actions::clean_stale_cache()
	 * @return void
	 */
	public function testCleanStaleCache() {
		new Actions();

		wp_mkdir_p( OMGF_UPLOAD_DIR . '/test_cache_key' );
		file_put_contents( OMGF_UPLOAD_DIR . '/test_cache_key/dummy.file', 'test' );

		add_filter( 'omgf_setting_cache_keys', [ $this, 'addTestCacheKey' ] );

		do_action( 'omgf_pre_update_setting_cache_keys', '', 'test_cache_key-mod123' );

		remove_filter( 'omgf_setting_cache_keys', [ $this, 'addTestCacheKey' ] );

		$this->assertDirectoryDoesNotExist( OMGF_UPLOAD_DIR . '/test_cache_key' );
	}
}
