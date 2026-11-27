<?php
/**
 * OMGF: Tests for the UnicodeRanges class.
 */

namespace OMGF\Tests\Integration;

use OMGF\Tests\TestCase;
use OMGF\UnicodeRanges;

class UnicodeRangesTest extends TestCase {
	/**
	 * Ranges are normalized the way browsers report them: padded, uppercase, without spaces and wildcards.
	 *
	 * @see   UnicodeRanges::normalize()
	 * @see   UnicodeRanges::is_subset_range()
	 */
	public function testNormalize() {
		$this->assertSame( 'U+0400-04FF,U+0131', UnicodeRanges::normalize( 'u+400-4ff, U+131' ) );
		$this->assertSame( 'U+0400-04FF,U+1F000-1FFFF', UnicodeRanges::normalize( 'U+4??, u+1f???' ) );
		$this->assertTrue( UnicodeRanges::is_subset_range( UnicodeRanges::normalize( 'U+4??, u+1f???' ) ) );
		$this->assertFalse( UnicodeRanges::is_subset_range( UnicodeRanges::normalize( 'U+0-10FFFF' ) ) );
		$this->assertFalse( UnicodeRanges::is_subset_range( 'U+0400-04FF; } body {' ) );
	}

	/**
	 * @see UnicodeRanges::find()
	 */
	public function testFind() {
		$this->assertSame( 'U+0400-045F', UnicodeRanges::find( "@font-face { font-family: 'A'; unicode-range: U+0400-045F; }" ) );
		// The last one is used, and comments and strings are ignored.
		$this->assertSame( 'U+0100-024F', UnicodeRanges::find( "@font-face { unicode-range: U+0000-00FF; unicode-range: U+0100-024F /* unicode-range: U+0370-03FF; */; src: url('unicode-range: U+0370-03FF;'); }" ) );
		// Comments don't join the tokens around them, so this invalid value isn't read as U+1F00-1FFF.
		$this->assertSame( 'U+0-10FFFF', UnicodeRanges::find( '@font-face { unicode-range: U+0-10FFFF; unicode-range: U+1/**/F00-1FFF; }' ) );
		$this->assertSame( 'U+0-10FFFF', UnicodeRanges::find( '@font-face { unicode-range: U+0-10FFFF; unicode-range: U+1F00-1FFF "invalid"; }' ) );
		$this->assertSame( 'U+0400-045F, U+0490', UnicodeRanges::find( '@font-face { unicode-range: U+0400-045F, U+0490; unicode-range: latin; }' ) );
		// Properties which end in unicode-range aren't.
		$this->assertSame( '', UnicodeRanges::find( '@font-face { x-unicode-range: U+0000-00FF; }' ) );
		$this->assertSame( '', UnicodeRanges::find( '@font-face { font-family: A; }' ) );
	}

	/**
	 * @see UnicodeRanges::get_subset()
	 */
	public function testGetSubset() {
		$this->assertSame( 'latin', UnicodeRanges::get_subset( strtolower( str_replace( ',', ', ', UnicodeRanges::MAP['latin'] ) ) ) );
		$this->assertSame( 'U+4E00-4FFF', UnicodeRanges::get_subset( 'U+4E00-4FFF' ) );
		$this->assertSame( '', UnicodeRanges::get_subset( 'U+0-10FFFF' ) );
		$this->assertSame( '', UnicodeRanges::get_subset( '' ) );
	}
}
