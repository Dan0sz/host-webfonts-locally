<?php
/* * * * * * * * * * * * * * * * * * * * *
 *
 *  ██████╗ ███╗   ███╗ ██████╗ ███████╗
 * ██╔═══██╗████╗ ████║██╔════╝ ██╔════╝
 * ██║   ██║██╔████╔██║██║  ███╗█████╗
 * ██║   ██║██║╚██╔╝██║██║   ██║██╔══╝
 * ╚██████╔╝██║ ╚═╝ ██║╚██████╔╝██║
 *  ╚═════╝ ╚═╝     ╚═╝ ╚═════╝ ╚═╝
 *
 * @package  : OMGF
 * @author   : Daan van den Bergh
 * @copyright: © 2026 Daan van den Bergh
 * @url      : https://daan.dev
 * * * * * * * * * * * * * * * * * * * */

namespace OMGF;

/**
 * Scheduled events.
 *
 * @since v6.5.0
 */
class Cron {
	/**
	 * Daily removal of stylesheets which weren't found on the site for a while.
	 *
	 * @see Helper::remove_unused_stylesheets()
	 */
	const REMOVE_UNUSED_STYLESHEETS = 'omgf_remove_unused_stylesheets';

	/**
	 * Build class.
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Action/filter hooks.
	 *
	 * @return void
	 */
	private function init() {
		add_action( self::REMOVE_UNUSED_STYLESHEETS, [ '\OMGF\Helper', 'remove_unused_stylesheets' ] );

		/**
		 * Events are scheduled on activation. This makes sure they're scheduled for existing installs, too.
		 */
		add_action( 'admin_init', [ '\OMGF\Cron', 'schedule' ] );
	}

	/**
	 * @return void
	 */
	public static function schedule() {
		if ( ! wp_next_scheduled( self::REMOVE_UNUSED_STYLESHEETS ) ) {
			wp_schedule_event( time(), 'daily', self::REMOVE_UNUSED_STYLESHEETS );
		}
	}

	/**
	 * @return void
	 */
	public static function unschedule() {
		wp_clear_scheduled_hook( self::REMOVE_UNUSED_STYLESHEETS );
	}
}
