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
 * @copyright: (c) 2021 Daan van den Bergh
 * @url      : https://daan.dev
 * * * * * * * * * * * * * * * * * * * */

defined('ABSPATH') || exit;

class OMGF_Admin_Notice
{
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
	public static function set_notice($message, $message_id = '', $die = true, $type = 'success', $code = 200, $screen_id = 'all')
	{
		self::$notices = get_transient(self::OMGF_ADMIN_NOTICE_TRANSIENT);

		if (!self::$notices) {
			self::$notices = [];
		}

		self::$notices[$screen_id][$type][$message_id] = $message;

		set_transient(self::OMGF_ADMIN_NOTICE_TRANSIENT, self::$notices, self::OMGF_ADMIN_NOTICE_EXPIRATION);

		if ($die) {
			switch ($type) {
				case 'error':
					wp_send_json_error($message, $code);
					break;
				default:
					wp_send_json_success($message, $code);
			}
		}
	}

	/**
	 * @param string $message_id
	 * @param string $type
	 * @param string $screen_id
	 */
	public static function unset_notice($message_id = '', $type = 'info', $screen_id = 'all')
	{
		self::$notices = get_transient(self::OMGF_ADMIN_NOTICE_TRANSIENT);

		if (isset(self::$notices[$screen_id][$type][$message_id])) {
			unset(self::$notices[$screen_id][$type][$message_id]);
		}

		if (is_array(self::$notices) && empty(self::$notices[$screen_id][$type])) {
			unset(self::$notices[$screen_id][$type]);
		}

		set_transient(self::OMGF_ADMIN_NOTICE_TRANSIENT, self::$notices, self::OMGF_ADMIN_NOTICE_EXPIRATION);
	}

	/**
	 * Prints notice (if any) grouped by type.
	 */
	public static function print_notices()
	{
		$admin_notices = get_transient(self::OMGF_ADMIN_NOTICE_TRANSIENT);

		if (is_array($admin_notices)) {
			$current_screen = get_current_screen();

			foreach ($admin_notices as $screen => $notice) {
				if ($current_screen->id != $screen && $screen != 'all') {
					continue;
				}

				foreach ($notice as $type => $message) {
?>
					<div id="message" class="notice notice-<?php echo $type; ?> is-dismissible">
						<?php foreach ($message as $line) : ?>
							<p><?= $line; ?></p>
						<?php endforeach; ?>
					</div>
<?php
				}
			}
		}

		delete_transient(self::OMGF_ADMIN_NOTICE_TRANSIENT);
	}
}
