<?php
/**
 * @package OMGF integration test - Notice
 */

namespace OMGF\Tests\Integration\Admin;

use OMGF\Admin\Notice;
use OMGF\Tests\TestCase;

class NoticeTest extends TestCase {
	/**
	 * @see Notice::set_notice()
	 * @return void
	 */
	public function testSetNotice() {
		Notice::set_notice( 'test', 'test-notice' );

		$this->expectOutputContains( 'test' );

		Notice::print_notices();

		Notice::set_notice( 'test', 'test-notice', 'info' );
		Notice::unset_notice( 'test-notice' );

		$notices = get_transient( Notice::OMGF_ADMIN_NOTICE_TRANSIENT );

		$this->assertEmpty( $notices[ 'all' ] );
	}
}
