<?php
/**
 * Optimize Test
 * @package OMGF
 * @author  Daan van den Bergh
 */

namespace OMGF\Tests\Integration;

use OMGF\Optimize;
use OMGF\Tests\TestCase;

class OptimizeTest extends TestCase {
	/**
	 * Test @see \OMGF\Optimize::process() with a CSS2 url with multiple font-families.
	 */
	public function testProcessWithCSS2() {
		$url    = 'https://fonts.googleapis.com/css2?family=Afacad:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500;1,600;1,700&display=swap&family=IBM+Plex+Sans:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;1,100;1,200;1,300;1,400;1,500;1,600;1,700&display=swap&family=Nunito+Sans:ital,wght@0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap&family=Open+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,300;1,400;1,500;1,600;1,700;1,800&display=swap&family=Readex+Pro:wght@200;300;400;500;600;700&display=swap';
		$handle = 'test-css2';
		$return = 'object';

		$class     = new Optimize( $url, $handle, $handle, $return );
		$processed = $class->process();

		$this->assertArrayHasKey( 'test-css2', $processed );
		$this->assertArrayHasKey( 'afacad', $processed[ 'test-css2' ] );
		$this->assertArrayHasKey( 'ibm-plex-sans', $processed[ 'test-css2' ] );
		$this->assertArrayHasKey( 'nunito-sans', $processed[ 'test-css2' ] );
		$this->assertArrayHasKey( 'open-sans', $processed[ 'test-css2' ] );
		$this->assertArrayHasKey( 'readex-pro', $processed[ 'test-css2' ] );
	}

	/**
	 * Test @see \OMGF\Optimize::process() with a CSS stylesheet containing several different src notations.
	 * @return void
	 */
	public function testProcessWithCrazySyntaxes() {
		$url    = 'https://daan.dev/tests/crazy-syntaxes.css';
		$handle = 'test-crazy-syntaxes';
		$return = 'object';

		$class     = new Optimize( $url, $handle, $handle, $return );
		$processed = $class->process();

		$this->assertArrayHasKey( 'test-crazy-syntaxes', $processed );
		$this->assertArrayHasKey( 'roboto', $processed[ 'test-crazy-syntaxes' ] );
		$this->assertArrayHasKey( 'open-sans', $processed[ 'test-crazy-syntaxes' ] );
		$this->assertArrayHasKey( 'lato', $processed[ 'test-crazy-syntaxes' ] );
		$this->assertArrayHasKey( 'montserrat', $processed[ 'test-crazy-syntaxes' ] );
		$this->assertArrayHasKey( 'source-sans-pro', $processed[ 'test-crazy-syntaxes' ] );
		$this->assertArrayHasKey( 'poppins', $processed[ 'test-crazy-syntaxes' ] );
		$this->assertArrayHasKey( 'merriweather', $processed[ 'test-crazy-syntaxes' ] );
		$this->assertArrayHasKey( 'nunito', $processed[ 'test-crazy-syntaxes' ] );
		$this->assertArrayHasKey( 'ubuntu', $processed[ 'test-crazy-syntaxes' ] );
		$this->assertArrayHasKey( 'quicksand', $processed[ 'test-crazy-syntaxes' ] );

		foreach ( $processed as $font_objects ) {
			foreach ( $font_objects as $font_object ) {
				$this->assertTrue( count( $font_object->variants ) > 0 );
			}
		}
	}
}
