<?php
/**
 * @package OMGF integration tests - Optimize
 */

namespace OMGF\Tests\Integration\Admin;

use OMGF\Admin\Optimize;
use OMGF\Admin\Settings;
use OMGF\Tests\TestCase;

class OptimizeTest extends TestCase {
	/** @var int */
	private $requests = 0;

	public function set_up() {
		parent::set_up();

		$this->requests = 0;

		$_GET['page']             = Settings::OMGF_ADMIN_PAGE;
		$_GET['tab']              = Settings::OMGF_SETTINGS_FIELD_OPTIMIZE;
		$_GET['settings-updated'] = 'true';

		add_filter( 'pre_http_request', [ $this, 'countRequest' ], 9 );
	}

	public function tear_down() {
		remove_filter( 'pre_http_request', [ $this, 'countRequest' ], 9 );
		remove_filter( 'user_has_cap', [ $this, 'addManageOptionsCap' ] );

		unset( $_GET['page'], $_GET['tab'], $_GET['settings-updated'], $_GET['_wpnonce'] );

		parent::tear_down();
	}

	public function countRequest() {
		$this->requests ++;

		return [
			'response' => [ 'code' => 200 ],
			'body'     => '<html></html>',
		];
	}

	/**
	 * @see Optimize::init()
	 */
	public function testRunsWhenAuthorized() {
		add_filter( 'user_has_cap', [ $this, 'addManageOptionsCap' ] );
		$_GET['_wpnonce'] = wp_create_nonce( Optimize::NONCE_ACTION );

		new Optimize();

		$this->assertSame( 1, $this->requests );
	}

	/**
	 * @see Optimize::init()
	 */
	public function testDoesNotRunWithoutNonce() {
		add_filter( 'user_has_cap', [ $this, 'addManageOptionsCap' ] );

		new Optimize();

		$this->assertSame( 0, $this->requests );
	}

	/**
	 * @see Optimize::init()
	 */
	public function testDoesNotRunWithInvalidNonce() {
		add_filter( 'user_has_cap', [ $this, 'addManageOptionsCap' ] );
		$_GET['_wpnonce'] = wp_create_nonce( 'some-other-action' );

		new Optimize();

		$this->assertSame( 0, $this->requests );
	}

	/**
	 * @see Optimize::init()
	 */
	public function testDoesNotRunWithoutCapability() {
		$_GET['_wpnonce'] = wp_create_nonce( Optimize::NONCE_ACTION );

		new Optimize();

		$this->assertSame( 0, $this->requests );
	}
}
