<?php
/**
 * @package OMGF integration tests - Cron
 */

namespace OMGF\Tests\Integration;

use OMGF\Cron;
use OMGF\Tests\TestCase;

class CronTest extends TestCase {
	/**
	 * The removal of unused stylesheets is scheduled daily on activation and in the admin, and unscheduled when OMGF
	 * is deactivated.
	 *
	 * @see Cron::schedule()
	 * @see Cron::unschedule()
	 * @return void
	 */
	public function testSchedule() {
		Cron::unschedule();

		try {
			Cron::schedule();

			$this->assertNotFalse( wp_next_scheduled( Cron::REMOVE_UNUSED_STYLESHEETS ) );
			$this->assertSame( 'daily', wp_get_schedule( Cron::REMOVE_UNUSED_STYLESHEETS ) );

			do_action( 'deactivate_' . plugin_basename( OMGF_PLUGIN_FILE ) );

			$this->assertFalse( wp_next_scheduled( Cron::REMOVE_UNUSED_STYLESHEETS ) );

			// Activating OMGF schedules it, too, so it doesn't depend on an admin request.
			do_action( 'activate_' . plugin_basename( OMGF_PLUGIN_FILE ) );

			$this->assertNotFalse( wp_next_scheduled( Cron::REMOVE_UNUSED_STYLESHEETS ) );
		} finally {
			Cron::unschedule();
		}
	}

	/**
	 * The event removes unused stylesheets, and it's scheduled in the admin for existing installs.
	 *
	 * @see Cron::init()
	 * @return void
	 */
	public function testInit() {
		new Cron();

		$this->assertNotFalse( has_action( Cron::REMOVE_UNUSED_STYLESHEETS, [ '\OMGF\Helper', 'remove_unused_stylesheets' ] ) );
		$this->assertNotFalse( has_action( 'admin_init', [ '\OMGF\Cron', 'schedule' ] ) );
	}
}
