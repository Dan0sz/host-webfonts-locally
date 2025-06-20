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

		add_filter( 'stylesheet', [ $this, 'setMesmerizeTheme' ] );

		// When Mesmerize is the active theme.
		$html = $class->remove_mesmerize_filter( $test_html );

		$this->assertStringNotContainsString( 'data-href', $html );

		remove_filter( 'stylesheet', [ $this, 'setMesmerizeTheme' ] );

		$html = $class->remove_mesmerize_filter( $test_html );

		// When any other theme is active theme.
		$this->assertStringContainsString( 'data-href', $html );
	}

	public function setMesmerizeTheme() {
		return 'mesmerize';
	}
}
