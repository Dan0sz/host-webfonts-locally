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

	/**
	 * Weights and styles which share a file are listed in one row.
	 *
	 * @see Optimize::do_optimize_fonts_contents()
	 * @return void
	 */
	public function testVariableFontsAreListedPerFile() {
		$variants = [];
		$files    = [
			[ 'latin', '300', 'normal', 'a' ],
			[ 'latin-ext', '300', 'normal', 'b' ],
			[ 'latin', '700', 'normal', 'a' ],
			[ 'latin-ext', '700', 'normal', 'b' ],
			[ 'latin', '800', 'normal', 'a' ],
			[ 'latin', '800', 'italic', 'a' ],
			[ 'latin', '300', 'italic', 'c' ],
			[ 'latin', '900', 'normal', 'd' ],
			[ 'latin', '100 200', 'normal', 'e' ],
			// 500 links 2 files, e.g. of different widths.
			[ 'latin', '500', 'normal', 'f' ],
			[ 'latin', '600', 'normal', 'g' ],
			[ 'latin', '500', 'normal', 'g' ],
		];

		foreach ( $files as $file ) {
			$variants[] = (object) [
				'id'         => str_replace( ' ', '-', $file[1] ) . ( $file[2] === 'italic' ? 'italic' : '' ),
				'fontStyle'  => $file[2],
				'fontWeight' => $file[1],
				'woff2'      => "$file[3].woff2",
			];
		}

		$fonts    = function () use ( $variants ) {
			return [ 'variable' => [ 'lato' => (object) [ 'id' => 'lato', 'family' => 'Lato', 'variants' => $variants ] ] ];
		};
		$preloads = function () {
			return [ 'variable' => [ 'lato' => [ '700' => '700' ] ] ];
		};
		$unloads  = function () {
			return [ 'variable' => [ 'lato' => [ '800' => '800', '800italic' => '800italic' ] ] ];
		};

		add_filter( 'omgf_filter_admin_optimized_fonts', $fonts );
		add_filter( 'omgf_setting_preload_fonts', $preloads );
		add_filter( 'omgf_setting_unload_fonts', $unloads );

		try {
			ob_start();
			( new Optimize() )->do_optimize_fonts_contents();
			$output = ob_get_clean();
		} finally {
			remove_filter( 'omgf_filter_admin_optimized_fonts', $fonts );
			remove_filter( 'omgf_setting_preload_fonts', $preloads );
			remove_filter( 'omgf_setting_unload_fonts', $unloads );
		}

		// 300 + 700, 800 normal + italic (not loaded), 300 italic, 900, 100 - 200 and 500 + 600.
		$this->assertSame( 6, substr_count( $output, 'class="unload"' ) );
		$this->assertSame( 4, substr_count( $output, 'class="variable-font tooltip"' ) );
		$this->assertMatchesRegularExpression( '/<td>500, 600\s+<span class="variable-font tooltip">/', $output );
		$this->assertMatchesRegularExpression( '/<td>300, 700\s+<span class="variable-font tooltip">/', $output );
		$this->assertMatchesRegularExpression( '/<td>100 - 200\s+<span class="variable-font tooltip">/', $output );
		$this->assertMatchesRegularExpression( '/<td>900\s+<\/td>/', $output );
		$this->assertMatchesRegularExpression( '/<td>normal, italic<\/td>\s+<td>800\s+<span class="variable-font tooltip">/', $output );

		// Preloading one of its weights preloads the file.
		$this->assertMatchesRegularExpression( '/data-value="700"\s+name="omgf_preload_fonts\[variable\]\[lato\]\[700\]" value="700"/', $output );
		$this->assertMatchesRegularExpression( '/name="omgf_preload_fonts\[variable\]\[lato\]\[300\]"\s+value="300" checked/', $output );
		$this->assertMatchesRegularExpression( '/data-value="700"\s+name="omgf_unload_fonts\[variable\]\[lato\]\[700\]" value="0"/', $output );
		$this->assertMatchesRegularExpression( '/name="omgf_unload_fonts\[variable\]\[lato\]\[800\]"\s+value="800" checked/', $output );
		// Unloading the row selects both styles, but must not submit a preload for either.
		$this->assertMatchesRegularExpression( '/data-value="800italic"\s+name="omgf_unload_fonts\[variable\]\[lato\]\[800italic\]" value="800italic"/', $output );
		$this->assertMatchesRegularExpression( '/data-value="800italic"\s+name="omgf_preload_fonts\[variable\]\[lato\]\[800italic\]" value="0"/', $output );
		$this->assertMatchesRegularExpression( '/name="omgf_preload_fonts\[variable\]\[lato\]\[800\]"\s+value="800"\s+disabled/', $output );
	}
}
