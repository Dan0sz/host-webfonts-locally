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
            $this->add_ajax_hooks();

            add_action('plugin_loaded', array($this, 'do_setup'));
        }

        if (!is_admin()) {
            add_action('plugins_loaded', [$this, 'do_frontend']);
        }
	
	    add_action('rest_api_init', [$this, 'register_routes']);
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
        define('OMGF_FONT_PROCESSING', esc_attr(get_option(OMGF_Admin_Settings::OMGF_BASIC_SETTING_FONT_PROCESSING, 'replace')));
	    define('OMGF_DISPLAY_OPTION', esc_attr(get_option( OMGF_Admin_Settings::OMGF_BASIC_SETTING_DISPLAY_OPTION, 'swap')) ?: 'swap');
	    define('OMGF_OPTIMIZE_EDIT_ROLES', esc_attr(get_option( OMGF_Admin_Settings::OMGF_BASIC_SETTING_OPTIMIZE_EDIT_ROLES, 'on')));
	    define('OMGF_CACHE_PATH', esc_attr(get_option(OMGF_Admin_Settings::OMGF_BASIC_SETTING_CACHE_PATH)) ?: '/uploads/omgf');
	    define('OMGF_CACHE_URI', esc_attr(get_option(OMGF_Admin_Settings::OMGF_ADV_SETTING_CACHE_URI)) ?: '');
	    define('OMGF_FORCE_SSL', esc_attr(get_option(OMGF_Admin_Settings::OMGF_ADV_SETTING_FORCE_SSL)));
	    define('OMGF_RELATIVE_URL', esc_attr(get_option(OMGF_Admin_Settings::OMGF_ADV_SETTING_RELATIVE_URL)));
	    define('OMGF_CDN_URL', esc_attr(get_option(OMGF_Admin_Settings::OMGF_ADV_SETTING_CDN_URL)));
	    define('OMGF_WEB_FONT_LOADER', esc_attr(get_option(OMGF_Admin_Settings::OMGF_ADV_SETTING_WEB_FONT_LOADER)));
	    define('OMGF_REMOVE_VERSION', esc_attr(get_option(OMGF_Admin_Settings::OMGF_ADV_SETTING_REMOVE_VERSION)));
	    define('OMGF_CURRENT_BLOG_ID', get_current_blog_id());
	    define('OMGF_FONTS_DIR', WP_CONTENT_DIR . OMGF_CACHE_PATH);
	    define('OMGF_FONTS_URL', $this->get_fonts_url());
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
     * @return OMGF_Setup
     */
    public function do_setup()
    {
        register_uninstall_hook(OMGF_PLUGIN_FILE, 'OMGF::do_uninstall');

        return new OMGF_Setup();
    }

    /**
     * @return OMGF_Frontend_Functions
     */
    public function do_frontend()
    {
        return new OMGF_Frontend_Functions();
    }
	
	/**
	 * Register CAOS Proxy so endpoint can be used.
	 * For using Stealth mode, SSL is required.
	 */
	public function register_routes()
	{
		$proxy = new OMGF_API_Download();
		$proxy->register_routes();
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

        if (OMGF_FORCE_SSL) {
            $uploadUrl = str_replace('http://', 'https://', $uploadUrl);
        }

        return $uploadUrl;
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
