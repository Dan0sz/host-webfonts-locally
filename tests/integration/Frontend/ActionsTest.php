<?php
/**
 * Actions Tests
 * @package OMGF
 * @author  Daan van den Bergh
 */

namespace OMGF\Tests\Integration\Frontend;

use OMGF\Filters;
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

	/**
	 * @see Actions::maybe_add_frontend_assets()
	 *
	 *
	 * @return void
	 */
	public function testAddFrontendAssetsDefault() {
		global $current_user;

		$current_user = new \WP_User( 1 );
		$current_user->set_role( 'administrator' );

		$class = new Actions();

		$class->maybe_add_frontend_assets();

		$this->assertTrue( wp_script_is( 'omgf-frontend', 'enqueued' ) );
		$this->assertTrue( wp_style_is( 'omgf-frontend', 'enqueued' ) );

		wp_dequeue_script( 'omgf-frontend' );
		wp_dequeue_style( 'omgf-frontend' );

		$this->assertFalse( wp_script_is( 'omgf-frontend', 'enqueued' ) );
		$this->assertFalse( wp_style_is( 'omgf-frontend', 'enqueued' ) );
	}

	/**
	 * When Disable Quick Access is enabled, the frontend assets should not be enqueued.
	 *
	 * @return void
	 */
	public function testAddFrontendAssetsWithDisableQuickAccessEnabled() {
		global $current_user;

		$current_user = new \WP_User( 1 );
		$current_user->set_role( 'administrator' );

		$class = new Actions();

		add_filter( 'omgf_setting_disable_quick_access', '__return_true' );

		new Filters();

		$class->maybe_add_frontend_assets();

		remove_filter( 'omgf_setting_disable_quick_access', '__return_true' );

		$this->assertFalse( wp_script_is( 'omgf-frontend', 'enqueued' ) );
		$this->assertFalse( wp_style_is( 'omgf-frontend', 'enqueued' ) );

		wp_dequeue_script( 'omgf-frontend' );
		wp_dequeue_style( 'omgf-frontend' );

		$this->assertFalse( wp_script_is( 'omgf-frontend', 'enqueued' ) );
		$this->assertFalse( wp_style_is( 'omgf-frontend', 'enqueued' ) );
	}

	/**
	 * When Disable Quick Access and Run Google Fonts Checker in Background are both enabled,
	 * the frontend assets should still be enqueued.
	 *
	 * @return void
	 */
	public function testAddFrontendAssetsWithDisableQuickAccessAndGFCEnabled() {
		global $current_user;

		$current_user = new \WP_User( 1 );
		$current_user->set_role( 'administrator' );

		$class = new Actions();

		add_filter( 'omgf_setting_disable_quick_access', '__return_true' );

		new Filters();

		// OMGF Pro's Google Fonts Checker overwrites all other filters by running last.
		add_filter( 'omgf_do_not_load_frontend_assets', '__return_false', 11 );

		$class->maybe_add_frontend_assets();

		$this->assertTrue( wp_script_is( 'omgf-frontend', 'enqueued' ) );
		$this->assertTrue( wp_style_is( 'omgf-frontend', 'enqueued' ) );

		wp_dequeue_script( 'omgf-frontend' );
		wp_dequeue_style( 'omgf-frontend' );

		$this->assertFalse( wp_script_is( 'omgf-frontend', 'enqueued' ) );
		$this->assertFalse( wp_style_is( 'omgf-frontend', 'enqueued' ) );
	}
}
