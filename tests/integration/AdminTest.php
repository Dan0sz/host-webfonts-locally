<?php
/**
 * @package OMGF integration tests - Admin
 */

namespace OMGF\Tests\Integration;

use OMGF\Admin;
use OMGF\Admin\Settings;
use OMGF\Helper as OMGF;
use OMGF\Tests\TestCase;

class AdminTest extends TestCase {
	/**
	 * @see Admin::clean_up_cache()
	 * Ensure entries matching old cache keys are removed from OMGF_UPLOAD_DIR.
	 */
	public function testCleanUpCacheDeletesMatchingEntries() {
		$admin = new Admin();

		// Ensure cache directory exists.
		wp_mkdir_p( OMGF_UPLOAD_DIR );

		$dir_to_remove  = OMGF_UPLOAD_DIR . '/foo-key1';
		$file_to_remove = OMGF_UPLOAD_DIR . '/bar-key2';
		$dir_to_keep    = OMGF_UPLOAD_DIR . '/keep-this';

		// Create dummy entries.
		wp_mkdir_p( $dir_to_remove );
		file_put_contents( $dir_to_remove . '/dummy.txt', 'test' );
		file_put_contents( $file_to_remove, 'test' );
		wp_mkdir_p( $dir_to_keep );

		// Invoke cleanup with old value containing two keys.
		$admin->clean_up_cache( 'new-value', 'key1,key2' );

		// Assert matching entries are deleted and unrelated remain.
		$this->assertDirectoryDoesNotExist( $dir_to_remove );
		$this->assertFileDoesNotExist( $file_to_remove );
		$this->assertDirectoryExists( $dir_to_keep );

		// Cleanup remaining artifact.
		OMGF::delete( $dir_to_keep );
	}

	/**
	 * @see Admin::do_advanced_settings()
	 * @return void
	 */
	public function testDoAdvancedSettings() {
		new Admin();

		$this->expectOutputContains( 'Remove Settings/Files At Uninstall' );

		do_action( 'omgf_advanced_settings_content' );
	}

	/**
	 * @see Admin::do_help()
	 * @return void
	 */
	public function testDoHelp() {
		new Admin();

		$this->expectOutputContains( 'Thank you for using' );

		do_action( 'omgf_help_content' );
	}

	/**
	 * @see Admin::do_optimize_settings()
	 * @return void
	 */
	public function testDoOptimizeSettings() {
		new Admin();

		$this->expectOutputContains( 'Dashboard' );

		do_action( 'omgf_optimize_settings_content' );
	}

	/**
	 * @see Admin::maybe_show_stale_cache_notice()
	 * @return void
	 */
	public function testMaybeShowStaleCacheNoticeWithExistingErrors() {
		global $wp_settings_errors;

		$original_wp_settings_errors = $wp_settings_errors ?? [];
		$original_get                = $_GET;

		try {
			$wp_settings_errors = [
				[
					'code'    => 'omgf_some_error',
					'message' => 'Some OMGF error',
					'type'    => 'error',
				],
			];

			$class = new Admin();

			$_GET['page'] = Settings::OMGF_ADMIN_PAGE;
			$_GET['tab']  = 'test';

			OMGF::delete_option( Settings::OMGF_FLAG_CACHE_IS_STALE );

			// This should trigger line 248 ($show_message = false) because of the existing 'omgf' error
			$class->maybe_show_stale_cache_notice( [ 'subsets' => [ 'latin-ext' ] ], [ 'subsets' => [ 'latin' ] ] );

			$this->assertNull( OMGF::get_option( Settings::OMGF_FLAG_CACHE_IS_STALE ) );
		} finally {
			// Cleanup
			$wp_settings_errors = $original_wp_settings_errors;
			$_GET               = $original_get;
			OMGF::delete_option( Settings::OMGF_FLAG_CACHE_IS_STALE );
		}
	}

	/**
	 * @see Admin::maybe_show_stale_cache_notice()
	 * @return void
	 */
	public function testShowStaleCacheNotice() {
		global $wp_settings_errors;

		/**
		 * Make sure it's empty.
		 */
		$wp_settings_errors = [];

		$class = new Admin();

		$_GET['page'] = Settings::OMGF_ADMIN_PAGE;
		$_GET['tab']  = 'test';

		$class->maybe_show_stale_cache_notice( [ 'subsets' => [ 'latin-ext' ] ], [ 'subsets' => [ 'latin' ] ] );

		$this->assertTrue( OMGF::get_option( Settings::OMGF_FLAG_CACHE_IS_STALE ) );

		OMGF::delete_option( Settings::OMGF_FLAG_CACHE_IS_STALE );
	}
}
