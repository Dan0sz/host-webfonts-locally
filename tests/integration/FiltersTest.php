<?php
/**
 * @package OMGF Unit Tests - Filters
 */

namespace OMGF\Tests\Integration;

use OMGF\Admin\Settings;
use OMGF\Filters;
use OMGF\Helper;
use OMGF\Optimize;
use OMGF\Tests\TestCase;

class FiltersTest extends TestCase {
	/**
	 * @see Filters::force_ssl()
	 * @return void
	 */
	public function testForceSsl() {
		new Filters();

		$url = apply_filters( 'content_url', 'http://example.org/test' );

		$this->assertEquals( 'http://example.org/test', $url );

		$url = apply_filters( 'content_url', OMGF_UPLOAD_URL . '/test' );

		$this->assertEquals( '//example.org/wp-content/uploads/omgf/test', $url );
	}

	/**
	 * @see Filters::maybe_do_legacy_mode()
	 * @return void
	 */
	public function testLegacyMode() {
		new Filters();

		Helper::update_option( Settings::OMGF_ADV_SETTING_LEGACY_MODE, 'on' );

		$user_agent = apply_filters( 'omgf_optimize_user_agent', Optimize::USER_AGENT[ 'woff2' ] );

		Helper::update_option( Settings::OMGF_ADV_SETTING_LEGACY_MODE, '' );

		$this->assertEquals( Optimize::USER_AGENT_COMPATIBILITY[ 'woff2' ], $user_agent );
	}

	/**
	 * At the time of writing this test, the Legacy Mode User Agent no longer supports Variable Fonts for Open Sans, which I believe was the case
	 * before.
	 *
	 * If compatibility for our current user agent changes in the future, this test should capture it.
	 *
	 * @see Optimize::process()
	 * @return void
	 */
	public function testLegacyModeAgainstVariableFontsSupport() {
		Helper::update_option( Settings::OMGF_ADV_SETTING_LEGACY_MODE, 'on' );

		$class = new Optimize(
			'https://fonts.googleapis.com/css?family=Open+Sans:300,400,700', 'traditional-fonts', 'variable-fonts'
		);
		$class->process();

		$this->assertFileExists( OMGF_UPLOAD_DIR . '/traditional-fonts/open-sans-normal-latin-300.woff2' );

		Helper::update_option( Settings::OMGF_ADV_SETTING_LEGACY_MODE, '' );

		$class = new Optimize(
			'https://fonts.googleapis.com/css?family=Open+Sans:300,400,700', 'variable-fonts', 'variable-fonts'
		);
		$class->process();

		$this->assertFileExists( OMGF_UPLOAD_DIR . '/variable-fonts/open-sans-normal-latin.woff2' );
	}

	/**
	 * @see Filters::maybe_load_frontend_assets()
	 *
	 * @return void
	 */
	public function testDoLoadFrontendAssets() {
		add_filter( 'omgf_setting_disable_quick_access', '__return_true' );

		$filters = new Filters();
		$load    = $filters->maybe_load_frontend_assets( false );

		remove_filter( 'omgf_setting_disable_quick_access', '__return_true' );

		$this->assertTrue( $load );
	}

	/**
	 * @see Filters::maybe_load_frontend_assets()
	 *
	 * @return void
	 */
	public function testDoNotLoadFrontendAssets() {
		remove_filter( 'omgf_setting_disable_quick_access', '__return_true' );

		$filters = new Filters();
		$load    = $filters->maybe_load_frontend_assets( false );

		$this->assertFalse( $load );
	}
}
