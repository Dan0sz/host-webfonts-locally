<?php
/**
 * Filters Tests
 * @package OMGF
 * @author  Daan van den Bergh
 */

namespace OMGF\Tests\Integration\Frontend;

use OMGF\Frontend\Filters;
use OMGF\Tests\TestCase;

class FiltersTest extends TestCase {
	/**
	 * Are encoded URLs properly decoded?
	 * @return void
	 */
	public function testDecodeUrl() {
		new Filters();

		$optimize_url = apply_filters( 'omgf_optimize_url', 'https://fonts.googleapis.com?family=Roboto:100,200,300|Lato:100italic,200,300,500&amp;subset=latin' );

		$this->assertEquals( $optimize_url, 'https://fonts.googleapis.com?family=Roboto:100,200,300|Lato:100italic,200,300,500&subset=latin' );
	}

	/**
	 * @return void
	 */
	public function testInvalidUrl() {
		new Filters();

		$url = apply_filters( 'omgf_optimize_url', 'https://fonts.googleapis.com?family=Roboto:100,400,700,&subset=,latin' );

		$this->assertEquals( $url, 'https://fonts.googleapis.com?family=Roboto:100,400,700&subset=latin' );
	}
}
