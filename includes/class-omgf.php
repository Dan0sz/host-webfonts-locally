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
 * @copyright: (c) 2020 Daan van den Bergh
 * @url      : https://daan.dev
 * * * * * * * * * * * * * * * * * * * */

defined('ABSPATH') || exit;

class OMGF
{
    /** @var string */
    private $page = '';

    /**
     * OMGF constructor.
     */
    public function __construct()
    {
        $this->define_constants();
        $this->page = isset($_GET['page']) ?: '';

        if (is_admin()) {
            $this->do_settings();
            add_action('plugin_loaded', array($this, 'do_setup'));

            if ($this->page == 'optimize-webfonts') {
                $this->do_auto_detect();
                $this->add_ajax_hooks();
            }
        }

        if (!is_admin()) {
            $this->do_frontend();
        }

        // @formatter:off
        register_activation_hook(OMGF_PLUGIN_FILE, array($this, 'create_cache_dir'));
        register_deactivation_hook(OMGF_PLUGIN_FILE, array($this, 'dequeue_css_js'));
        // @formatter:on
    }

    /**
     * Define constants.
     */
    public function define_constants()
    {
        global $wpdb;

        define('OMGF_SITE_URL', 'https://daan.dev');
        define('OMGF_DB_TABLENAME', $wpdb->prefix . 'omgf_fonts'); // legacy
        define('OMGF_DB_CHARSET', $wpdb->get_charset_collate());
        define('OMGF_HELPER_URL', 'https://google-webfonts-helper.herokuapp.com/api/fonts/');
        define('OMGF_FILENAME', 'fonts.css');
        define('OMGF_AUTO_DETECT_ENABLED', esc_attr(get_option(OMGF_Admin_Settings::OMGF_SETTING_AUTO_DETECTION_ENABLED, false)));
        define('OMGF_CACHE_PATH', esc_attr(get_option(OMGF_Admin_Settings::OMGF_ADV_SETTING_CACHE_PATH)) ?: '/cache/omgf-webfonts');
        define('OMGF_CACHE_URI', esc_attr(get_option(OMGF_Admin_Settings::OMGF_ADV_SETTING_CACHE_URI)) ?: '');
        define('OMGF_RELATIVE_URL', esc_attr(get_option(OMGF_Admin_Settings::OMGF_ADV_SETTING_RELATIVE_URL)));
        define('OMGF_CDN_URL', esc_attr(get_option(OMGF_Admin_Settings::OMGF_ADV_SETTING_CDN_URL)));
        define('OMGF_WEB_FONT_LOADER', esc_attr(get_option(OMGF_Admin_Settings::OMGF_ADV_SETTING_WEB_FONT_LOADER)));
        define('OMGF_REMOVE_VERSION', esc_attr(get_option(OMGF_Admin_Settings::OMGF_ADV_SETTING_REMOVE_VERSION)));
        define('OMGF_CURRENT_BLOG_ID', get_current_blog_id());
        define('OMGF_FONTS_DIR', WP_CONTENT_DIR . OMGF_CACHE_PATH);
        define('OMGF_FONTS_URL', $this->get_fonts_url());
        define('OMGF_DISPLAY_OPTION', esc_attr(get_option(OMGF_Admin_Settings::OMGF_ADV_SETTING_DISPLAY_OPTION)) ?: 'auto');
        define('OMGF_REMOVE_GFONTS', esc_attr(get_option(OMGF_Admin_Settings::OMGF_ADV_SETTING_REMOVE_GOOGLE_FONTS)));
        define('OMGF_PRELOAD', esc_attr(get_option(OMGF_Admin_Settings::OMGF_ADV_SETTING_ENABLE_PRELOAD)));
        define('OMGF_ENQUEUE_ORDER', esc_attr(get_option(OMGF_Admin_Settings::OMGF_ADV_SETTING_ENQUEUE_ORDER, 10)));
        define('OMGF_OPTIMIZE_EDIT_ROLES', esc_attr(get_option(OMGF_Admin_Settings::OMGF_ADV_SETTING_OPTIMIZE_EDIT_ROLES, 'on')));
        define('OMGF_UNINSTALL', esc_attr(get_option(OMGF_Admin_Settings::OMGF_ADV_SETTING_UNINSTALL)));
    }

    /**
     * @return OMGF_Admin_Settings
     */
    private function do_settings()
    {
        return new OMGF_Admin_Settings();
    }

    /**
     * @return OMGF_AJAX
     */
    private function add_ajax_hooks()
    {
        return new OMGF_AJAX();
    }

    /**
     * @return OMGF_Admin_AutoDetect
     */
    private function do_auto_detect()
    {
        $fonts = json_decode(get_option(OMGF_Admin_Settings::OMGF_SETTING_DETECTED_FONTS));

        if (OMGF_AUTO_DETECT_ENABLED && $fonts) {
            return new OMGF_Admin_AutoDetect($fonts);
        }
    }

    /**
     * @return OMGF_Frontend_Functions
     */
    private function do_frontend()
    {
        return new OMGF_Frontend_Functions();
    }

    /**
     * @return OMGF_Setup
     */
    public function do_setup()
    {
        register_uninstall_hook(OMGF_PLUGIN_FILE, 'OMGF::do_uninstall');

        return new OMGF_Setup();
    }

    /**
     * Create cache dir upon plugin (re-)activation.
     */
    public function create_cache_dir()
    {
        $uploadDir = OMGF_FONTS_DIR;
        if (!is_dir($uploadDir)) {
            wp_mkdir_p($uploadDir);
        }
    }

    /**
     * Returns the configured name of WordPress' content directory.
     *
     * @return mixed
     */
    private function get_content_dir()
    {
        if (OMGF_CACHE_URI) {
            $match = array_filter(explode('/', OMGF_CACHE_URI));
            $match = array_values($match);
        } else {
            preg_match('/[^\/]+$/u', WP_CONTENT_DIR, $match);
        }

        return $match[0];
    }

    /**
     * @return string
     */
    public function get_fonts_url()
    {
        if (OMGF_RELATIVE_URL) {
            return '/' . $this->get_content_dir() . OMGF_CACHE_PATH;
        }

        if (OMGF_CDN_URL) {
            $uploadUrl = '//' . OMGF_CDN_URL . '/' . $this->get_content_dir() . OMGF_CACHE_PATH;
        } elseif (OMGF_CACHE_URI) {
            $uploadUrl = get_site_url(OMGF_CURRENT_BLOG_ID, OMGF_CACHE_URI);
        } else {
            $uploadUrl = get_site_url(OMGF_CURRENT_BLOG_ID, $this->get_content_dir() . OMGF_CACHE_PATH);
        }

        return $uploadUrl;
    }

    /**
     * Removes all static files upon plugin deactivation.
     */
    public function dequeue_css_js()
    {
        wp_dequeue_script(OMGF_Admin::OMGF_ADMIN_JS_HANDLE);
        wp_dequeue_style(OMGF_Admin::OMGF_ADMIN_CSS_HANDLE);
        wp_dequeue_style(OMGF_Frontend_Functions::OMGF_STYLE_HANDLE);
    }

    /**
     * @return OMGF_Uninstall
     * @throws ReflectionException
     */
    public static function do_uninstall()
    {
        return new OMGF_Uninstall();
    }
}
