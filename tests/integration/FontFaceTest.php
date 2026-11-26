<?php
/**
 * OMGF: Tests for the FontFace class.
 */

namespace OMGF\Tests\Integration;

use OMGF\FontFace;
use OMGF\Tests\TestCase;

class FontFaceTest extends TestCase {
	/**
	 * @see FontFace::mask_comments_and_strings()
	 */
	public function testMaskCommentsAndStrings() {
		$this->assertSame( 'a   b  ""  c', FontFace::mask_comments_and_strings( "a /* x */ b 'y;}' c" ) );
		$this->assertSame( "a   b 'y;}' c", FontFace::mask_comments_and_strings( "a /* x */ b 'y;}' c", false ) );
		// A comment inside a string is part of the string.
		$this->assertSame( '"/* x */"', FontFace::mask_comments_and_strings( '"/* x */"', false ) );
	}

	/**
	 * @see FontFace::descriptor()
	 */
	public function testDescriptor() {
		$this->assertSame( 'swap', FontFace::descriptor( '@font-face { font-display: swap; }', 'font-display' ) );
		$this->assertSame( 'swap', FontFace::descriptor( '@font-face { font-display:swap}', 'font-display' ) );
		// The last valid value is used.
		$this->assertSame(
			'b',
			FontFace::descriptor(
				'@font-face { x: b; x: invalid; }',
				'x',
				function ( $value ) {
					return $value === 'invalid' ? null : $value;
				}
			)
		);
		// Properties which end in the descriptor aren't.
		$this->assertNull( FontFace::descriptor( '@font-face { -x-font-display: swap; }', 'font-display' ) );
		$this->assertNull( FontFace::descriptor( '@font-face { /* font-display: swap; */ }', 'font-display' ) );
	}

	/**
	 * @see FontFace::family()
	 */
	public function testFamily() {
		$this->assertSame( 'Open Sans', FontFace::family( "@font-face { font-family: 'Open Sans'; }" ) );
		$this->assertSame( 'Roboto', FontFace::family( '@font-face { font-family:Roboto }' ) );
		$this->assertSame( 'A;B', FontFace::family( '@font-face { font-family: "A;B"; }' ) );
		$this->assertSame( 'Lato', FontFace::family( "@font-face { font-family: /* 'Inter' */ 'Lato'; }" ) );
		// A descriptor's name inside a string isn't a descriptor.
		$this->assertSame( 'Lato', FontFace::family( '@font-face { font-family: Lato; src: local("font-family: Fake;"), url(a.woff2); }' ) );
		$this->assertSame( 'Lato', FontFace::family( '@font-face { font-family: Lato; src: url(https://example.com/font-family:Fake;v=1.woff2); }' ) );
		$this->assertSame( 'Lato', FontFace::family( '@font-face { font-family: Lato; --note: font-family: Fake; }' ) );
		$this->assertSame( 'Lato', FontFace::family( 'font-family:Lato' ) );
		$this->assertSame( '', FontFace::family( '@font-face { font-weight: 400; }' ) );
	}

	/**
	 * @see FontFace::weight()
	 */
	public function testWeight() {
		$this->assertSame( '700', FontFace::weight( '@font-face { font-weight: 700; }' ) );
		$this->assertSame( '300 800', FontFace::weight( "@font-face { font-weight: 300\t 800; }" ) );
		$this->assertSame( '400 700', FontFace::weight( '@font-face { font-weight: Normal bold; }' ) );
		$this->assertSame( '500', FontFace::weight( '@font-face { font-weight: 500; font-weight: heavy; }' ) );
		$this->assertSame( '400', FontFace::weight( '@font-face { font-weight: "700"; }' ) );
		$this->assertSame( '400', FontFace::weight( '@font-face { font-weight: 100 200 300; }' ) );
		$this->assertSame( '1000', FontFace::weight( '@font-face { font-weight: 1000; font-weight: 1001; font-weight: 0; }' ) );
		$this->assertSame( '700 450.5', FontFace::weight( '@font-face { font-weight: +7e2 450.50; }' ) );
		$this->assertSame( '400', FontFace::weight( '@font-face { font-family: A; }' ) );
	}

	/**
	 * @see FontFace::style()
	 */
	public function testStyle() {
		$this->assertSame( 'italic', FontFace::style( '@font-face { font-style: Italic; }' ) );
		$this->assertSame( 'oblique -10deg 20.5deg', FontFace::style( '@font-face { font-style: oblique  -10deg 20.5deg; }' ) );
		$this->assertSame( 'italic', FontFace::style( '@font-face { font-style: italic; font-style: slanted; }' ) );
		$this->assertSame( 'oblique 0.1turn', FontFace::style( '@font-face { font-style: oblique 0.1turn; }' ) );
		$this->assertSame( 'normal', FontFace::style( '@font-face { font-family: A; }' ) );
	}

	/**
	 * @see FontFace::src()
	 */
	public function testSrc() {
		$this->assertSame(
			'https://fonts.gstatic.com/a.woff2',
			FontFace::src( "@font-face { src: url('https://fonts.gstatic.com/a.woff2') format('woff2'), url(b.woff) format('woff'); }" )
		);
		$this->assertSame( 'a.ttf', FontFace::src( '@font-face { src: local("A"), URL( a.ttf ); }' ) );
		$this->assertSame( 'b.woff2', FontFace::src( '@font-face { src: url(a.woff2); src: url("b.woff2"); }' ) );
		$this->assertSame( '', FontFace::src( '@font-face { src: local(A); }' ) );
		// Semicolons in parentheses don't end the value, and strings aren't URLs.
		$this->assertSame( 'data:font/woff2;base64,AAAA', FontFace::src( '@font-face { src: url(data:font/woff2;base64,AAAA) format("woff2"); }' ) );
		$this->assertSame( '', FontFace::src( '@font-face { src: url(old.woff2); src: local(A); }' ) );
		$this->assertSame( 'real.woff2', FontFace::src( '@font-face { src: local("url(fake.woff2)"), url(real.woff2); }' ) );
	}
}
