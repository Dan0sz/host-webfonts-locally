<?php
/**
 * @package OMGF integration tests - Import/Export
 */

namespace OMGF\Tests\Integration\Admin;

use OMGF\Admin\ImportExport;
use OMGF\Admin\Notice;
use OMGF\Admin\Settings;
use OMGF\Helper as OMGF;
use OMGF\Tests\TestCase;

class ImportExportTest extends TestCase {
	/** @var array Options touched by these tests. */
	private $options = [
		'omgf_settings',
		'omgf_preload_fonts',
		'omgf_unload_fonts',
		'omgf_optimized_fonts',
		'omgf_optimized_fonts_frontend',
		'omgf_cache_is_stale',
		'ffwp_valid_licenses',
		'ffwp_license_key',
		'ffwp_encryption_cypher',
		Notice::OMGF_ADMIN_NOTICE_TRANSIENT,
	];

	public function set_up() {
		parent::set_up();

		$this->clean_up();

		add_filter( 'wp_die_handler', [ $this, 'getDieHandler' ] );
	}

	public function tear_down() {
		$this->clean_up();

		remove_filter( 'wp_die_handler', [ $this, 'getDieHandler' ] );
		remove_filter( 'user_has_cap', [ $this, 'addManageOptionsCap' ] );

		unset( $_REQUEST['_wpnonce'], $_POST['_wpnonce'], $_POST[ ImportExport::FIELD_INCLUDE_FONTS ], $_FILES[ ImportExport::FIELD_FILE ] );

		parent::tear_down();
	}

	private function clean_up() {
		foreach ( $this->options as $option ) {
			delete_option( $option );
		}

		delete_transient( Notice::OMGF_ADMIN_NOTICE_TRANSIENT );
		delete_transient( Settings::OMGF_NEWS_REEL );

		OMGF::reset_cache();
	}

	public function getDieHandler() {
		return function ( $message ) {
			throw new \RuntimeException( is_string( $message ) ? $message : 'wp_die' );
		};
	}

	/**
	 * Populate the DB with settings, fonts data and secrets that must never be exported.
	 */
	private function populate() {
		update_option(
			'omgf_settings',
			[
				'display_option'     => 'block',
				'test_mode'          => 'on',
				'subsets'            => [ 'latin', 'cyrillic' ],
				'cache_keys'         => 'roboto-mod-abc12',
				'unload_stylesheets' => 'some-handle',
				'not_a_setting'      => 'leftover-value',
			]
		);
		update_option( 'omgf_optimized_fonts', $this->getFonts(), false );
		update_option( 'omgf_optimized_fonts_frontend', $this->getFonts() );
		update_option( 'omgf_preload_fonts', [ 'roboto' => [ 'roboto' => [ '400' => '400' ] ] ] );
		update_option( 'omgf_unload_fonts', [ 'roboto' => [ 'roboto' => [ '700' => '700' ] ] ] );
		update_option( 'ffwp_valid_licenses', [ 4027 => [ 'key' => 'super-secret-license-key' ] ] );
		update_option( 'ffwp_license_key', 'another-secret-license-key' );
		update_option( 'ffwp_encryption_cypher', 'secret-cypher' );
		set_transient( Settings::OMGF_NEWS_REEL, 'cached-news-reel' );

		OMGF::reset_cache();
	}

	/**
	 * @return array
	 */
	private function getFonts() {
		$variant             = new \stdClass();
		$variant->id         = '400';
		$variant->fontFamily = 'Roboto%20Flex';
		$variant->fontStyle  = 'normal';
		$variant->fontWeight = '400';
		$variant->woff2      = '/wp-content/uploads/omgf/roboto-mod-abc12/roboto-normal-latin.woff2';
		$variant->subset     = 'latin';
		$variant->range      = 'U+0000-00FF, U+0131';

		$font           = new \stdClass();
		$font->id       = 'roboto';
		$font->family   = 'Roboto%20Flex';
		$font->variants = [ 'latin-400' => $variant ];
		$font->subsets  = [ 'latin', 'latin-ext' ];

		return [ 'roboto' => [ 'roboto' => $font ] ];
	}

	/**
	 * @return array A valid export, as decoded from JSON.
	 */
	private function getImport( $options ) {
		return [
			'plugin'  => 'omgf',
			'format'  => 1,
			'meta'    => [],
			'options' => $options,
		];
	}

	/**
	 * @see ImportExport::export()
	 */
	public function testExportExcludesFontsByDefault() {
		$this->populate();

		$export = ImportExport::export();

		$this->assertSame( 'omgf', $export['plugin'] );
		$this->assertSame( ImportExport::FORMAT_VERSION, $export['format'] );
		$this->assertFalse( $export['meta']['includes_fonts'] );
		$this->assertSame( get_site_url(), $export['meta']['site_url'] );
		$this->assertNotEmpty( $export['meta']['versions']['omgf'] );
		$this->assertSame( [ 'omgf_settings' ], array_keys( $export['options'] ) );
		$this->assertSame( 'block', $export['options']['omgf_settings']['display_option'] );
		$this->assertSame( [ 'latin', 'cyrillic' ], $export['options']['omgf_settings']['subsets'] );
		$this->assertArrayNotHasKey( 'cache_keys', $export['options']['omgf_settings'] );
		$this->assertArrayNotHasKey( 'unload_stylesheets', $export['options']['omgf_settings'] );
	}

	/**
	 * @see ImportExport::export()
	 */
	public function testExportIncludesFonts() {
		$this->populate();

		$export = ImportExport::export( true );

		$this->assertTrue( $export['meta']['includes_fonts'] );
		$this->assertEquals( $this->getFonts(), $export['options']['omgf_optimized_fonts'] );
		$this->assertEquals( $this->getFonts(), $export['options']['omgf_optimized_fonts_frontend'] );
		$this->assertSame( [ 'roboto' => [ 'roboto' => [ '400' => '400' ] ] ], $export['options']['omgf_preload_fonts'] );
		$this->assertSame( 'roboto-mod-abc12', $export['options']['omgf_settings']['cache_keys'] );
	}

	/**
	 * License keys, unknown settings, transients and caches are never exported.
	 *
	 * @see ImportExport::export_json()
	 */
	public function testExportNeverContainsSecrets() {
		$this->populate();

		$json = ImportExport::export_json( true );

		$this->assertStringNotContainsString( 'secret', $json );
		$this->assertStringNotContainsString( 'ffwp_', $json );
		$this->assertStringNotContainsString( 'cached-news-reel', $json );
		$this->assertStringNotContainsString( 'not_a_setting', $json );
		$this->assertStringNotContainsString( 'leftover-value', $json );
		$this->assertStringNotContainsString( 'nonce', $json );
	}

	/**
	 * Properties added by add-ons (e.g. additional_css) aren't exported, because an import rejects them.
	 *
	 * @see ImportExport::export()
	 */
	public function testExportDropsUnknownFontProperties() {
		$fonts                             = $this->getFonts();
		$fonts['roboto']['roboto']->additional_css = [ '.icon { color: red; }' ];

		update_option( 'omgf_optimized_fonts', $fonts );
		OMGF::reset_cache();

		$export = ImportExport::export( true );

		$this->assertFalse( property_exists( $export['options']['omgf_optimized_fonts']['roboto']['roboto'], 'additional_css' ) );
		$this->assertIsArray( ImportExport::parse( wp_json_encode( $export ) ) );
	}

	/**
	 * An invalid stored setting is left out of the export, instead of the entire settings row.
	 *
	 * @see ImportExport::export()
	 */
	public function testExportDropsInvalidSettings() {
		update_option( 'omgf_settings', [ 'display_option' => 'invalid', 'test_mode' => 'on' ] );
		OMGF::reset_cache();

		$settings = ImportExport::export()['options']['omgf_settings'];

		$this->assertArrayNotHasKey( 'display_option', $settings );
		$this->assertSame( 'on', $settings['test_mode'] );
	}

	/**
	 * @see ImportExport::parse()
	 * @see ImportExport::import()
	 */
	public function testRoundTrip() {
		$this->populate();

		$json = ImportExport::export_json( true );

		$this->clean_up();
		update_option( 'omgf_settings', [ 'debug' => 'on' ] );
		OMGF::reset_cache();

		$options = ImportExport::parse( $json );

		$this->assertIsArray( $options );

		ImportExport::import( $options );
		OMGF::reset_cache();

		$this->assertSame( 'block', OMGF::get_option( 'display_option' ) );
		$this->assertSame( [ 'latin', 'cyrillic' ], OMGF::get_option( 'subsets' ) );
		$this->assertSame( 'roboto-mod-abc12', OMGF::get_option( 'cache_keys' ) );
		$this->assertEquals( $this->getFonts(), get_option( 'omgf_optimized_fonts' ) );
		$this->assertEquals( $this->getFonts(), get_option( 'omgf_optimized_fonts_frontend' ) );
		$this->assertInstanceOf( \stdClass::class, get_option( 'omgf_optimized_fonts' )['roboto']['roboto']->variants['latin-400'] );
		$this->assertSame( [ 'roboto' => [ 'roboto' => [ '700' => '700' ] ] ], get_option( 'omgf_unload_fonts' ) );
		// The exported default for debug is empty, so it's overwritten.
		$this->assertSame( '', OMGF::get_option( 'debug' ) );
		$this->assertNotEmpty( get_option( Settings::OMGF_FLAG_CACHE_IS_STALE ) );
		$this->assertFalse( get_option( 'ffwp_license_key' ) );
	}

	/**
	 * Settings missing from the import, are left alone.
	 *
	 * @see ImportExport::import()
	 */
	public function testImportMergesSettings() {
		update_option( 'omgf_settings', [ 'debug' => 'on', 'display_option' => 'swap' ] );
		update_option( 'omgf_optimized_fonts', $this->getFonts() );
		OMGF::reset_cache();

		$options = ImportExport::validate( $this->getImport( [ 'omgf_settings' => [ 'display_option' => 'fallback' ] ] ) );

		ImportExport::import( $options );
		OMGF::reset_cache();

		$this->assertSame( 'on', OMGF::get_option( 'debug' ) );
		$this->assertSame( 'fallback', OMGF::get_option( 'display_option' ) );
		$this->assertEquals( $this->getFonts(), get_option( 'omgf_optimized_fonts' ) );
	}

	/**
	 * @see ImportExport::parse()
	 */
	public function testParseRejectsInvalidJson() {
		$this->assertWPError( ImportExport::parse( '' ), 'omgf_import_empty' );
		$this->assertWPError( ImportExport::parse( 'not json' ), 'omgf_import_invalid_json' );
		$this->assertWPError( ImportExport::parse( '"a string"' ), 'omgf_import_invalid_json' );
		$this->assertWPError( ImportExport::parse( serialize( $this->getImport( [] ) ) ), 'omgf_import_invalid_json' );
		$this->assertWPError( ImportExport::parse( str_repeat( ' ', ImportExport::MAX_FILE_SIZE + 1 ) ), 'omgf_import_too_large' );
		$this->assertWPError( ImportExport::parse( str_repeat( '[', 20 ) . str_repeat( ']', 20 ) ), 'omgf_import_invalid_json' );
	}

	/**
	 * @see ImportExport::validate()
	 */
	public function testValidateRejectsInvalidFormat() {
		$this->assertWPError( ImportExport::validate( [] ), 'omgf_import_invalid_format' );
		$this->assertWPError( ImportExport::validate( [ 'plugin' => 'other', 'format' => 1, 'options' => [] ] ), 'omgf_import_invalid_format' );
		$this->assertWPError( ImportExport::validate( [ 'plugin' => 'omgf', 'format' => 99, 'options' => [] ] ), 'omgf_import_invalid_format' );
		$this->assertWPError( ImportExport::validate( [ 'plugin' => 'omgf', 'format' => '1', 'options' => [] ] ), 'omgf_import_invalid_format' );
		$this->assertWPError( ImportExport::validate( [ 'plugin' => 'omgf', 'format' => 1, 'options' => 'string' ] ), 'omgf_import_invalid_format' );
		$this->assertSame( [], ImportExport::validate( $this->getImport( [] ) ) );
	}

	/**
	 * @see ImportExport::validate()
	 */
	public function testValidateRejectsUnknownKeys() {
		$this->assertWPError( ImportExport::validate( $this->getImport( [ 'ffwp_valid_licenses' => [] ] ) ), 'omgf_import_unknown_keys' );
		$this->assertWPError( ImportExport::validate( $this->getImport( [ 'siteurl' => 'https://evil.example' ] ) ), 'omgf_import_unknown_keys' );
		$this->assertWPError( ImportExport::validate( $this->getImport( [ 'omgf_cache_timestamp' => '1' ] ) ), 'omgf_import_unknown_keys' );
		$this->assertWPError( ImportExport::validate( $this->getImport( [ 'omgf_settings' => [ 'license_key' => 'x' ] ] ) ), 'omgf_import_unknown_keys' );

		// The error message is escaped, because it's shown in an admin notice.
		$error = ImportExport::validate( $this->getImport( [ '<script>alert(1)</script>' => 1 ] ) );

		$this->assertStringNotContainsString( '<script>', $error->get_error_message() );
	}

	/**
	 * @see ImportExport::validate()
	 */
	public function testValidateRejectsBadTypes() {
		$invalid = [
			[ 'omgf_settings' => 'string' ],
			[ 'omgf_settings' => [ 'display_option' => [ 'swap' ] ] ],
			[ 'omgf_settings' => [ 'display_option' => 'invalid' ] ],
			[ 'omgf_settings' => [ 'subsets' => 'latin' ] ],
			[ 'omgf_settings' => [ 'subsets' => [ 'klingon' ] ] ],
			[ 'omgf_settings' => [ 'subsets' => [ [ 'latin' ] ] ] ],
			[ 'omgf_settings' => [ 'test_mode' => [ 'nested' => [ 'array' ] ] ] ],
			[ 'omgf_settings' => [ 'test_mode' => [ '0' ] ] ],
			[ 'omgf_preload_fonts' => 'on' ],
			[ 'omgf_preload_fonts' => [ 'handle' => 'on' ] ],
			[ 'omgf_preload_fonts' => [ 'handle' => [ 'font' => [ '400' => [ 'too-deep' ] ] ] ] ],
			[ 'omgf_preload_fonts' => [ '<handle>' => [ 'font' => [ '400' => '400' ] ] ] ],
			[ 'omgf_optimized_fonts' => 'O:8:"stdClass":0:{}' ],
		];

		foreach ( $invalid as $options ) {
			$this->assertWPError( ImportExport::validate( $this->getImport( $options ) ), 'omgf_import_invalid_value' );
		}
	}

	/**
	 * @see ImportExport::validate()
	 */
	public function testValidateRejectsBadFontStructure() {
		$font    = json_decode( wp_json_encode( $this->getFonts() ), true );
		$invalid = [];

		$broken                                = $font;
		$broken['roboto']['roboto']['variants'] = 'string';
		$invalid[]                             = $broken;

		$broken                                    = $font;
		$broken['roboto']['roboto']['additional_css'] = [ '} body { display: none; }' ];
		$invalid[]                                 = $broken;

		$broken                                                             = $font;
		$broken['roboto']['roboto']['variants']['latin-400']['fontFamily'] = "Roboto'; } body { display: none; } .x{";
		$invalid[]                                                          = $broken;

		$broken                                                        = $font;
		$broken['roboto']['roboto']['variants']['latin-400']['woff2'] = 'javascript:alert(1)';
		$invalid[]                                                     = $broken;

		$broken                                                        = $font;
		$broken['roboto']['roboto']['variants']['latin-400']['woff2'] = [ 'array' ];
		$invalid[]                                                     = $broken;

		$broken                                                         = $font;
		$broken['roboto']['roboto']['variants']['latin-400']['unknown'] = 'value';
		$invalid[]                                                      = $broken;

		$broken                               = $font;
		$broken['roboto']['roboto']['subsets'] = [ [ 'latin' ] ];
		$invalid[]                            = $broken;

		$invalid[] = [ 'roboto' => 'string' ];
		$invalid[] = [ 'roboto' => [ 'roboto' => 'O:8:"stdClass":0:{}' ] ];

		foreach ( $invalid as $fonts ) {
			$this->assertWPError(
				ImportExport::validate( $this->getImport( [ 'omgf_optimized_fonts' => $fonts ] ) ),
				'omgf_import_invalid_value'
			);
		}

		$valid = ImportExport::validate( $this->getImport( [ 'omgf_optimized_fonts' => $font ] ) );

		$this->assertEquals( $this->getFonts(), $valid['omgf_optimized_fonts'] );
	}

	/**
	 * @see ImportExport::validate()
	 */
	public function testValidateSanitizesValues() {
		$options = ImportExport::validate(
			$this->getImport(
				[
					'omgf_settings'      => [ 'test_mode' => '<b>on</b>', 'debug' => true, 'legacy' => null ],
					'omgf_preload_fonts' => [ 'handle' => [ 'font' => [ 400 => "<script>400</script>" ] ] ],
				]
			)
		);

		$this->assertSame( 'on', $options['omgf_settings']['test_mode'] );
		$this->assertSame( 'on', $options['omgf_settings']['debug'] );
		$this->assertSame( '', $options['omgf_settings']['legacy'] );
		$this->assertSame( '', $options['omgf_preload_fonts']['handle']['font'][400] );
	}

	/**
	 * @see ImportExport::read_upload()
	 */
	public function testReadUpload() {
		$file = wp_tempnam( 'omgf-import' );

		file_put_contents( $file, '{"plugin":"omgf"}' );

		$upload = [ 'name' => 'export.json', 'tmp_name' => $file, 'size' => filesize( $file ), 'error' => UPLOAD_ERR_OK ];

		$this->assertSame( '{"plugin":"omgf"}', ImportExport::read_upload( $upload, false ) );
		// Not an actual upload.
		$this->assertWPError( ImportExport::read_upload( $upload ), 'omgf_import_no_file' );
		$this->assertWPError( ImportExport::read_upload( null, false ), 'omgf_import_no_file' );
		$this->assertWPError( ImportExport::read_upload( array_merge( $upload, [ 'name' => 'export.php' ] ), false ), 'omgf_import_invalid_json' );
		$this->assertWPError( ImportExport::read_upload( array_merge( $upload, [ 'error' => UPLOAD_ERR_PARTIAL ] ), false ), 'omgf_import_upload_error' );
		$this->assertWPError( ImportExport::read_upload( array_merge( $upload, [ 'size' => ImportExport::MAX_FILE_SIZE + 1 ] ), false ), 'omgf_import_too_large' );

		unlink( $file );
	}

	/**
	 * @see ImportExport::handle_import()
	 */
	public function testHandleImportRequiresCapability() {
		$_REQUEST['_wpnonce'] = wp_create_nonce( ImportExport::ACTION_IMPORT );

		$this->expectException( \RuntimeException::class );

		( new ImportExport() )->handle_import();
	}

	/**
	 * @see ImportExport::handle_import()
	 */
	public function testHandleImportRequiresNonce() {
		add_filter( 'user_has_cap', [ $this, 'addManageOptionsCap' ] );

		$_REQUEST['_wpnonce'] = wp_create_nonce( 'some-other-action' );

		$this->expectException( \RuntimeException::class );

		( new ImportExport() )->handle_import();
	}

	/**
	 * @see ImportExport::handle_import()
	 */
	public function testHandleImportSetsErrorNotice() {
		add_filter( 'user_has_cap', [ $this, 'addManageOptionsCap' ] );

		$_REQUEST['_wpnonce'] = wp_create_nonce( ImportExport::ACTION_IMPORT );

		( new ImportExport() )->handle_import();

		$notices = get_transient( Notice::OMGF_ADMIN_NOTICE_TRANSIENT );

		$this->assertArrayHasKey( 'omgf-import-export', $notices['all']['error'] );
		$this->assertFalse( get_option( Settings::OMGF_FLAG_CACHE_IS_STALE ) );
	}

	/**
	 * @see ImportExport::handle_export()
	 */
	public function testHandleExportRequiresCapability() {
		$_REQUEST['_wpnonce'] = wp_create_nonce( ImportExport::ACTION_EXPORT );

		$this->expectException( \RuntimeException::class );

		( new ImportExport() )->handle_export();
	}

	/**
	 * @see ImportExport::handle_export()
	 */
	public function testHandleExport() {
		$this->populate();

		add_filter( 'user_has_cap', [ $this, 'addManageOptionsCap' ] );

		$_REQUEST['_wpnonce']                       = wp_create_nonce( ImportExport::ACTION_EXPORT );
		$_POST[ ImportExport::FIELD_INCLUDE_FONTS ] = '1';

		ob_start();
		( new ImportExport() )->handle_export();
		$json = ob_get_clean();

		$export = json_decode( $json, true );

		$this->assertTrue( $export['meta']['includes_fonts'] );
		$this->assertArrayHasKey( 'omgf_optimized_fonts', $export['options'] );
	}

	/**
	 * @param mixed  $actual
	 * @param string $code
	 */
	private function assertWPError( $actual, $code ) {
		$this->assertInstanceOf( \WP_Error::class, $actual );
		$this->assertSame( $code, $actual->get_error_code() );
	}
}
