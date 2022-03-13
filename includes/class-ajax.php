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

class OMGF_AJAX
{
	/** @var string $plugin_text_domain */
	private $plugin_text_domain = 'host-webfonts-local';

	/**
	 * OMGF_AJAX constructor.
	 */
	public function __construct()
	{
		add_action('wp_ajax_omgf_remove_stylesheet_from_db', [$this, 'remove_stylesheet_from_db']);
		add_action('wp_ajax_omgf_refresh_cache', [$this, 'refresh_cache']);
		add_action('wp_ajax_omgf_empty_dir', [$this, 'empty_directory']);
	}

	/**
	 * Remove stylesheet with $handle from database.
	 */
	public function remove_stylesheet_from_db()
	{
		check_ajax_referer(OMGF_Admin_Settings::OMGF_ADMIN_PAGE, 'nonce');

		if (!current_user_can('manage_options')) {
			wp_die(__("Hmmm, you're not supposed to be here.", $this->plugin_text_domain));
		}

		$handle               = $_POST['handle'];
		$optimized_fonts      = OMGF::optimized_fonts();
		$unloaded_fonts       = OMGF::unloaded_fonts();
		$unloaded_stylesheets = OMGF::unloaded_stylesheets();
		$preloaded_fonts      = OMGF::preloaded_fonts();
		$cache_keys           = OMGF::cache_keys();

		$this->maybe_unset(OMGF_Admin_Settings::OMGF_OPTIMIZE_SETTING_CACHE_KEYS, $cache_keys, $handle, true);
		$this->maybe_unset(OMGF_Admin_Settings::OMGF_OPTIMIZE_SETTING_OPTIMIZED_FONTS, $optimized_fonts, $handle);
		$this->maybe_unset(OMGF_Admin_Settings::OMGF_OPTIMIZE_SETTING_UNLOAD_FONTS, $unloaded_fonts, $handle);
		$this->maybe_unset(OMGF_Admin_Settings::OMGF_OPTIMIZE_SETTING_UNLOAD_STYLESHEETS, $unloaded_stylesheets, $handle);
		$this->maybe_unset(OMGF_Admin_Settings::OMGF_OPTIMIZE_SETTING_PRELOAD_FONTS, $preloaded_fonts, $handle);
	}

	/**
	 * Unset a $key from $array and update $option_name. Optionally store array as comma separated string.
	 * 
	 * @param string $option_name     The option name to update.
	 * @param array  $array           The array to saarch.
	 * @param string $key             The key to unset when found.
	 * @param bool   $comma_separated When true, $array is converted to a comma separated string before saving it 
	 *                                to the database. 
	 * 
	 * @return void 
	 */
	private function maybe_unset($option_name, $array, $key, $comma_separated = false)
	{
		if (isset($array[$key]) || in_array($key, $array)) {
			if ($comma_separated) {
				$cache_key = OMGF::get_cache_key($key) ?: $key;
				$key_key   = array_search($cache_key, $array);

				unset($array[$key_key]);
			} else {
				unset($array[$key]);
			}

			if ($comma_separated) {
				$array = implode(',', $array);
			}

			update_option($option_name, $array);
		}
	}

	/**
	 * Removes the stale cache mark. Should be triggered along with a form submit.
	 */
	public function refresh_cache()
	{
		check_ajax_referer(OMGF_Admin_Settings::OMGF_ADMIN_PAGE, 'nonce');

		if (!current_user_can('manage_options')) {
			wp_die(__("Hmmm, you're not supposed to be here.", $this->plugin_text_domain));
		}

		add_filter('omgf_clean_up_instructions', function () {
			return [
				'section' => '/',
				'exclude' => [],
				'queue'   => [
					OMGF_Admin_Settings::OMGF_CACHE_IS_STALE
				]
			];
		});

		$this->empty_cache('/');

		delete_option(OMGF_Admin_Settings::OMGF_CACHE_IS_STALE);
	}

	/**
	 * Empty cache directory.
	 * 
	 * @since v4.5.3: Hardened security.
	 * @since v4.5.5: Added authentication.
	 */
	public function empty_directory()
	{
		check_ajax_referer(OMGF_Admin_Settings::OMGF_ADMIN_PAGE, 'nonce');

		if (!current_user_can('manage_options')) {
			wp_die(__("Hmmm, you're not supposed to be here.", $this->plugin_text_domain));
		}

		$section       = str_replace('*', '', $_POST['section']);
		$set_path      = rtrim(OMGF_CACHE_PATH . $section, '/');
		$resolved_path = realpath(OMGF_CACHE_PATH . $section);

		if ($resolved_path != $set_path) {
			wp_die(__('Attempted path traversal detected. Sorry, no script kiddies allowed!', $this->plugin_text_domain));
		}

		try {
			$section = $_POST['section'];
			$entries = array_filter((array) glob(OMGF_CACHE_PATH . $section));

			$this->empty_cache($section, $entries);

			OMGF_Admin_Notice::set_notice(__('Cache directory successfully emptied.', $this->plugin_text_domain));
		} catch (\Exception $e) {
			OMGF_Admin_Notice::set_notice(
				__('OMGF encountered an error while emptying the cache directory: ', $this->plugin_text_domain) . $e->getMessage(),
				'omgf-cache-error',
				'error',
				$e->getCode()
			);
		}
	}

	private function empty_cache($section)
	{
		$entries      = array_filter((array) glob(OMGF_CACHE_PATH . $section));
		$instructions = apply_filters(
			'omgf_clean_up_instructions',
			[
				'section' => $section,
				'exclude' => [],
				'queue'   => [
					OMGF_Admin_Settings::OMGF_CACHE_IS_STALE,
					OMGF_Admin_Settings::OMGF_CACHE_TIMESTAMP,
					OMGF_Admin_Settings::OMGF_OPTIMIZE_SETTING_CACHE_KEYS,
					OMGF_Admin_Settings::OMGF_OPTIMIZE_SETTING_OPTIMIZED_FONTS,
					OMGF_Admin_Settings::OMGF_OPTIMIZE_SETTING_UNLOAD_FONTS,
					OMGF_Admin_Settings::OMGF_OPTIMIZE_SETTING_PRELOAD_FONTS,
					OMGF_Admin_Settings::OMGF_OPTIMIZE_SETTING_UNLOAD_STYLESHEETS
				]
			]
		);

		foreach ($entries as $entry) {
			if (in_array($entry, $instructions['exclude'])) {
				continue;
			}

			OMGF::delete($entry);
		}


		foreach ($instructions['queue'] as $option) {
			delete_option($option);
		}
	}
}
