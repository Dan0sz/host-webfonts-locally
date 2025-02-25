<?php
/**
 * @package OMGF integration tests - Admin
 */

namespace OMGF\Tests\Integration;

use OMGF\Admin\Settings;
use OMGF\Ajax;
use OMGF\Helper as OMGF;
use OMGF\Tests\TestCase;

class AjaxTest extends TestCase {
	/**
	 * @see Ajax::get_admin_bar_status()
	 * @return void
	 */
	public function testGetAdminBarStatus() {
		$_REQUEST[ '_wpnonce' ] = wp_create_nonce( 'omgf_frontend_nonce' );
		$_POST[ 'path' ]        = '/test';
		$_POST[ 'urls' ]        = [ 'https://fonts.googleapis.com/css?family=Roboto:400,700' ];

		$ajax = new Ajax();
		$ajax->get_admin_bar_status();

		$results = OMGF::get_option( Settings::OMGF_GOOGLE_FONTS_CHECKER_RESULTS );

		$this->assertArrayHasKey( '/test', $results );

		$_POST[ 'urls' ] = [];

		$ajax->get_admin_bar_status();

		$results = OMGF::get_option( Settings::OMGF_GOOGLE_FONTS_CHECKER_RESULTS );

		unset( $_POST[ '_wpnonce' ] );
		OMGF::delete_option( Settings::OMGF_GOOGLE_FONTS_CHECKER_RESULTS );

		$this->assertArrayNotHasKey( '/test', $results );
	}
}
