<?php
/**
 * @package OMGF integration tests - Admin
 */

namespace OMGF\Tests\Integration\API;

use OMGF\Admin\Settings;
use OMGF\API\AdminbarMenu;
use OMGF\Helper as OMGF;
use OMGF\Tests\TestCase;

class AdminbarMenuTest extends TestCase {
	/**
	 * @see AdminbarMenu::get_admin_bar_status()
	 * @return void
	 */
	public function testGetAdminBarStatus() {
		// We send over 1 URL, and it should be saved.
		$request = new \WP_REST_Request( 'POST', '/omgf/v1/adminbar-menu/status' );

		try {
			$request->set_param( 'path', '/test' );
			$request->set_param( 'urls', [ 'https://fonts.googleapis.com/css?family=Roboto:400,700' ] );
			$api = new AdminbarMenu();

			$api->get_admin_bar_status( $request );

			$results = OMGF::get_option( Settings::OMGF_GOOGLE_FONTS_CHECKER_RESULTS );

			$this->assertArrayHasKey( 'https://fonts.googleapis.com/css?family=Roboto:400,700', $results );
		} finally {
			$request->set_param( 'urls', [] );
		}

		// We send over no URLs and no URLs should be saved.
		try {
			add_filter( 'omgf_is_running_optimize', '__return_true' );

			$api = new AdminbarMenu();

			$api->get_admin_bar_status( $request );

			$results = OMGF::get_option( Settings::OMGF_GOOGLE_FONTS_CHECKER_RESULTS );

			$this->assertArrayNotHasKey( 'https://fonts.googleapis.com/css?family=Roboto:400,700', $results );
		} finally {
			remove_filter( 'omgf_is_running_optimize', '__return_true' );
		}

		// We send over 6 URLs, but only 5 should be saved.
		try {
			add_filter( 'omgf_is_running_optimize', '__return_true' );

			$request->set_param(
				'urls',
				[
					'https://fonts.googleapis.com/css?family=Test',
					'https://fonts.googleapis.com/css?family=Test2',
					'https://fonts.googleapis.com/css?family=Test3',
					'https://fonts.googleapis.com/css?family=Test4',
					'https://fonts.googleapis.com/css?family=Test5',
					'https://fonts.googleapis.com/css?family=Test6',
				]
			);

			$api = new AdminbarMenu();

			$api->get_admin_bar_status( $request );

			$results = OMGF::get_option( Settings::OMGF_GOOGLE_FONTS_CHECKER_RESULTS );

			$this->assertCount( 5, $results );
		} finally {
			remove_filter( 'omgf_is_running_optimize', '__return_true' );

			OMGF::delete_option( Settings::OMGF_GOOGLE_FONTS_CHECKER_RESULTS );
		}
	}
}
