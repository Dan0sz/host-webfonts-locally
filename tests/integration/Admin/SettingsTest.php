<?php
/**
 * @package OMGF Integration Test - Settings
 */

namespace OMGF\Tests\Integration\Admin;

use OMGF\Admin\ImportExport;
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
	 * @see Settings::register_settings()
	 * @return void
	 */
	public function testRegisterSettings() {
		global $wp_registered_settings;

		$class = new Settings();
		$class->register_settings();

		$this->assertNotEmpty( $wp_registered_settings[ 'omgf_settings[display_option]' ] );

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
		$this->expectOutputContains( Settings::OMGF_SETTINGS_FIELD_ADVANCED );

		do_action( 'omgf_settings_tab' );
	}

	/**
	 * The Import/Export section is only rendered on the Help tab, outside its settings form.
	 *
	 * @see Settings::import_export_content()
	 * @return void
	 */
	public function testImportExportContent() {
		$_GET['page'] = Settings::OMGF_ADMIN_PAGE;
		$_GET['tab']  = Settings::OMGF_SETTINGS_FIELD_HELP;

		new Settings();

		ob_start();
		do_action( 'omgf_settings_content' );
		$html = ob_get_clean();

		unset( $_GET['page'], $_GET['tab'] );

		$this->assertStringContainsString( 'name="action" value="' . ImportExport::ACTION_EXPORT . '"', $html );
		$this->assertStringContainsString( 'name="action" value="' . ImportExport::ACTION_IMPORT . '"', $html );
		$this->assertStringContainsString( 'enctype="multipart/form-data"', $html );
		$this->assertStringContainsString( 'name="' . ImportExport::FIELD_INCLUDE_FONTS . '"', $html );
		// The section starts after the Help tab's form is closed, i.e. forms aren't nested.
		$this->assertGreaterThan( strpos( $html, '</form>' ), strpos( $html, 'id="omgf-import-export"' ) );

		$settings = new Settings();

		ob_start();
		$settings->import_export_content();
		$this->assertSame( '', ob_get_clean() );
	}
}
