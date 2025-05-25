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
		// We send over 1 URL, and it should be saved.
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

		// We send over no URLs and no URLs should be saved.
		try {
			add_filter( 'omgf_is_running_optimize', '__return_true' );

			$ajax = new Ajax();

			$ajax->get_admin_bar_status();

			$results = OMGF::get_option( Settings::OMGF_GOOGLE_FONTS_CHECKER_RESULTS );

			$this->assertArrayNotHasKey( 'https://fonts.googleapis.com/css?family=Roboto:400,700', $results );
		} finally {
			remove_filter( 'omgf_is_running_optimize', '__return_true' );

			unset( $_POST[ 'urls' ] );
		}

		// We send over 6 URLs, but only 5 should be saved.
		try {
			add_filter( 'omgf_is_running_optimize', '__return_true' );

			$_POST[ 'urls' ] = [
				'https://fonts.googleapis.com/css?family=Test',
				'https://fonts.googleapis.com/css?family=Test2',
				'https://fonts.googleapis.com/css?family=Test3',
				'https://fonts.googleapis.com/css?family=Test4',
				'https://fonts.googleapis.com/css?family=Test5',
				'https://fonts.googleapis.com/css?family=Test6',
			];

			$ajax = new Ajax();

			$ajax->get_admin_bar_status();

			$results = OMGF::get_option( Settings::OMGF_GOOGLE_FONTS_CHECKER_RESULTS );

			$this->assertCount( 5, $results );
		} finally {
			remove_filter( 'omgf_is_running_optimize', '__return_true' );

			unset( $_POST[ '_wpnonce' ] );
			unset( $_POST[ 'path' ] );
			unset( $_POST[ 'urls' ] );

			OMGF::delete_option( Settings::OMGF_GOOGLE_FONTS_CHECKER_RESULTS );
		}
	}
}
