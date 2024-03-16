<?php
/**
 * Actions Tests
 * @package OMGF
 * @author  Daan van den Bergh
 */

namespace OMGF\Tests\Integration\Frontend;

use OMGF\Frontend\Process;
use OMGF\Tests\TestCase;

class ProcessTest extends TestCase {
	/**
	 * Are preloads output properly?
	 * @return void
	 */
	public function testAddPreloads() {
		add_filter( 'omgf_frontend_preloaded_fonts', [ $this, 'addPreloads' ] );
		add_filter( 'omgf_frontend_optimized_fonts', [ $this, 'addOptimizedFonts' ] );

		$class = new Process( true );

		$this->expectOutputContains(
			"<link id='omgf-preload-0' rel='preload' href='/wp-content/uploads/omgf/astra-google-fonts-mod-jdm02/jost-normal-latin-400.woff2' as='font' type='font/woff2' crossorigin />"
		);
		$class->add_preloads();

		remove_filter( 'omgf_frontend_preloaded_fonts', [ $this, 'addPreloads' ] );
		remove_filter( 'omgf_frontend_optimized_fonts', [ $this, 'addOptimizedFonts' ] );
	}

	/**
	 * @return array
	 */
	public function addPreloads() {
		return [
			'astra-google-fonts' => [
				'jost' => [
					400 => "400",
					500 => '0',
					600 => '600',
				],
			],
		];
	}

	/**
	 * @return mixed
	 */
	public function addOptimizedFonts() {
		return unserialize(
			'a:1:{s:18:"astra-google-fonts";a:1:{s:4:"jost";O:8:"stdClass":4:{s:2:"id";s:4:"jost";s:6:"family";s:4:"Jost";s:8:"variants";a:3:{s:9:"latin-400";O:8:"stdClass":7:{s:2:"id";s:3:"400";s:10:"fontFamily";s:4:"Jost";s:9:"fontStyle";s:6:"normal";s:10:"fontWeight";s:3:"400";s:5:"woff2";s:81:"/wp-content/uploads/omgf/astra-google-fonts-mod-jdm02/jost-normal-latin-400.woff2";s:6:"subset";s:5:"latin";s:5:"range";s:178:"U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+0304, U+0308, U+0329, U+2000-206F, U+2074, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD";}s:9:"latin-500";O:8:"stdClass":7:{s:2:"id";s:3:"500";s:10:"fontFamily";s:4:"Jost";s:9:"fontStyle";s:6:"normal";s:10:"fontWeight";s:3:"500";s:5:"woff2";s:78:"https://fonts.gstatic.com/s/jost/v15/92zPtBhPNqw79Ij1E865zBUv7myRJTVBNIg.woff2";s:6:"subset";s:5:"latin";s:5:"range";s:178:"U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+0304, U+0308, U+0329, U+2000-206F, U+2074, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD";}s:9:"latin-600";O:8:"stdClass":7:{s:2:"id";s:3:"600";s:10:"fontFamily";s:4:"Jost";s:9:"fontStyle";s:6:"normal";s:10:"fontWeight";s:3:"600";s:5:"woff2";s:81:"/wp-content/uploads/omgf/astra-google-fonts-mod-jdm02/jost-normal-latin-600.woff2";s:6:"subset";s:5:"latin";s:5:"range";s:178:"U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+0304, U+0308, U+0329, U+2000-206F, U+2074, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD";}}s:7:"subsets";a:3:{i:0;s:8:"cyrillic";i:1;s:9:"latin-ext";i:2;s:5:"latin";}}}}'
		);
	}

	/**
	 * Are resource hints properly removed from HTML?
	 * @return void
	 */
	public function testRemoveResourceHints() {
		$class = new Process( true );
		$test_html = file_get_contents( OMGF_TESTS_ROOT . 'assets/resource-hints.html' );

		$html = $class->remove_resource_hints( $test_html );

		$this->assertNotEquals( $test_html, $html );
		$this->assertStringNotContainsString( 'fonts.googleapis.com', $html );
		$this->assertStringNotContainsString( 'fonts.gstatic.com', $html );
		$this->assertStringNotContainsString( 'https://fonts.gstatic.com/s/inter', $html );
	}
}
