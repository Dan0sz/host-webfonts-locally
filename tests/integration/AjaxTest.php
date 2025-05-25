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
		try {
			$_REQUEST[ '_wpnonce' ] = wp_create_nonce( 'omgf_frontend_nonce' );
			$_POST[ 'path' ]        = '/test';
			$_POST[ 'urls' ]        = [ 'https://fonts.googleapis.com/css?family=Roboto:400,700' ];
			$ajax                   = new Ajax();
			$ajax->get_admin_bar_status();

			$results = OMGF::get_option( Settings::OMGF_GOOGLE_FONTS_CHECKER_RESULTS );

			$this->assertArrayHasKey( 'https://fonts.googleapis.com/css?family=Roboto:400,700', $results );
		} finally {
			$_POST[ 'urls' ] = [];
		}

		try {
			$_REQUEST[ '_wpnonce' ] = wp_create_nonce( 'omgf_frontend_nonce' );
			$_POST[ 'path' ]        = '/test';
			$_POST[ 'urls' ]        = [];

			var_dump( $_POST );

			$ajax = new Ajax();
			$ajax->get_admin_bar_status();

			$results = OMGF::get_option( Settings::OMGF_GOOGLE_FONTS_CHECKER_RESULTS );

			$this->assertArrayNotHasKey( 'https://fonts.googleapis.com/css?family=Roboto:400,700', $results );
		} finally {
			unset( $_POST[ '_wpnonce' ] );
			unset( $_POST[ 'path' ] );
			unset( $_POST[ 'urls' ] );

			OMGF::delete_option( Settings::OMGF_GOOGLE_FONTS_CHECKER_RESULTS );
		}

	}
}
