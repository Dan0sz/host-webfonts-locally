<?php
/**
 * @package OMGF Integration Test - Optimize
 */

namespace OMGF\Tests\Integration;

use OMGF\Admin\Optimize;
use OMGF\Admin\Settings;
use OMGF\Helper as OMGF;
use OMGF\Tests\TestCase;

class OptimizeTest extends TestCase {
	/**
	 * @see Optimize::init()
	 * @return void
	 */
	public function testInit() {
		$_GET[ 'page' ]             = Settings::OMGF_ADMIN_PAGE;
		$_GET[ 'settings-updated' ] = true;

		new Optimize();

		$this->assertTrue( OMGF::get_option( Settings::OMGF_OPTIMIZE_HAS_RUN ) );
	}
}
