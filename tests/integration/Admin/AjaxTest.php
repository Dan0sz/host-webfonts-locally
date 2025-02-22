<?php
/**
 * @package OMGF integration tests - Ajax
 */

namespace OMGF\Tests\Integration\Admin;

use OMGF\Admin\Ajax;
use OMGF\Admin\Settings;
use OMGF\Helper as OMGF;
use OMGF\Tests\TestCase;

class AjaxTest extends TestCase {
	/**
	 * This method verifies that a specified stylesheet handle is correctly removed
	 * from the stored cache keys.
	 *
	 * @return void
	 */
	public function testRemoveStylesheetFromDb() {
		$class               = new Ajax();
		$nonce               = wp_create_nonce( Settings::OMGF_ADMIN_PAGE );
		$_REQUEST[ 'nonce' ] = $nonce;

		add_filter( 'omgf_setting_cache_keys', [ $this, 'addTestCacheKey' ] );
		add_filter( 'omgf_setting_preload_fonts', [ $this, 'addPreloadFonts' ] );
		add_filter( 'user_has_cap', [ $this, 'addManageOptionsCap' ], 10 );

		// Handle to remove.
		$_POST[ 'handle' ] = 'test_cache_key';

		$class->remove_stylesheet_from_db();

		remove_filter( 'omgf_setting_cache_keys', [ $this, 'addTestCacheKey' ] );
		remove_filter( 'omgf_setting_preload_fonts', [ $this, 'addPreloadFonts' ] );
		remove_filter( 'user_has_cap', [ $this, 'addManageOptionsCap' ], 10 );

		$cache_keys = OMGF::get_option( Settings::OMGF_OPTIMIZE_SETTING_CACHE_KEYS );
		$preloads   = OMGF::get_option( Settings::OMGF_OPTIMIZE_SETTING_PRELOAD_FONTS );

		OMGF::delete_option( Settings::OMGF_OPTIMIZE_SETTING_CACHE_KEYS );
		OMGF::delete_option( Settings::OMGF_OPTIMIZE_SETTING_OPTIMIZED_FONTS );
		OMGF::delete_option( Settings::OMGF_OPTIMIZE_SETTING_OPTIMIZED_FONTS_FRONTEND );
		OMGF::delete_option( Settings::OMGF_OPTIMIZE_SETTING_UNLOAD_FONTS );
		OMGF::delete_option( Settings::OMGF_OPTIMIZE_SETTING_UNLOAD_STYLESHEETS );
		OMGF::delete_option( Settings::OMGF_OPTIMIZE_SETTING_PRELOAD_FONTS );

		$this->assertStringNotContainsString( 'test_cache_key', $cache_keys );
		$this->assertEmpty( $preloads );
	}

	public function addPreloadFonts() {
		return unserialize(
			'a:1:{s:14:"test_cache_key";a:1:{s:15:"source-sans-pro";a:7:{s:9:"300italic";s:1:"0";s:9:"400italic";s:1:"0";i:300;s:1:"0";i:400;s:3:"400";i:600;s:3:"600";i:700;s:3:"700";i:900;s:1:"0";}}}'
		);
	}
}
