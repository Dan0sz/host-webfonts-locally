<?php
/**
 * @package OMGF Integration Test - Settings
 */

namespace OMGF\Tests\Integration\Admin;

use OMGF\Admin\Settings;
use OMGF\Tests\TestCase;

class SettingsTest extends TestCase {
	/**
	 * @see Settings::create_menu()
	 * @return void
	 */
	public function testCreateMenu() {
		new Settings();

		add_filter( 'user_has_cap', [ $this, 'addManageOptionsCap' ], 10 );

		global $_parent_pages;

		do_action( 'admin_menu' );

		remove_filter( 'user_has_cap', [ $this, 'addManageOptionsCap' ] );

		$this->assertTrue( isset( $_parent_pages[ Settings::OMGF_ADMIN_PAGE ] ) );
	}

	/**
	 * Add manage_options cap.
	 *
	 * @param $allcaps
	 *
	 * @return true[]
	 */
	public function addManageOptionsCap( $allcaps ) {
		return array_merge( $allcaps, [ 'manage_options' => true ] );
	}

	/**
	 * @see Settings::register_settings()
	 * @return void
	 */
	public function testRegisterSettings() {
		global $wp_registered_settings;

		$class = new Settings();
		$class->register_settings();

		$this->assertNotEmpty( $wp_registered_settings[ 'omgf_settings[auto_subsets]' ] );

		$_GET[ 'tab' ] = Settings::OMGF_SETTINGS_FIELD_ADVANCED;

		$class = new Settings();
		$class->register_settings();

		$this->assertNotEmpty( $wp_registered_settings[ 'omgf_settings[compatibility]' ] );
	}

	/**
	 * @see Settings::generate_tab()
	 * @return void
	 */
	public function testTabs() {
		$_GET[ 'page' ] = Settings::OMGF_ADMIN_PAGE;

		new Settings();

		$this->expectOutputContains( Settings::OMGF_SETTINGS_FIELD_OPTIMIZE );
		$this->expectOutputContains( Settings::OMGF_OPTIONS_GENERAL_PAGE_OPTIMIZE_WEBFONTS );
		$this->expectOutputContains( Settings::OMGF_SETTINGS_FIELD_DETECTION );
		$this->expectOutputContains( Settings::OMGF_SETTINGS_FIELD_ADVANCED );

		do_action( 'omgf_settings_tab' );
	}

	/**
	 * @see Settings::do_settings_content()
	 * @return void
	 */
	public function testContent() {
		$tabs = [
			Settings::OMGF_SETTINGS_FIELD_OPTIMIZE  => new Settings\Optimize(),
			Settings::OMGF_SETTINGS_FIELD_DETECTION => new Settings\Detection(),
			Settings::OMGF_SETTINGS_FIELD_ADVANCED  => new Settings\Advanced(),
		];

		foreach ( $tabs as $tab => $class ) {
			$_GET[ 'tab' ] = $tab;

			new Settings();

			$this->expectOutputContains( str_replace( '-', '_', $tab ) );

			do_action( 'omgf_settings_content' );
		}
	}
}
