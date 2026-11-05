<?php
/**
 * @package OMGF integration tests - Optimize Settings
 */

namespace OMGF\Tests\Integration\Admin\Settings;

use OMGF\Admin\Settings;
use OMGF\Admin\Settings\Optimize;
use OMGF\Tests\TestCase;

class OptimizeTest extends TestCase {
	/**
	 * Superseded stylesheets aren't listed, because their settings were copied to their new handle.
	 *
	 * @see Optimize::do_optimize_fonts_contents()
	 * @return void
	 */
	public function testSupersededStylesheetsArentListed() {
		$fonts = function () {
			$font = (object) [ 'id' => 'lato', 'family' => 'Lato', 'variants' => [] ];

			return [ 'legacy' => [ 'lato' => $font ], 'new' => [ 'lato' => $font ] ];
		};

		add_filter( 'omgf_filter_admin_optimized_fonts', $fonts );
		update_option( Settings::OMGF_DB_SUPERSEDED_STYLESHEETS, [ 'legacy' ] );

		try {
			ob_start();
			( new Optimize() )->do_optimize_fonts_contents();
			$output = ob_get_clean();

			$this->assertStringContainsString( 'id="new"', $output );
			$this->assertStringNotContainsString( 'id="legacy"', $output );
		} finally {
			remove_filter( 'omgf_filter_admin_optimized_fonts', $fonts );
			delete_option( Settings::OMGF_DB_SUPERSEDED_STYLESHEETS );
		}
	}
}
