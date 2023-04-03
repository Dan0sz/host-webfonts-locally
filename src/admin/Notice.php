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
* @copyright: © 2023 Daan van den Bergh
* @url      : https://daan.dev
* * * * * * * * * * * * * * * * * * * */

namespace OMGF\Admin;

defined( 'ABSPATH' ) || exit;

class Notice {

	const OMGF_ADMIN_NOTICE_TRANSIENT  = 'omgf_admin_notice';
	const OMGF_ADMIN_NOTICE_EXPIRATION = 60;

	/** @var array $notices */
	public static $notices = [];

	/**
	 * @param        $message
	 * @param string $type (info|warning|error|success)
	 * @param string $screen_id
	 * @param bool   $json
	 * @param int    $code
	 */
	public static function set_notice( $message, $message_id = '', $type = 'success', $code = 200, $screen_id = 'all' ) {
		self::$notices = get_transient( self::OMGF_ADMIN_NOTICE_TRANSIENT );

		if ( ! self::$notices ) {
			self::$notices = [];
		}

		self::$notices[ $screen_id ][ $type ][ $message_id ] = $message;

		set_transient( self::OMGF_ADMIN_NOTICE_TRANSIENT, self::$notices, self::OMGF_ADMIN_NOTICE_EXPIRATION );
	}

	/**
	 * @param string $message_id
	 * @param string $type
	 * @param string $screen_id
	 */
	public static function unset_notice( $message_id = '', $type = 'info', $screen_id = 'all' ) {
		self::$notices = get_transient( self::OMGF_ADMIN_NOTICE_TRANSIENT );

		if ( isset( self::$notices[ $screen_id ][ $type ][ $message_id ] ) ) {
			unset( self::$notices[ $screen_id ][ $type ][ $message_id ] );
		}

		if ( is_array( self::$notices ) && empty( self::$notices[ $screen_id ][ $type ] ) ) {
			unset( self::$notices[ $screen_id ][ $type ] );
		}

		set_transient( self::OMGF_ADMIN_NOTICE_TRANSIENT, self::$notices, self::OMGF_ADMIN_NOTICE_EXPIRATION );
	}

	/**
	 * Prints notice (if any) grouped by type.
	 */
	public static function print_notices() {
		$admin_notices = get_transient( self::OMGF_ADMIN_NOTICE_TRANSIENT );

		if ( is_array( $admin_notices ) ) {
			$current_screen = get_current_screen();

			foreach ( $admin_notices as $screen => $notice ) {
				if ( $current_screen->id != $screen && $screen != 'all' ) {
					continue;
				}

				foreach ( $notice as $type => $message ) {
					?>
					<div id="message" class="notice notice-<?php echo $type; ?> is-dismissible">
						<?php foreach ( $message as $line ) : ?>
							<p><strong><?php echo $line; ?></strong></p>
						<?php endforeach; ?>
					</div>
					<?php
				}
			}
		}

		delete_transient( self::OMGF_ADMIN_NOTICE_TRANSIENT );
	}
}
