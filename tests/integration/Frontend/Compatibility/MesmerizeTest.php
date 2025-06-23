<?php
/**
 * OMGF - Compatibility - Mesmerize
 */

namespace OMGF\Tests\Integration\Frontend\Compatibility;

use OMGF\Frontend\Compatibility\Mesmerize;
use OMGF\Tests\TestCase;

class MesmerizeTest extends TestCase {
	/**
	 * @see Mesmerize::maybe_remove_data_attribute()
	 * @return void
	 */
	public function testRemoveMesmerizeFilter() {
		$class     = new Mesmerize();
		$test_html = file_get_contents( OMGF_TESTS_ROOT . 'assets/mesmerize.html' );

		switch_theme( 'mesmerize' );

		// When Mesmerize is the active theme.
		$html = $class->maybe_remove_data_attribute( $test_html );

		$this->assertStringNotContainsString( 'data-href', $html );

		switch_theme( 'twentytwenty' );

		// When any other theme is the active theme.
		$html = $class->maybe_remove_data_attribute( $test_html );

		$this->assertStringContainsString( 'data-href', $html );
	}
}
