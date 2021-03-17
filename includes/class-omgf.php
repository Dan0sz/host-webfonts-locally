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
			$this->do_settings();
			$this->add_ajax_hooks();
		}

		if (!is_admin()) {
			$this->do_frontend();
		}

		add_action('rest_api_init', [$this, 'register_routes']);
		add_action('admin_init', [$this, 'do_optimize']);
		add_filter('content_url', [$this, 'rewrite_url'], 10, 2);
	}

	/**
	 * Define constants.
	 */
	public function define_constants()
	{
		define('OMGF_SITE_URL', 'https://daan.dev');
		define('OMGF_OPTIMIZATION_MODE', esc_attr(get_option(OMGF_Admin_Settings::OMGF_OPTIMIZE_SETTING_OPTIMIZATION_MODE, 'manual')));
		define('OMGF_MANUAL_OPTIMIZE_URL', esc_attr(get_option(OMGF_Admin_Settings::OMGF_OPTIMIZE_SETTING_MANUAL_OPTIMIZE_URL, site_url())));
		define('OMGF_FONT_PROCESSING', esc_attr(get_option(OMGF_Admin_Settings::OMGF_DETECTION_SETTING_FONT_PROCESSING, 'replace')));
		define('OMGF_DISPLAY_OPTION', esc_attr(get_option(OMGF_Admin_Settings::OMGF_OPTIMIZE_SETTING_DISPLAY_OPTION, 'swap')) ?: 'swap');
		define('OMGF_WOFF2_ONLY', esc_attr(get_option(OMGF_Admin_Settings::OMGF_OPTIMIZE_SETTING_WOFF2_ONLY)));
		define('OMGF_OPTIMIZE_EDIT_ROLES', esc_attr(get_option(OMGF_Admin_Settings::OMGF_OPTIMIZE_SETTING_OPTIMIZE_EDIT_ROLES, 'on')));
		define('OMGF_CACHE_PATH', esc_attr(get_option(OMGF_Admin_Settings::OMGF_ADV_SETTING_CACHE_PATH)) ?: '/uploads/omgf');
		define('OMGF_CACHE_URI', esc_attr(get_option(OMGF_Admin_Settings::OMGF_ADV_SETTING_CACHE_URI)) ?: '');
		define('OMGF_RELATIVE_URL', esc_attr(get_option(OMGF_Admin_Settings::OMGF_ADV_SETTING_RELATIVE_URL)));
		define('OMGF_CDN_URL', esc_attr(get_option(OMGF_Admin_Settings::OMGF_ADV_SETTING_CDN_URL)));
		define('OMGF_FONTS_DIR', WP_CONTENT_DIR . OMGF_CACHE_PATH);
		define('OMGF_UNINSTALL', esc_attr(get_option(OMGF_Admin_Settings::OMGF_ADV_SETTING_UNINSTALL)));
		define('OMGF_UNLOAD_STYLESHEETS', esc_attr(get_option(OMGF_Admin_Settings::OMGF_OPTIMIZE_SETTING_UNLOAD_STYLESHEETS, '')));
		define('OMGF_CACHE_KEYS', esc_attr(get_option(OMGF_Admin_Settings::OMGF_OPTIMIZE_SETTING_CACHE_KEYS, '')));
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
	 * @param $url
	 * @param $path
	 *
	 * @return mixed
	 */
	public function rewrite_url($url, $path)
	{
		/**
		 * Exit early if this isn't requested by OMGF.
		 */
		if (strpos($url, OMGF_CACHE_PATH) === false) {
			return $url;
		}

		/**
		 * If Relative URLs is enabled, overwrite URL with Path and continue execution.
		 */
		if (OMGF_RELATIVE_URL) {
			$content_dir = str_replace(site_url(), '', content_url());

			$url = $content_dir . $path;
		}

		if (OMGF_CDN_URL) {
			$url = str_replace(site_url(), OMGF_CDN_URL, $url);
		}

		if (OMGF_CACHE_URI) {
			$url = str_replace(OMGF_CACHE_PATH, OMGF_CACHE_URI, $url);
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
