<?php
/**
 * @package OMGF Unit Tests - Filters
 */

namespace OMGF\Tests\Unit;

use OMGF\Filters;
use OMGF\Tests\TestCase;

class FiltersTest extends TestCase {
	/**
	 * @return void
	 */
	public function testBase64DecodeOptimizeFonts() {
		$class = new Filters();
		$value = $class->base64_decode_optimized_fonts( 'testing' );

		$this->assertEquals( 'testing', $value );

		$value = $class->base64_decode_optimized_fonts( 'dGVzdGluZw==' );

		$this->assertEquals( 'testing', $value );
	}
}
