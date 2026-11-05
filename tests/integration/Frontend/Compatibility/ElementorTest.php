<?php
/**
 * OMGF - Compatibility - Elementor
 */

namespace OMGF\Tests\Integration\Frontend\Compatibility;

use OMGF\Frontend\Compatibility\Elementor;
use OMGF\Tests\TestCase;

class ElementorTest extends TestCase {
	/**
	 * Elementor uses the same handle for all its Google Fonts stylesheets, so each configuration should get its own.
	 *
	 * @see Elementor::maybe_modify_id()
	 * @return void
	 */
	public function testModifyIdIsUniquePerConfiguration() {
		$class = new Elementor();
		$lato  = [ 'href' => 'https://fonts.googleapis.com/css?family=Lato:400' ];
		$lora  = [ 'href' => 'https://fonts.googleapis.com/css?family=Lora:400' ];

		try {
			$id = $class->maybe_modify_id( 'google-fonts-1', $lato );

			$this->assertMatchesRegularExpression( '/^google-fonts-[0-9a-f]{8}$/', $id );
			$this->assertSame( $id, $class->maybe_modify_id( 'google-fonts-1', $lato ) );
			$this->assertNotSame( $id, $class->maybe_modify_id( 'google-fonts-1', $lora ) );
			$this->assertSame( 'roboto', $class->maybe_modify_id( 'roboto', $lato ) );
			$this->assertSame(
				[ 'google-fonts-' . strlen( $lato['href'] ), 'google-fonts-1' ],
				$class->add_legacy_ids( [], 'google-fonts-1', $lato )
			);
			$this->assertSame( [], $class->add_legacy_ids( [], 'roboto', $lato ) );
		} finally {
			remove_filter( 'omgf_frontend_process_fonts_set', [ $class, 'maybe_modify_id' ] );
			remove_filter( 'omgf_frontend_process_legacy_ids', [ $class, 'add_legacy_ids' ] );
			remove_filter( 'omgf_frontend_process_parse_links', [ $class, 'validate_link_element' ] );
			remove_filter( 'omgf_frontend_process_invalid_request', [ $class, 'validate_request' ] );
		}
	}
}
