<?php
/**
 * @package OMGF Unit Tests - AdminbarMenu
 */

namespace OMGF\Tests\Unit;

use Brain\Monkey\Expectation\Exception\ExpectationArgsRequired;
use OMGF\Admin\Dashboard;
use OMGF\API\AdminbarMenu;
use OMGF\Tests\TestCase;
use Brain\Monkey\Functions;
use Brain\Monkey\Filters;

class AdminbarMenuTest extends TestCase {
	public function setUp(): void {
		parent::setUp();
		Functions\when( 'wp_parse_url' )->alias( 'parse_url' );
		Functions\when( 'wp_unslash' )->alias( function ( $value ) {
			return is_string( $value ) ? stripslashes( $value ) : $value;
		} );
		Functions\when( 'esc_url_raw' )->alias( function ( $url ) {
			return $url;
		} );
		Functions\when( 'sanitize_text_field' )->alias( function ( $text ) {
			return $text;
		} );
		Functions\when( '__' )->alias( function ( $text ) {
			return $text;
		} );
		Functions\when( 'is_multisite' )->justReturn( false );
		Functions\when( 'wp_get_theme' )->justReturn( (object) [ 'template' => 'twentytwenty' ] );
		Functions\when( 'get_plugins' )->justReturn( [] );
		Functions\when( 'get_option' )->alias( function ( $option, $default = false ) {
			if ( $option === 'home' || $option === 'siteurl' ) {
				return 'https://example.com';
			}

			return $default;
		} );
	}

	/**
	 * @return void
	 * @throws ExpectationArgsRequired
	 */
	public function testMultilingualPluginDetection() {
		foreach ( array_keys( Dashboard::MULTILANG_PLUGINS ) as $path ) {
			Functions\when( 'is_plugin_active' )->alias( function ( $plugin_path ) use ( $path ) {
				return $plugin_path === $path;
			} );

			$adminbar_menu = new AdminbarMenu();
			$request       = \Mockery::mock( \WP_REST_Request::class );
			$request->shouldReceive( 'get_params' )->andReturn( [
				'path' => '/',
				'urls' => '[]',
			] );

			Functions\expect( 'update_option' )->andReturn( true );
			Functions\expect( 'current_user_can' )->with( 'manage_options' )->andReturn( true );

			$response = $adminbar_menu->get_admin_bar_status( $request );

			$this->assertEquals( 'notice', $response['status'] );
			$this->assertArrayNotHasKey( 'notice', $response );
		}
	}

	/**
	 * @return void
	 */
	public function testMissingPreloadsNotice() {
		Functions\expect( 'is_plugin_active' )->andReturn( false );
		Functions\expect( 'update_option' )->times( 2 )->andReturn( true );

		$adminbar_menu = new AdminbarMenu();
		$request       = \Mockery::mock( \WP_REST_Request::class );
		$request->shouldReceive( 'get_params' )->andReturn( [
			'path'             => '/',
			'urls'             => '[]',
			'missing_preloads' => json_encode( [ 'Open Sans' ] ),
		] );

		$response = $adminbar_menu->get_admin_bar_status( $request );

		$this->assertEquals( 'info', $response['status'] );
	}

	/**
	 * @return void
	 */
	public function testUnusedFontsNotice() {
		Functions\expect( 'is_plugin_active' )->andReturn( false );
		Functions\expect( 'update_option' )->times( 2 )->andReturn( true );

		$adminbar_menu = new AdminbarMenu();
		$request       = \Mockery::mock( \WP_REST_Request::class );
		$request->shouldReceive( 'get_params' )->andReturn( [
			'path'         => '/',
			'urls'         => '[]',
			'unused_fonts' => json_encode( [ 'Lato' ] ),
		] );

		$response = $adminbar_menu->get_admin_bar_status( $request );

		$this->assertEquals( 'info', $response['status'] );
	}

	/**
	 * @return void
	 */
	public function testNoNoticesWhenConditionsNotMet() {
		Functions\expect( 'is_plugin_active' )->andReturn( false );
		Functions\expect( 'update_option' )->andReturn( true );

		$adminbar_menu = new AdminbarMenu();
		$request       = \Mockery::mock( \WP_REST_Request::class );
		$request->shouldReceive( 'get_params' )->andReturn( [
			'path' => '/',
			'urls' => '[]',
		] );

		$response = $adminbar_menu->get_admin_bar_status( $request );

		$this->assertEquals( 'success', $response['status'] );
	}
}
