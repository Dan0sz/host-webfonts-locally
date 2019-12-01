<?php
/**
 * @package  : OMGF
 * @author   : Daan van den Bergh
 * @copyright: (c) 2019 Daan van den Bergh
 * @url      : https://daan.dev
 */

defined('ABSPATH') || exit;

class OMGF
{
    /**
     * OMGF constructor.
     */
    public function __construct()
    {
        $this->define_constants();

        if (is_admin()) {
            $this->do_setup();
            $this->do_settings();
            $this->add_ajax_hooks();
        }

        if (!is_admin()) {
            $this->do_frontend();
        }

        // @formatter:off
        register_activation_hook(OMGF_PLUGIN_FILE, array($this, 'create_cache_dir'));
        // @formatter:on
    }

    /**
     *
     */
    public function define_constants()
    {
        global $wpdb;

        define('OMGF_SITE_URL', 'https://daan.dev');
        define('OMGF_DB_TABLENAME', $wpdb->prefix . 'caos_webfonts');
        define('OMGF_DB_CHARSET', $wpdb->get_charset_collate());
        define('OMGF_HELPER_URL', 'https://google-webfonts-helper.herokuapp.com/api/fonts/');
        define('OMGF_FILENAME', 'fonts.css');
        define('OMGF_CACHE_DIR', esc_attr(get_option('caos_webfonts_cache_dir')) ?: '/cache/omgf-webfonts');
        define('OMGF_CDN_URL', esc_attr(get_option('caos_webfonts_cdn_url')));
        define('OMGF_WEB_FONT_LOADER', esc_attr(get_option('omgf_web_font_loader')));
        define('OMGF_REMOVE_VERSION', esc_attr(get_option('caos_webfonts_remove_version')));
        define('OMGF_CURRENT_BLOG_ID', get_current_blog_id());
        define('OMGF_UPLOAD_DIR', WP_CONTENT_DIR . OMGF_CACHE_DIR);
        define('OMGF_UPLOAD_URL', $this->get_upload_url());
        define('OMGF_DISPLAY_OPTION', esc_attr(get_option('caos_webfonts_display_option')) ?: 'auto');
        define('OMGF_REMOVE_GFONTS', esc_attr(get_option('caos_webfonts_remove_gfonts')));
        define('OMGF_PRELOAD', esc_attr(get_option('caos_webfonts_preload')));
    }

    /**
     * @return OMGF_Setup
     */
    private function do_setup()
    {
        return new OMGF_Setup();
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
     * @return OMGF_Frontend_Functions
     */
    private function do_frontend()
    {
        return new OMGF_Frontend_Functions();
    }

    /**
     * Create cache dir upon plugin (re-)activation.
     */
    public function create_cache_dir()
    {
        $uploadDir = OMGF_UPLOAD_DIR;
        if (!is_dir($uploadDir)) {
            wp_mkdir_p($uploadDir);
        }
    }

    /**
     * Returns the configured name of WordPress' content directory.
     *
     * @return mixed
     */
    public function get_content_dir()
    {
        preg_match('/[^\/]+$/u', WP_CONTENT_DIR, $match);

        return $match[0];
    }

    /**
     * @return string
     */
    public function get_upload_url()
    {
        if (OMGF_CDN_URL) {
            $uploadUrl = '//' . OMGF_CDN_URL . '/' . $this->get_content_dir() . OMGF_CACHE_DIR;
        } else {
            $uploadUrl = get_site_url(OMGF_CURRENT_BLOG_ID, $this->get_content_dir() . OMGF_CACHE_DIR);
        }

        return $uploadUrl;
    }
}
