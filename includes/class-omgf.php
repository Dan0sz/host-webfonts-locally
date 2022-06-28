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
 * @copyright: © 2022 Daan van den Bergh
 * @url      : https://ffw.press
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
			add_action('_admin_menu', [$this, 'init_admin']);

			$this->add_ajax_hooks();
		}

		if (!is_admin()) {
			add_action('init', [$this, 'init_frontend'], 50);
		}

		add_action('admin_init', [$this, 'do_optimize']);
		add_filter('content_url', [$this, 'force_ssl'], 1000, 2);
		add_filter('pre_update_option_omgf_optimized_fonts', [$this, 'base64_decode_optimized_fonts']);

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
		/** Prevents undefined constant in OMGF Pro, if its not at version v3.3.0 (yet) */
		define('OMGF_OPTIMIZATION_MODE', false);
		define('OMGF_SITE_URL', 'https://ffw.press');
		define('OMGF_CACHE_IS_STALE', esc_attr(get_option(OMGF_Admin_Settings::OMGF_CACHE_IS_STALE)));
		define('OMGF_CURRENT_DB_VERSION', esc_attr(get_option(OMGF_Admin_Settings::OMGF_CURRENT_DB_VERSION)));
		define('OMGF_DISPLAY_OPTION', esc_attr(get_option(OMGF_Admin_Settings::OMGF_OPTIMIZE_SETTING_DISPLAY_OPTION, 'swap')) ?: 'swap');
		define('OMGF_UNLOAD_STYLESHEETS', esc_attr(get_option(OMGF_Admin_Settings::OMGF_OPTIMIZE_SETTING_UNLOAD_STYLESHEETS, '')));
		define('OMGF_CACHE_KEYS', esc_attr(get_option(OMGF_Admin_Settings::OMGF_OPTIMIZE_SETTING_CACHE_KEYS, '')));
		define('OMGF_TEST_MODE', esc_attr(get_option(OMGF_Admin_Settings::OMGF_OPTIMIZE_SETTING_TEST_MODE)));
		define('OMGF_COMPATIBILITY', esc_attr(get_option(OMGF_Admin_Settings::OMGF_ADV_SETTING_COMPATIBILITY, 'on')));
		define('OMGF_UNINSTALL', esc_attr(get_option(OMGF_Admin_Settings::OMGF_ADV_SETTING_UNINSTALL)));
		define('OMGF_UPLOAD_DIR', apply_filters('omgf_upload_dir', WP_CONTENT_DIR . '/uploads/omgf'));
		define('OMGF_UPLOAD_URL', apply_filters('omgf_upload_url', WP_CONTENT_URL . '/uploads/omgf'));
	}

	/**
	 * Needs to run before admin_menu and admin_init.
	 * 
	 * @action _admin_menu
	 * 
	 * @return OMGF_Admin_Settings
	 */
	public function init_admin()
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
	 * @return OMGF_Frontend_Process
	 */
	public function init_frontend()
	{
		return new OMGF_Frontend_Process();
	}

	/**
	 * @return OMGF_Admin_Optimize
	 */
	public function do_optimize()
	{
		return new OMGF_Admin_Optimize();
	}

	/**
	 * @since v5.0.5 omgf_optimized_fonts is base64_encoded in the frontend, to bypass firewall restrictions on
	 * some servers.
	 * 
	 * @param $old_value
	 * @param $value
	 *
	 * @return bool|array
	 */
	public function base64_decode_optimized_fonts($value)
	{
		if (is_string($value) && base64_decode($value, true)) {
			return base64_decode($value);
		}

		return $value;
	}

	/**
	 * content_url uses is_ssl() to detect whether SSL is used. This fails for servers behind
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
		if (strpos($url, OMGF_UPLOAD_URL) === false) {
			return $url;
		}

		/**
		 * If the user entered https:// in the Home URL option, it's safe to assume that SSL is used.
		 */
		if (!is_ssl() && strpos(get_site_url(), 'https://') !== false) {
			$url = str_replace('http://', 'https://', $url);
		}

		return $url;
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
	 * Manage Optimized Fonts to be displayed in the Optimized Fonts table.
	 * 
	 * Use a static variable to reduce database reads/writes.
	 * 
	 * @since v4.5.7
	 * 
	 * @param array $maybe_add If it doesn't exist, it's added to the cache layer.
	 * 
	 * @return array
	 */
	public static function optimized_fonts($maybe_add = [])
	{
		/** @var array $optimized_fonts Cache layer */
		static $optimized_fonts;

		/**
		 * Get a fresh copy from the database if $optimized_fonts is empty|null|false (on 1st run)
		 */
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

		/**
		 * If $maybe_add doesn't exist in the cache layer yet, add it.
		 * 
		 * @since v4.5.7
		 */
		if (!empty($maybe_add) && !isset($optimized_fonts[key($maybe_add)])) {
			$optimized_fonts = array_merge($optimized_fonts, $maybe_add);
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
			/**
			 * @since v4.5.16 Convert $handle to lowercase, because $key is saved lowercase, too.
			 */
			if (strpos($key, strtolower($handle)) !== false) {
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
	public static function generate_stylesheet($fonts, $plugin = 'OMGF')
	{
		$generator = new OMGF_StylesheetGenerator($fonts, $plugin);

		return $generator->generate();
	}


	/**
	 * @return OMGF_Uninstall
	 * 
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

	/**
	 * Global debug logging function.
	 * 
	 * @param mixed $message 
	 * @return void 
	 */
	public static function debug($message)
	{
		if (!defined('OMGF_DEBUG_MODE') || !OMGF_DEBUG_MODE) {
			return;
		}

		error_log(current_time('Y-m-d H:i:s') . ' '  . microtime() . ": $message\n", 3, trailingslashit(WP_CONTENT_DIR) . 'omgf-pro-debug.log');
	}
}
