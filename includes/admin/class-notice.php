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
 * @copyright: (c) 2019 Daan van den Bergh
 * @url      : https://daan.dev
 * * * * * * * * * * * * * * * * * * * */

defined('ABSPATH') || exit;

class OMGF_Admin_Notice
{
    const OMGF_ADMIN_NOTICE_TRANSIENT  = 'omgf_admin_notice';
    const OMGF_ADMIN_NOTICE_EXPIRATION = 30;

    /** @var array $notices */
    public static $notices = [];

    /**
     * @param        $message
     * @param string $type (info|warning|error|success)
     * @param string $screen_id
     */
    public static function set_notice($message, $type = 'success', $screen_id = 'all')
    {
        self::$notices                    = get_transient(self::OMGF_ADMIN_NOTICE_TRANSIENT);
        self::$notices[$screen_id][$type] = $message;

        set_transient(self::OMGF_ADMIN_NOTICE_TRANSIENT, self::$notices, self::OMGF_ADMIN_NOTICE_EXPIRATION);
    }

    /**
     * Prints notice (if any)
     */
    public static function print_notice()
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
                        <p><?php _e($message, 'host-webfonts-local'); ?></p>
                    </div>
                    <?php
                }
            }
        }

        delete_transient(self::OMGF_ADMIN_NOTICE_TRANSIENT);
    }
}
