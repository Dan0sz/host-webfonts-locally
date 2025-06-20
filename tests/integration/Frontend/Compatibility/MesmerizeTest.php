<?php
/**
 * OMGF - Compatibility - Mesmerize
 */

namespace OMGF\Tests\Integration\Frontend\Compatibility;

use OMGF\Frontend\Compatibility\Mesmerize;
use OMGF\Tests\TestCase;

class MesmerizeTest extends TestCase {
	/**
	 * @see Mesmerize::remove_mesmerize_filter()
	 * @return void
	 */
	public function testRemoveMesmerizeFilter() {
		$class     = new Mesmerize();
		$test_html = file_get_contents( OMGF_TESTS_ROOT . 'assets/mesmerize.html' );

		switch_theme( 'mesmerize' );

		// When Mesmerize is the active theme.
		$html = $class->remove_mesmerize_filter( $test_html );

		$this->assertStringNotContainsString( 'data-href', $html );

		switch_theme( 'twentytwenty' );

		// When any other theme is the active theme.
		$html = $class->remove_mesmerize_filter( $test_html );

		$this->assertStringContainsString( 'data-href', $html );
	}
}
