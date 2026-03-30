<?php
/**
 * @package OMGF Unit Tests - Helper
 */

namespace OMGF\Tests\Unit;

use OMGF\Helper;
use OMGF\Tests\TestCase;

class HelperTest extends TestCase {
	/**
	 * @var string
	 */
	private $test_dir;

	/**
	 * Set up a test environment.
	 */
	public function set_up() {
		parent::set_up();

		$this->test_dir = __DIR__ . '/helper-test-tmp';

		if ( ! is_dir( $this->test_dir ) ) {
			mkdir( $this->test_dir );
		}
	}

	/**
	 * Clean up the test environment.
	 */
	public function tear_down() {
		if ( is_dir( $this->test_dir ) ) {
			Helper::delete( $this->test_dir );
		}

		parent::tear_down();
	}

	/**
	 * Test deleting a broken symlink.
	 */
	public function testDeleteBrokenSymlink() {
		$link   = $this->test_dir . '/broken-link';
		$target = $this->test_dir . '/non-existent';

		if ( ! @symlink( $target, $link ) ) {
			$this->markTestSkipped( 'Could not create symlink for testing.' );
		}

		$this->assertTrue( is_link( $link ) );
		$this->assertFalse( file_exists( $link ) ); // file_exists returns false for broken symlinks

		$this->assertTrue( Helper::delete( $link ) );
		$this->assertFalse( is_link( $link ) );
	}

	/**
	 * Test deleting a directory recursively.
	 */
	public function testDeleteDirectoryRecursively() {
		$sub_dir = $this->test_dir . '/sub';
		mkdir( $sub_dir );
		$file = $sub_dir . '/test.txt';
		file_put_contents( $file, 'test' );

		$this->assertDirectoryExists( $sub_dir );
		$this->assertFileExists( $file );

		Helper::delete( $this->test_dir );

		$this->assertDirectoryDoesNotExist( $sub_dir );
		$this->assertFileDoesNotExist( $file );
		$this->assertDirectoryDoesNotExist( $this->test_dir );
	}

	/**
	 * Test deleting a directory containing a symlink.
	 */
	public function testDeleteDirectoryWithSymlink() {
		$dir = $this->test_dir . '/dir-with-link';
		mkdir( $dir );
		$target = $this->test_dir . '/target-outside';
		file_put_contents( $target, 'target content' );

		$link = $dir . '/link-inside';
		if ( ! @symlink( $target, $link ) ) {
			$this->markTestSkipped( 'Could not create symlink for testing.' );
		}

		$this->assertTrue( is_link( $link ) );
		$this->assertDirectoryExists( $dir );

		$this->assertTrue( Helper::delete( $dir ) );

		$this->assertDirectoryDoesNotExist( $dir );
		$this->assertFileExists( $target ); // Target should still exist
	}

	/**
	 * Test deleting a single file.
	 */
	public function testDeleteFile() {
		$file = $this->test_dir . '/test.txt';
		file_put_contents( $file, 'test' );

		$this->assertFileExists( $file );
		Helper::delete( $file );
		$this->assertFileDoesNotExist( $file );
	}

	/**
	 * Test deleting a non-existent file.
	 */
	public function testDeleteNonExistent() {
		$file = $this->test_dir . '/does-not-exist.txt';
		$this->assertFileDoesNotExist( $file );
		$this->assertTrue( Helper::delete( $file ) );
	}

	/**
	 * Test that deleting a symlink doesn't follow it.
	 * This covers the path traversal fix.
	 */
	public function testDeleteSymlinkDoesNotFollow() {
		$target_dir = $this->test_dir . '/target';
		mkdir( $target_dir );
		$secret_file = $target_dir . '/secret.txt';
		file_put_contents( $secret_file, 'secret' );

		$link = $this->test_dir . '/link';

		// Attempt to create symlink. On some Windows environments this might fail without admin,
		// but in WSL/Linux it should be fine.
		if ( ! @symlink( $target_dir, $link ) ) {
			$this->markTestSkipped( 'Could not create symlink for testing.' );
		}

		$this->assertDirectoryExists( $target_dir );
		$this->assertFileExists( $secret_file );
		$this->assertTrue( is_link( $link ) );

		// Delete the symlink using Helper::delete
		Helper::delete( $link );

		// The link itself should be gone
		$this->assertFalse( file_exists( $link ), 'Symlink should be deleted' );

		// The target and its contents should still exist
		$this->assertDirectoryExists( $target_dir, 'Target directory should not be deleted' );
		$this->assertFileExists( $secret_file, 'Files inside target directory should not be deleted' );
	}

	/**
	 * Test @see Helper::flush_third_party_cache()
	 *
	 * Since the method uses a static variable to prevent multiple flushes,
	 * we can only truly test it once in a single request/process.
	 *
	 * @return void
	 */
	public function testFlushThirdPartyCache() {
		if ( ! defined( 'OMGF_UPLOAD_DIR' ) ) {
			define( 'OMGF_UPLOAD_DIR', $this->test_dir . '/omgf-cache' );
		}

		if ( ! is_dir( OMGF_UPLOAD_DIR ) ) {
			mkdir( OMGF_UPLOAD_DIR );
		}

		$third_party_dir = OMGF_UPLOAD_DIR . '/third-party';
		$known_dir       = OMGF_UPLOAD_DIR . '/known-handle';

		if ( ! is_dir( $third_party_dir ) ) {
			mkdir( $third_party_dir );
		}

		if ( ! is_dir( $known_dir ) ) {
			mkdir( $known_dir );
		}

		try {
			$set_cache_keys = function () {
				return [ 'known-handle' ];
			};

			add_filter( 'omgf_cache_keys', $set_cache_keys );
			// Reset static properties to ensure Helper::cache_keys() reads from the "database".
			Helper::reset_static_properties();
			Helper::cache_keys( true ); // Force reload from mocked get_option()

			$this->assertDirectoryExists( $third_party_dir );
			$this->assertDirectoryExists( $known_dir );

			Helper::flush_third_party_cache();

			$this->assertDirectoryDoesNotExist( $third_party_dir, 'Third-party directory should be flushed.' );
			$this->assertDirectoryExists( $known_dir, 'Known handle directory should NOT be flushed.' );

			// Test that it doesn't run again in the same request (static $flushed = true)
			$another_third_party_dir = OMGF_UPLOAD_DIR . '/another-third-party';

			mkdir( $another_third_party_dir );

			$this->assertDirectoryExists( $another_third_party_dir );

			Helper::flush_third_party_cache();

			$this->assertDirectoryExists( $another_third_party_dir, 'Static $flushed should prevent second flush in same request.' );
		} finally {
			remove_filter( 'omgf_cache_keys', $set_cache_keys );
		}
	}
}
