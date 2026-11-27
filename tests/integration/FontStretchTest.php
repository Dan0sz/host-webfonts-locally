<?php
/**
 * OMGF: Tests for the FontStretch class.
 */

namespace OMGF\Tests\Integration;

use OMGF\FontStretch;
use OMGF\Tests\TestCase;

class FontStretchTest extends TestCase {
	/**
	 * @see FontStretch::find()
	 */
	public function testFind() {
		$this->assertSame( '75%', FontStretch::find( '@font-face { font-family: A; font-stretch: condensed; }' ) );
		$this->assertSame( '75% 125%', FontStretch::find( '@font-face { font-stretch: 75% 125%; }' ) );
		$this->assertSame( '', FontStretch::find( '@font-face { font-stretch: normal; }' ) );
		$this->assertSame( '', FontStretch::find( '@font-face { font-family: A; }' ) );
		// The last valid value is used.
		$this->assertSame( '87.5%', FontStretch::find( '@font-face { font-stretch: semi-condensed; font-stretch: wide; }' ) );
		// Comments and strings are ignored, and don't join the tokens around them.
		$this->assertSame( '', FontStretch::find( "@font-face { font-stretch: con/**/densed; src: url('font-stretch: expanded;'); }" ) );
		$this->assertSame( '75%', FontStretch::find( '@font-face { font-stretch: condensed; font-stretch: expanded "invalid"; font-stretch: "expanded"; }' ) );
		// Properties which end in font-stretch aren't.
		$this->assertSame( '', FontStretch::find( '@font-face { x-font-stretch: condensed; }' ) );
	}

	/**
	 * @see FontStretch::normalize()
	 */
	public function testNormalize() {
		$this->assertSame( '75%', FontStretch::normalize( '+075.0%' ) );
		$this->assertSame( '62.5% 125%', FontStretch::normalize( 'Extra-Condensed expanded' ) );
		$this->assertSame( '75%', FontStretch::normalize( '75% condensed' ) );
		$this->assertSame( '', FontStretch::normalize( '100%' ) );
		$this->assertSame( '', FontStretch::normalize( '-75%' ) );
		$this->assertSame( '', FontStretch::normalize( '50% 75% 100%' ) );
	}
}
