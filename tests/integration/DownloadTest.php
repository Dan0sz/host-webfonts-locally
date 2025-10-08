<?php
/**
 * @package OMGF Pro - Download Tests
 */

namespace OMGF\Tests\Integration;

use OMGF\Download;
use OMGF\Tests\TestCase;

class DownloadTest extends TestCase {
	/**
	 * Is the test file properly downloaded?
	 * @return void
	 */
	public function testDownload() {
		$class = new Download(
			'https://fonts.googleapis.com/family?Not+Found', 'failed-request', OMGF_UPLOAD_DIR . '/failed-request'
		);
		$file  = $class->download();

		$this->assertEquals( '', $file );

		$class = new Download(
			'https://fonts.gstatic.com/s/roboto/v30/KFOmCnqEu92Fr1Mu72xKOzY.woff2', 'roboto-400-latin-test', OMGF_UPLOAD_DIR . '/download-test'
		);
		$file  = $class->download();

		$this->assertEquals( '//example.org/wp-content/uploads/omgf/download-test/roboto-400-latin-test.woff2', $file );
	}
}
