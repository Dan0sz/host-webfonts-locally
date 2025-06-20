<?php
/**
 * OMGF - Compatibility - Visual Composer
 */

namespace OMGF\Tests\Integration\Compatibility;

use OMGF\Compatibility\VisualComposer;
use OMGF\Tests\TestCase;

class VisualComposerTest extends TestCase {
	/**
	 * @see Filters::parse_vc_grid_data()
	 * @return void
	 */
	public function testParseVcGridData() {
		new VisualComposer();

		$test_html = file_get_contents( OMGF_TESTS_ROOT . 'assets/google-fonts.html' );
		$html      = apply_filters( 'vc_get_vc_grid_data_response', $test_html );

		$this->assertStringNotContainsString( 'fonts.googleapis.com', $html );
	}
}
