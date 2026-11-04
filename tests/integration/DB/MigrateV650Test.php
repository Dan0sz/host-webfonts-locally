<?php
/**
 * @package OMGF Integration Test - DB Migration V6.5.0
 */

namespace OMGF\Tests\Integration\DB;

use OMGF\Admin\Settings;
use OMGF\DB\Migrate\V650;
use OMGF\Tests\TestCase;

class MigrateV650Test extends TestCase {
	/**
	 * The option which kept track of when stylesheets were last seen is removed.
	 *
	 * @see V650::init()
	 * @return void
	 */
	public function testMigrationRemovesLastSeen() {
		$db_version = get_option( Settings::OMGF_CURRENT_DB_VERSION );

		try {
			update_option( 'omgf_stylesheets_last_seen', [ 'handle' => time() ] );

			new V650();

			$this->assertFalse( get_option( 'omgf_stylesheets_last_seen' ) );
			$this->assertSame( '6.5.0', get_option( Settings::OMGF_CURRENT_DB_VERSION ) );
		} finally {
			update_option( Settings::OMGF_CURRENT_DB_VERSION, $db_version );
		}
	}
}
