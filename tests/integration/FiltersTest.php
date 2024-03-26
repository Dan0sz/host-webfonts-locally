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

		$user_agent = apply_filters( 'omgf_optimize_user_agent', Optimize::USER_AGENT );

		Helper::update_option( Settings::OMGF_ADV_SETTING_LEGACY_MODE, '' );

		$this->assertEquals( Optimize::USER_AGENT_COMPATIBILITY, $user_agent );
	}

	/**
	 * @see Filters::parse_vc_grid_data()
	 * @return void
	 */
	public function testParseVcGridData() {
		new Filters();

		$test_html = file_get_contents( OMGF_TESTS_ROOT . 'assets/google-fonts.html' );
		$html      = apply_filters( 'vc_get_vc_grid_data_response', $test_html );

		$this->assertStringNotContainsString( 'fonts.googleapis.com', $html );
	}
}
