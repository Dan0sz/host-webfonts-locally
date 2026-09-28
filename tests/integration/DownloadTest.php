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

	/**
	 * @see Download::is_font_file()
	 * @return void
	 */
	public function testIsFontFile() {
		$file       = wp_tempnam( 'omgf-font-test' );
		$signatures = [
			'wOF2'             => true,
			'wOFF'             => true,
			'OTTO'             => true,
			"\x00\x01\x00\x00" => true,
			'true'             => true,
			'ttcf'             => true,
			'<?php'            => false,
			'<html>'           => false,
			'GIF89a'           => false,
		];

		try {
			foreach ( $signatures as $signature => $expected ) {
				file_put_contents( $file, $signature . str_repeat( "\x00", 64 ) );

				$this->assertSame( $expected, Download::is_font_file( $file ), bin2hex( $signature ) );
			}

			// EOT: magic number at offset 34.
			file_put_contents( $file, str_repeat( "\x00", 34 ) . 'LP' . str_repeat( "\x00", 64 ) );

			$this->assertTrue( Download::is_font_file( $file ) );
		} finally {
			unlink( $file );
		}
	}

	/**
	 * Downloaded files which aren't fonts, are empty or exceed the maximum file size aren't stored.
	 *
	 * @see Download::download()
	 * @return void
	 */
	public function testDownloadValidatesFile() {
		$path  = OMGF_UPLOAD_DIR . '/download-validation-test';
		$cases = [
			'not-a-font' => [ '<?php echo "not a font";', '' ],
			'empty'      => [ '', '' ],
			'too-large'  => [ 'wOF2' . str_repeat( "\x00", 100 ), '' ],
			'valid'      => [ 'wOF2' . str_repeat( "\x00", 10 ), '//example.org/wp-content/uploads/omgf/download-validation-test/valid.woff2' ],
		];

		$max_file_size = function () {
			return 50;
		};

		add_filter( 'omgf_download_max_file_size', $max_file_size );

		try {
			foreach ( $cases as $filename => $case ) {
				list( $body, $expected ) = $case;

				$mock = function ( $response, $args ) use ( $body ) {
					// Mimic the streaming transport, which writes the body to $args['filename'].
					file_put_contents( $args['filename'], $body );

					return [
						'headers'  => [ 'content-type' => 'font/woff2' ],
						'body'     => '',
						'response' => [ 'code' => 200, 'message' => 'OK' ],
						'cookies'  => [],
						'filename' => $args['filename'],
					];
				};

				add_filter( 'pre_http_request', $mock, 10, 2 );

				$file = ( new Download( 'https://fonts.gstatic.com/s/test/test.woff2', $filename, $path ) )->download();

				remove_filter( 'pre_http_request', $mock );

				$this->assertSame( $expected, $file, $filename );
				$this->assertSame( $expected !== '', file_exists( "$path/$filename.woff2" ), $filename );
				$this->assertEmpty( glob( "$path/*.tmp" ), $filename );
			}
		} finally {
			remove_filter( 'omgf_download_max_file_size', $max_file_size );
			array_map( 'unlink', glob( "$path/*" ) );
			rmdir( $path );
		}
	}
}
