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
	 * Set up test environment.
	 */
	public function set_up() {
		parent::set_up();
		$this->test_dir = __DIR__ . '/helper-test-tmp';
		if ( ! is_dir( $this->test_dir ) ) {
			mkdir( $this->test_dir );
		}
	}

	/**
	 * Clean up test environment.
	 */
	public function tear_down() {
		if ( is_dir( $this->test_dir ) ) {
			Helper::delete( $this->test_dir );
		}
		parent::tear_down();
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
}
