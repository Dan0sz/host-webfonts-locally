<?php
/**
 * OMGF - Compatibility - Divi
 */

namespace OMGF\Tests\Integration\Frontend\Compatibility;

use OMGF\Frontend\Compatibility\Divi;
use OMGF\Tests\TestCase;

class DiviTest extends TestCase {
	/**
	 * Different Google Fonts configurations get a different identifier, also when their URLs have the same length.
	 *
	 * @see Divi::maybe_modify_id()
	 * @return void
	 */
	public function testModifyIdIsUniquePerConfiguration() {
		$class = new Divi();
		$lato  = [ 'href' => 'https://fonts.googleapis.com/css?family=Lato:400|Nunito+Sans:400' ];
		$lora  = [ 'href' => 'https://fonts.googleapis.com/css?family=Lora:400|Nunito+Sans:400' ];

		try {
			$id = $class->maybe_modify_id( 'et-builder-googlefonts', $lato );

			$this->assertMatchesRegularExpression( '/^et-builder-googlefonts-[0-9a-f]{8}$/', $id );
			$this->assertSame( $id, $class->maybe_modify_id( 'et-builder-googlefonts', $lato ) );
			$this->assertNotSame( $id, $class->maybe_modify_id( 'et-builder-googlefonts', $lora ) );
			// Line breaks and tabs don't change the requested URL, so they don't change the identifier either.
			$this->assertSame( $id, $class->maybe_modify_id( 'et-builder-googlefonts', [ 'href' => "https://fonts.googleapis.com/css?family=Lato:400|\n\tNunito+Sans:400\n" ] ) );
			// A version bump doesn't change the identifier.
			$this->assertSame( $id, $class->maybe_modify_id( 'et-builder-googlefonts', [ 'href' => $lato['href'] . '&#038;ver=4.27.4' ] ) );
			// Other stylesheets are left alone.
			$this->assertSame( 'roboto', $class->maybe_modify_id( 'roboto', $lato ) );
		} finally {
			remove_filter( 'omgf_frontend_process_fonts_set', [ $class, 'maybe_modify_id' ] );
			remove_filter( 'omgf_frontend_process_legacy_ids', [ $class, 'add_legacy_ids' ] );
		}
	}

	/**
	 * The handles used before v6.4.2 are returned, so their settings can be inherited.
	 *
	 * @see Divi::add_legacy_ids()
	 * @return void
	 */
	public function testAddLegacyIds() {
		$class = new Divi();
		$lato  = [ 'href' => 'https://fonts.googleapis.com/css?family=Lato:400|Nunito+Sans:400' ];

		try {
			$this->assertSame(
				[
					'et-builder-googlefonts-' . strlen( $lato['href'] ),
					'et-builder-googlefonts-' . substr( md5( $lato['href'] ), 0, 8 ),
					'et-builder-googlefonts',
				],
				$class->add_legacy_ids( [], 'et-builder-googlefonts', $lato )
			);
			$this->assertSame( [], $class->add_legacy_ids( [], 'roboto', $lato ) );
		} finally {
			remove_filter( 'omgf_frontend_process_fonts_set', [ $class, 'maybe_modify_id' ] );
			remove_filter( 'omgf_frontend_process_legacy_ids', [ $class, 'add_legacy_ids' ] );
		}
	}
}
