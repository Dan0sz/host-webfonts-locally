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
			// Compatibility fixes are disabled by default.
			$this->assertSame( 'et-builder-googlefonts', $class->maybe_modify_id( 'et-builder-googlefonts', $lato ) );

			add_filter( 'omgf_setting_compatibility', [ $this, 'return_on' ] );

			$id = $class->maybe_modify_id( 'et-builder-googlefonts', $lato );

			$this->assertMatchesRegularExpression( '/^et-builder-googlefonts-[0-9a-f]{8}$/', $id );
			$this->assertSame( $id, $class->maybe_modify_id( 'et-builder-googlefonts', $lato ) );
			$this->assertNotSame( $id, $class->maybe_modify_id( 'et-builder-googlefonts', $lora ) );
			// Other stylesheets are left alone.
			$this->assertSame( 'roboto', $class->maybe_modify_id( 'roboto', $lato ) );
		} finally {
			remove_filter( 'omgf_setting_compatibility', [ $this, 'return_on' ] );
			remove_filter( 'omgf_frontend_process_fonts_set', [ $class, 'maybe_modify_id' ] );
		}
	}

	/**
	 * @return string
	 */
	public function return_on() {
		return 'on';
	}
}
