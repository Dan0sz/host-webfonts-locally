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

class OMGF
{
	/**
	 * OMGF constructor.
	 */
	public function __construct()
	{
		$this->define_constants();

		if (is_admin()) {
			add_action('plugins_loaded', [$this, 'init_admin'], 50);
		}

		if (!is_admin()) {
			add_action('plugins_loaded', [$this, 'init_frontend'], 50);
		}

		add_action('admin_init', [$this, 'do_optimize']);
		add_action('rest_api_init', [$this, 'register_routes']);
		add_filter('content_url', [$this, 'force_ssl'], 1000, 2);

		/**
		 * Render plugin update messages.
		 */
		add_action('in_plugin_update_message-' . OMGF_PLUGIN_BASENAME, [$this, 'render_update_notice'], 11, 2);
	}

	/**
	 * Define constants.
	 */
	public function define_constants()
	{
		define('OMGF_SITE_URL', 'https://daan.dev');
		define('OMGF_CURRENT_DB_VERSION', esc_attr(get_option(OMGF_Admin_Settings::OMGF_CURRENT_DB_VERSION)));
		define('OMGF_OPTIMIZATION_MODE', esc_attr(get_option(OMGF_Admin_Settings::OMGF_OPTIMIZE_SETTING_OPTIMIZATION_MODE, 'manual')));
		define('OMGF_MANUAL_OPTIMIZE_URL', esc_attr(get_option(OMGF_Admin_Settings::OMGF_OPTIMIZE_SETTING_MANUAL_OPTIMIZE_URL, site_url())));
		define('OMGF_FONT_PROCESSING', esc_attr(get_option(OMGF_Admin_Settings::OMGF_DETECTION_SETTING_FONT_PROCESSING, 'replace')));
		define('OMGF_DISPLAY_OPTION', esc_attr(get_option(OMGF_Admin_Settings::OMGF_OPTIMIZE_SETTING_DISPLAY_OPTION, 'swap')) ?: 'swap');
		define('OMGF_OPTIMIZE_EDIT_ROLES', esc_attr(get_option(OMGF_Admin_Settings::OMGF_OPTIMIZE_SETTING_OPTIMIZE_EDIT_ROLES, 'on')));
		define('OMGF_CACHE_PATH', esc_attr(get_option(OMGF_Admin_Settings::OMGF_ADV_SETTING_CACHE_PATH)) ?: '/uploads/omgf');
		define('OMGF_FONTS_DIR', WP_CONTENT_DIR . OMGF_CACHE_PATH);
		define('OMGF_UNINSTALL', esc_attr(get_option(OMGF_Admin_Settings::OMGF_ADV_SETTING_UNINSTALL)));
		define('OMGF_UNLOAD_STYLESHEETS', esc_attr(get_option(OMGF_Admin_Settings::OMGF_OPTIMIZE_SETTING_UNLOAD_STYLESHEETS, '')));
		define('OMGF_CACHE_KEYS', esc_attr(get_option(OMGF_Admin_Settings::OMGF_OPTIMIZE_SETTING_CACHE_KEYS, '')));
	}

	/**
	 * Required in Admin.
	 * 
	 * @return void 
	 */
	public function init_admin()
	{
		$this->do_settings();
		$this->add_ajax_hooks();
	}

	/**
	 * Required in Frontend.
	 * 
	 * @return void 
	 */
	public function init_frontend()
	{
		$this->do_frontend();
	}

	/**
	 * @return array
	 */
	public static function optimized_fonts()
	{
		static $optimized_fonts = [];

		if (empty($optimized_fonts)) {
			$optimized_fonts = get_option(OMGF_Admin_Settings::OMGF_OPTIMIZE_SETTING_OPTIMIZED_FONTS, []) ?: [];
		}

		/**
		 * get_option() should take care of this, but sometimes it doesn't.
		 * 
		 * @since v4.5.6
		 */
		if (is_string($optimized_fonts)) {
			$optimized_fonts = unserialize($optimized_fonts);
		}

		return $optimized_fonts;
	}

	/**
	 * @return array
	 */
	public static function preloaded_fonts()
	{
		static $preloaded_fonts = [];

		if (empty($preloaded_fonts)) {
			$preloaded_fonts = get_option(OMGF_Admin_Settings::OMGF_OPTIMIZE_SETTING_PRELOAD_FONTS, []) ?: [];
		}

		return $preloaded_fonts;
	}

	/**
	 * @return array
	 */
	public static function unloaded_fonts()
	{
		static $unloaded_fonts = [];

		if (empty($unloaded_fonts)) {
			$unloaded_fonts = get_option(OMGF_Admin_Settings::OMGF_OPTIMIZE_SETTING_UNLOAD_FONTS, []) ?: [];
		}

		return $unloaded_fonts;
	}

	/**
	 * @return array
	 */
	public static function unloaded_stylesheets()
	{
		static $unloaded_stylesheets = [];

		if (empty($unloaded_stylesheets)) {
			$unloaded_stylesheets = explode(',', get_option(OMGF_Admin_Settings::OMGF_OPTIMIZE_SETTING_UNLOAD_STYLESHEETS, ''));
		}

		return array_filter($unloaded_stylesheets);
	}

	/**
	 * @return array
	 */
	public static function cache_keys()
	{
		static $cache_keys = [];

		if (empty($cache_keys)) {
			$cache_keys = explode(',', get_option(OMGF_Admin_Settings::OMGF_OPTIMIZE_SETTING_CACHE_KEYS, ''));
		}

		return array_filter($cache_keys);
	}

	/**
	 * @param $handle
	 *
	 * @return string
	 */
	public static function get_cache_key($handle)
	{
		$cache_keys = self::cache_keys();

		foreach ($cache_keys as $index => $key) {
			if (strpos($key, $handle) !== false) {
				return $key;
			}
		}

		return '';
	}

	/**
	 * Download $url and save as $filename.$extension to $path.
	 * 
	 * @param mixed $url 
	 * @param mixed $filename 
	 * @param mixed $extension 
	 * @param mixed $path 
	 * 
	 * @return string 
	 */
	public static function download($url, $filename, $extension, $path)
	{
		$download = new OMGF_Download($url, $filename, $extension, $path);

		return $download->download();
	}

	/**
	 * @param mixed $fonts 
	 * 
	 * @return string 
	 */
	public static function generate_stylesheet($fonts, $handle = '', $plugin = 'OMGF')
	{
		$generator = new OMGF_StylesheetGenerator($fonts, $handle, $plugin);

		return $generator->generate();
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
	public function do_frontend()
	{
		return new OMGF_Frontend_Functions();
	}

	/**
	 *
	 */
	public function register_routes()
	{
		$proxy = new OMGF_API_Download();
		$proxy->register_routes();
	}

	/**
	 * @return OMGF_Optimize
	 */
	public function do_optimize()
	{
		return new OMGF_Optimize();
	}

	/**
	 * Render update notices if available.
	 * 
	 * @param mixed $plugin 
	 * @param mixed $response 
	 * @return void 
	 */
	public function render_update_notice($plugin, $response)
	{
		$current_version = $plugin['Version'];
		$new_version     = $plugin['new_version'];

		if (version_compare($current_version, $new_version, '<')) {
			$response = wp_remote_get('https://daan.dev/omgf-update-notices.json');

			if (is_wp_error($response)) {
				return;
			}

			$update_notices = (array) json_decode(wp_remote_retrieve_body($response));

			if (!isset($update_notices[$new_version])) {
				return;
			}

			printf(
				' <strong>' . __('This update includes major changes, please <a href="%s" target="_blank">read this</a> before continuing.') . '</strong>',
				$update_notices[$new_version]->url
			);
		}
	}

	/**
	 * content_url() uses is_ssl() to detect whether SSL is used. This fails for servers behind
	 * load balancers and/or reverse proxies. So, we double check with this filter.
	 * 
	 * @since v4.4.4
	 * 
	 * @param mixed $url 
	 * @param mixed $path 
	 * @return mixed 
	 */
	public function force_ssl($url, $path)
	{
		/**
		 * Only rewrite URLs requested by this plugin. We don't want to interfere with other plugins.
		 */
		if (strpos($url, OMGF_CACHE_PATH) === false) {
			return $url;
		}

		/**
		 * If the user entered https:// in the Home URL option, it's safe to assume that SSL is used.
		 */
		if (!is_ssl() && strpos(home_url(), 'https://') !== false) {
			$url = str_replace('http://', 'https://', $url);
		}

		return $url;
	}

	/**
	 * @return OMGF_Uninstall
	 * @throws ReflectionException
	 */
	public static function do_uninstall()
	{
		return new OMGF_Uninstall();
	}

	/**
	 * @param $entry
	 */
	public static function delete($entry)
	{
		if (is_dir($entry)) {
			$file = new \FilesystemIterator($entry);

			// If dir is empty, valid() returns false.
			while ($file->valid()) {
				self::delete($file->getPathName());
				$file->next();
			}

			rmdir($entry);
		} else {
			unlink($entry);
		}
	}
}
