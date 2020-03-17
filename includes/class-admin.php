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

class OMGF_Admin
{
    const OMGF_ADMIN_JS_HANDLE  = 'omgf-admin-js';
    const OMGF_ADMIN_CSS_HANDLE = 'omgf-admin-css';

    /** @var QM_DB|wpdb $wpdb */
    private $wpdb;

    public function __construct()
    {
        global $wpdb;

        $this->wpdb = $wpdb;
        // @formatter:off
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('admin_notices', array($this, 'table_exists'));
        add_action('admin_notices', array($this, 'add_notice'));
        // @formatter:on
    }

    /**
     * Enqueues the necessary JS and CSS and passes options as a JS object.
     *
     * @param $hook
     */
    public function enqueue_admin_scripts($hook)
    {
        if ($hook == 'settings_page_optimize-webfonts') {
            wp_enqueue_script(self::OMGF_ADMIN_JS_HANDLE, plugin_dir_url(OMGF_PLUGIN_FILE) . 'js/hwl-admin.js', array('jquery'), OMGF_STATIC_VERSION, true);
            wp_enqueue_style(self::OMGF_ADMIN_CSS_HANDLE, plugin_dir_url(OMGF_PLUGIN_FILE) . 'css/hwl-admin.css', array(), OMGF_STATIC_VERSION);

            $options = array(
                'auto_detect_enabled' => OMGF_AUTO_DETECT_ENABLED,
                'detected_fonts'      => get_option('omgf_detected_fonts')
            );

            wp_localize_script(self::OMGF_ADMIN_JS_HANDLE, 'omgf', $options);
        }
    }

    /**
     * Check if main OMGF table exists on page load in Admin screen.
     */
    public function table_exists()
    {
        $table = OMGF_DB_TABLENAME;

        if (!$this->wpdb->get_var($this->wpdb->prepare("SHOW TABLES LIKE %s", $table))): ?>
            <div id="message" class="notice notice-error is-dismissible">
                <p><?php _e("Table $table does not exist and is necessary for OMGF to function properly. Try manually de-activating and activating the plugin via the WordPress plugins screen.", 'host-webfonts-local'); ?></p>
            </div>
            <?php
            update_option(OMGF_Admin_Settings::OMGF_SETTING_DB_VERSION, null);

            /**
             * Backwards compatibility
             *
             * @since v2.2.2
             */
            update_option('caos_webfonts_db_version', null);
            ?>
        <?php endif;
    }

    /**
     * @param $name
     *
     * @return mixed
     */
    protected function get_template($name)
    {
        return include OMGF_PLUGIN_DIR . 'templates/admin/block-' . $name . '.phtml';
    }

    /**
     * Add notice to admin screen.
     */
    public function add_notice()
    {
        OMGF_Admin_Notice::print_notice();
    }
}
