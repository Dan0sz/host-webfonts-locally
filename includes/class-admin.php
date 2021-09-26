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

use function PHPSTORM_META\map;

defined('ABSPATH') || exit;

class OMGF_Admin
{
	const OMGF_ADMIN_JS_HANDLE  = 'omgf-admin-js';
	const OMGF_ADMIN_CSS_HANDLE = 'omgf-admin-css';

	/** @var array $show_notice */
	private $show_notice = [];

	/** @var string $plugin_text_domain */
	private $plugin_text_domain = 'host-webfonts-local';

	/**
	 * OMGF_Admin constructor.
	 */
	public function __construct()
	{
		/**
		 * Filterable list of options that require the cache to be emptied.
		 */
		$this->show_notice = apply_filters(
			'omgf_admin_options_show_notice',
			[
				OMGF_Admin_Settings::OMGF_OPTIMIZE_SETTING_OPTIMIZATION_MODE,
				OMGF_Admin_Settings::OMGF_OPTIMIZE_SETTING_DISPLAY_OPTION,
				OMGF_Admin_Settings::OMGF_ADV_SETTING_CACHE_PATH,
			]
		);

		add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
		add_action('admin_notices', [$this, 'print_notices']);

		$this->do_optimize_settings();
		$this->do_detection_settings();
		$this->do_advanced_settings();
		$this->do_help();
		$this->maybe_do_after_update_notice();

		// This used to fix a bug, but now it breaks stuff. Leave it here for the time being.
		// add_filter('pre_update_option_omgf_optimized_fonts', [$this, 'update_optimized_fonts'], 10, 2);
		add_filter('pre_update_option_omgf_cache_keys', [$this, 'clean_up_cache'], 10, 3);
		add_filter('pre_update_option', [$this, 'settings_changed'], 10, 3);
	}

	/**
	 * Enqueues the necessary JS and CSS and passes options as a JS object.
	 *
	 * @param $hook
	 */
	public function enqueue_admin_scripts($hook)
	{
		if ($hook == 'settings_page_optimize-webfonts') {
			wp_enqueue_script(self::OMGF_ADMIN_JS_HANDLE, plugin_dir_url(OMGF_PLUGIN_FILE) . 'assets/js/omgf-admin.js', ['jquery'], OMGF_STATIC_VERSION, true);
			wp_enqueue_style(self::OMGF_ADMIN_CSS_HANDLE, plugin_dir_url(OMGF_PLUGIN_FILE) . 'assets/css/omgf-admin.css', [], OMGF_STATIC_VERSION);
		}
	}

	/**
	 * Add notice to admin screen.
	 */
	public function print_notices()
	{
		OMGF_Admin_Notice::print_notices();
	}

	/**
	 * @return OMGF_Admin_Settings_Optimize
	 */
	private function do_optimize_settings()
	{
		return new OMGF_Admin_Settings_Optimize();
	}

	/**
	 * @return OMGF_Admin_Settings_Detection
	 */
	private function do_detection_settings()
	{
		return new OMGF_Admin_Settings_Detection();
	}

	/**
	 * @return OMGF_Admin_Settings_Advanced
	 */
	private function do_advanced_settings()
	{
		return new OMGF_Admin_Settings_Advanced();
	}

	/**
	 * Add filters for Help section.
	 * 
	 * @return OMGF_Admin_Settings_Help 
	 */
	private function do_help()
	{
		return new OMGF_Admin_Settings_Help();
	}

	/**
	 * Checks if an update notice should be displayed after updating.
	 */
	private function maybe_do_after_update_notice()
	{
		if (version_compare(OMGF_CURRENT_DB_VERSION, OMGF_DB_VERSION, '<')) {
			OMGF_Admin_Notice::set_notice(
				sprintf(
					__('Thank you for updating OMGF to v%s! This version contains database changes. <a href="%s">Verify your settings</a> and make sure everything is as you left it or, <a href="%s">view the changelog</a> for details. ', $this->plugin_text_domain),
					OMGF_DB_VERSION,
					admin_url(OMGF_Admin_Settings::OMGF_OPTIONS_GENERAL_PAGE_OPTIMIZE_WEBFONTS),
					admin_url(OMGF_Admin_Settings::OMGF_PLUGINS_INSTALL_CHANGELOG_SECTION)
				),
				'omgf-post-update',
				false
			);

			update_option(OMGF_Admin_Settings::OMGF_CURRENT_DB_VERSION, OMGF_DB_VERSION);
		}
	}

	/**
	 * This fixes a bug where the admin screen wouldn't properly be updated after omgf_optimized_fonts 
	 * was updated by the API.
	 * 
	 * @param $old_value
	 * @param $value
	 *
	 * @return bool|array
	 */
	public function update_optimized_fonts($value, $old_value)
	{
		return $old_value;
	}

	/**
	 * Triggered when unload settings is changed, cleans up old cache files.
	 *
	 * TODO: Clean up doesn't work on 2nd run?
	 *
	 * @param $old_value
	 * @param $value
	 * @param $option_name
	 */
	public function clean_up_cache($value, $old_value)
	{
		if ($old_value == $value) {
			return $value;
		}

		if ($old_value == null) {
			return $value;
		}

		$cache_keys = explode(',', $old_value);

		foreach ($cache_keys as $key) {
			$entries = array_filter((array) glob(OMGF_FONTS_DIR . "/*$key"));

			foreach ($entries as $entry) {
				OMGF::delete($entry);
			}
		}

		return $value;
	}

	/**
	 * Shows notice if $option_name is in $show_notice array.
	 * 
	 * @param $new_value
	 * @param $old_settings
	 * 
	 * @see $show_notice
	 *
	 * @return mixed
	 */
	public function settings_changed($value, $option_name, $old_value)
	{
		if (!in_array($option_name, $this->show_notice)) {
			return $value;
		}

		if ($value != $old_value) {
			global $wp_settings_errors;

			if (!empty($wp_settings_errors)) {
				$wp_settings_errors = [];
			}

			add_settings_error(
				'general',
				'omgf_settings_changed',
				sprintf(
					__('Settings changed. <a href="#" data-cache-section="/*" data-nonce="%s" class="omgf-empty">Click here</a> to empty OMGF\'s cache.', $this->plugin_text_domain),
					wp_create_nonce(OMGF_Admin_Settings::OMGF_ADMIN_PAGE)
				),
				'success'
			);
		}

		return $value;
	}
}
