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

    public function __construct()
    {
        // @formatter:off
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_js'));
        // @formatter:on
    }

    /**
     * Enqueues the necessary JS and CSS and passes options as a JS object.
     *
     * @param $hook
     */
    public function enqueue_admin_js($hook)
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

    protected function get_template($name)
    {
        return include OMGF_PLUGIN_DIR . 'templates/admin/block-' . $name . '.phtml';
    }
}
