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

class OMGF_Frontend_Functions
{
	const OMGF_STYLE_HANDLE = 'omgf-fonts';

	/** @var bool $do_optimize */
	private $do_optimize;

	/**
	 * OMGF_Frontend_Functions constructor.
	 */
	public function __construct()
	{
		$this->do_optimize = $this->maybe_optimize_fonts();

		add_action('wp_head', [$this, 'add_preloads'], 3);
		add_action('wp_print_styles', [$this, 'process_fonts'], PHP_INT_MAX - 1000);
	}

	/**
	 * Should we optimize for logged in editors/administrators?
	 *
	 * @return bool
	 */
	private function maybe_optimize_fonts()
	{
		if (!OMGF_OPTIMIZE_EDIT_ROLES && current_user_can('edit_pages')) {
			return false;
		}

		return true;
	}

	/**
	 * TODO: When setting all preloads at once (different stylesheet handles) combined with unloads, not all URLs are rewritten with their cache keys properly.
	 *       When configured handle by handle, it works fine. PHP multi-threading issues?
	 */
	public function add_preloads()
	{
		$preloaded_fonts = omgf_init()::preloaded_fonts();

		if (!$preloaded_fonts) {
			return;
		}

		$stylesheets = omgf_init()::optimized_fonts();

		$i = 0;

		foreach ($stylesheets as $stylesheet => $fonts) {
			foreach ($fonts as $font) {
				$preloads_stylesheet = $preloaded_fonts[$stylesheet] ?? [];

				if (!in_array($font->id, array_keys($preloads_stylesheet))) {
					continue;
				}

				$font_id          = $font->id;
				$preload_variants = array_filter(
					$font->variants,
					function ($variant) use ($preloads_stylesheet, $font_id) {
						return in_array($variant->id, $preloads_stylesheet[$font_id]);
					}
				);

				foreach ($preload_variants as $variant) {
					$url = $variant->woff2;
					echo "<link id='omgf-preload-$i' rel='preload' href='$url' as='font' type='font/woff2' crossorigin />\n";
					$i++;
				}
			}
		}
	}

	/**
	 * Check if the Remove Google Fonts option is enabled.
	 */
	public function process_fonts()
	{
		if (!$this->do_optimize) {
			return;
		}

		if (is_admin()) {
			return;
		}

		if (apply_filters('omgf_pro_advanced_processing_enabled', false)) {
			return;
		}

		switch (OMGF_FONT_PROCESSING) {
			case 'remove':
				add_action('wp_print_styles', [$this, 'remove_registered_fonts'], PHP_INT_MAX - 500);
				break;
			default:
				add_action('wp_print_styles', [$this, 'replace_registered_fonts'], PHP_INT_MAX - 500);
		}
	}

	/**
	 * This function contains a nice little hack, to avoid messing with potential dependency issues. We simply set the source to an empty string!
	 */
	public function remove_registered_fonts()
	{
		global $wp_styles;

		$registered = $wp_styles->registered;
		$fonts      = apply_filters('omgf_auto_remove', $this->detect_registered_google_fonts($registered));

		foreach ($fonts as $handle => $font) {
			$wp_styles->registered[$handle]->src = '';
		}
	}

	/**
	 * Retrieve stylesheets from Google Fonts' API and modify the stylesheet for local storage.
	 */
	public function replace_registered_fonts()
	{
		global $wp_styles;

		$registered           = $wp_styles->registered;
		$fonts                = apply_filters('omgf_auto_replace', $this->detect_registered_google_fonts($registered));
		$unloaded_stylesheets = omgf_init()::unloaded_stylesheets();
		$unloaded_fonts       = omgf_init()::unloaded_fonts();

		foreach ($fonts as $handle => $font) {
			// If this stylesheet has been marked for unload, empty the src and skip out early.
			if (in_array($handle, $unloaded_stylesheets)) {
				$wp_styles->registered[$handle]->src = '';

				continue;
			}

			$updated_handle = $handle;

			if ($unloaded_fonts) {
				$updated_handle = omgf_init()::get_cache_key($handle);
			}

			$cached_file = OMGF_CACHE_PATH . '/' . $updated_handle . "/$updated_handle.css";

			if (file_exists(WP_CONTENT_DIR . $cached_file)) {
				$wp_styles->registered[$handle]->src = content_url($cached_file);

				continue;
			}

			if (OMGF_OPTIMIZATION_MODE == 'auto' || (OMGF_OPTIMIZATION_MODE == 'manual' && isset($_GET['omgf_optimize']))) {
				$api_url  = str_replace(['http:', 'https:'], '', home_url('/wp-json/omgf/v1/download/'));
				$protocol = '';

				if (substr($font->src, 0, 2) == '//') {
					$protocol = 'https:';
				}

				$wp_styles->registered[$handle]->src = $protocol . str_replace('//fonts.googleapis.com/', $api_url, $font->src) . "&handle=$updated_handle&original_handle=$handle";
			}
		}
	}

	/**
	 * @param $registered_styles
	 *
	 * @return array
	 */
	private function detect_registered_google_fonts($registered_styles)
	{
		return array_filter(
			$registered_styles,
			function ($contents) {
				return strpos($contents->src, 'fonts.googleapis.com/css') !== false
					|| strpos($contents->src, 'fonts.gstatic.com') !== false;
			}
		);
	}
}
