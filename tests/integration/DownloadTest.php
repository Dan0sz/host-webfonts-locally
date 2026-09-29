<?php
/**
 * @package OMGF Pro - Download Tests
 */

namespace OMGF\Tests\Integration;

use OMGF\Admin\Settings;
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
			// filename => [ body, expected return value, expected failure reason (or null) ].
			'not-a-font' => [ '<?php echo "not a font";', '', Download::FAILURE_INVALID ],
			'empty'      => [ '', '', Download::FAILURE_EMPTY ],
			'too-large'  => [ 'wOF2' . str_repeat( "\x00", 100 ), '', Download::FAILURE_TOO_LARGE ],
			'valid'      => [ 'wOF2' . str_repeat( "\x00", 10 ), '//example.org/wp-content/uploads/omgf/download-validation-test/valid.woff2', null ],
		];

		$max_file_size = function () {
			return 50;
		};

		add_filter( 'omgf_download_max_file_size', $max_file_size );

		try {
			foreach ( $cases as $filename => $case ) {
				list( $body, $expected, $reason ) = $case;

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

				$url  = "https://fonts.gstatic.com/s/test/$filename.woff2";
				$file = ( new Download( $url, $filename, $path ) )->download();

				remove_filter( 'pre_http_request', $mock );

				$this->assertSame( $expected, $file, $filename );
				$this->assertSame( $expected !== '', file_exists( "$path/$filename.woff2" ), $filename );
				$this->assertEmpty( glob( "$path/*.tmp" ), $filename );
				// Failed downloads are stored with their reason, to be reported on the Dashboard.
				$this->assertSame( $reason, Download::get_failures()[ $url ]['reason'] ?? null, $filename );
			}

			// A failed download which succeeds later on is removed.
			$retry = function ( $response, $args ) {
				file_put_contents( $args['filename'], 'wOF2' . str_repeat( "\x00", 10 ) );

				return [
					'headers'  => [ 'content-type' => 'font/woff2' ],
					'body'     => '',
					'response' => [ 'code' => 200, 'message' => 'OK' ],
					'cookies'  => [],
					'filename' => $args['filename'],
				];
			};

			add_filter( 'pre_http_request', $retry, 10, 2 );

			( new Download( 'https://fonts.gstatic.com/s/test/empty.woff2', 'empty', $path ) )->download();

			remove_filter( 'pre_http_request', $retry );

			$this->assertArrayNotHasKey( 'https://fonts.gstatic.com/s/test/empty.woff2', Download::get_failures() );
		} finally {
			remove_filter( 'omgf_download_max_file_size', $max_file_size );
			delete_option( Settings::OMGF_DB_DOWNLOAD_FAILURES );
			array_map( 'unlink', glob( "$path/*" ) );
			rmdir( $path );
		}
	}

	/**
	 * At most MAX_FAILURES failed downloads are kept, most recent first, each with a message.
	 *
	 * @see Download::add_failure()
	 * @see Download::get_failure_message()
	 * @return void
	 */
	public function testFailuresAreLimitedAndDescribed() {
		try {
			for ( $i = 0; $i < Download::MAX_FAILURES + 5; $i++ ) {
				Download::add_failure( "https://fonts.example/font-$i.woff2", Download::FAILURE_EMPTY );
			}

			$failures = Download::get_failures();

			$this->assertCount( Download::MAX_FAILURES, $failures );
			$this->assertSame( 'https://fonts.example/font-' . ( Download::MAX_FAILURES + 4 ) . '.woff2', array_key_first( $failures ) );

			// Invalid entries are ignored.
			update_option( Settings::OMGF_DB_DOWNLOAD_FAILURES, [ 'https://fonts.example/x.woff2' => [ 'reason' => '<script>' ], 0 => 'invalid' ], false );

			$this->assertSame( [], Download::get_failures() );

			foreach ( [ Download::FAILURE_EMPTY, Download::FAILURE_TOO_LARGE, Download::FAILURE_INVALID ] as $reason ) {
				$this->assertNotEmpty( Download::get_failure_message( $reason ) );
			}

			$this->assertStringContainsString( '25 MB', Download::get_failure_message( Download::FAILURE_TOO_LARGE ) );
		} finally {
			delete_option( Settings::OMGF_DB_DOWNLOAD_FAILURES );
		}
	}
}
