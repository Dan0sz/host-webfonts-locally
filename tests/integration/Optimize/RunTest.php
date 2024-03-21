<?php
/**
 * @package OMGF Integration Tests - Run
 */

namespace OMGF\Tests\Integration\Optimize;

use OMGF\Admin\Notice;
use OMGF\Optimize\Run;
use OMGF\Tests\TestCase;

class RunTest extends TestCase {
	/**
	 * @see Run::run()
	 * @return void
	 */
	public function testRun() {
		new Run();

		$transient = get_transient( Notice::OMGF_ADMIN_NOTICE_TRANSIENT );

		$this->assertNotEmpty( $transient[ 'all' ][ 'warning' ][ 'omgf-cache-notice' ] );
	}
}
