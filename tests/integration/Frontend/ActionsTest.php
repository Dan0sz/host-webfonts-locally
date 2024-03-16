<?php
/**
 * Actions Tests
 * @package OMGF
 * @author  Daan van den Bergh
 */

namespace OMGF\Tests\Integration\Frontend;

use OMGF\Frontend\Actions;
use OMGF\Tests\TestCase;

class ActionsTest extends TestCase {
	/**
	 * @see Actions::init_frontend()
	 * @return void
	 */
	public function testInitFrontend() {
		new Actions();

		do_action( 'init' );

		$this->assertTrue( class_exists( '\OMGF\Frontend\Process' ) );
	}

	/**
	 * @see Actions::add_admin_bar_item()
	 * @return void
	 */
	public function testAddAdminBarItem() {
		require_once( ABSPATH . 'wp-includes/class-wp-admin-bar.php' );

		$admin_bar = new \WP_Admin_Bar();
		$class     = new Actions();

		$class->add_admin_bar_item( $admin_bar );

		$nodes = $admin_bar->get_nodes();

		$this->assertCount( 3, $nodes );
	}
}
