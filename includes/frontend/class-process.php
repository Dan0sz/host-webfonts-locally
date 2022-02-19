<?php
defined('ABSPATH') || exit;

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
class OMGF_Frontend_Process
{
	const OMGF_STYLE_HANDLE = 'omgf-fonts';

	/**
	 * @var array $page_builders Array of keys set by page builders when they're displaying their previews.
	 */
	private $page_builders = [
		'bt-beaverbuildertheme',
		'ct_builder',
		'elementor-preview',
		'et_fb',
		'fb-edit',
		'fl_builder',
		'siteorigin_panels_live_editor',
		'tve',
		'vc_action'
	];

	/**
	 * OMGF_Frontend_Functions constructor.
	 */
	public function __construct()
	{
		add_action('wp_head', [$this, 'add_preloads'], 3);
		add_filter('wp_resource_hints', [$this, 'remove_preconnects']);
		add_filter('omgf_buffer_output', [$this, 'parse_source']);
		add_action('template_redirect', [$this, 'maybe_buffer_output'], 3);
	}

	/**
	 * TODO: When setting all preloads at once (different stylesheet handles) combined with unloads, not all URLs are rewritten with their cache keys properly.
	 *       When configured handle by handle, it works fine. PHP multi-threading issues?
	 */
	public function add_preloads()
	{
		$preloaded_fonts = apply_filters('omgf_frontend_preloaded_fonts', OMGF::preloaded_fonts());

		if (!$preloaded_fonts) {
			return;
		}

		$optimized_fonts = apply_filters('omgf_frontend_optimized_fonts', OMGF::optimized_fonts());

		/**
		 * When OMGF Pro is enabled and set to Automatic mode, the merged handle is used to only load selected
		 * preloads for the currently used stylesheet.
		 * 
		 * @since v4.5.3 Added 2nd dummy parameter, to prevent Fatal Errors after updating.
		 */
		$pro_handle = apply_filters('omgf_pro_merged_handle', '', '');
		$i          = 0;

		foreach ($optimized_fonts as $stylesheet_handle => $font_faces) {
			if ($pro_handle && $stylesheet_handle != $pro_handle) {
				continue;
			}

			foreach ($font_faces as $font_face) {
				$preloads_stylesheet = $preloaded_fonts[$stylesheet_handle] ?? [];

				if (!in_array($font_face->id, array_keys($preloads_stylesheet))) {
					continue;
				}

				$font_id          = $font_face->id;
				$preload_variants = array_filter(
					(array) $font_face->variants,
					function ($variant) use ($preloads_stylesheet, $font_id) {
						return in_array($variant->id, $preloads_stylesheet[$font_id]);
					}
				);

				foreach ($preload_variants as $variant) {
					$url = rawurldecode($variant->woff2);
					echo "<link id='omgf-preload-$i' rel='preload' href='$url' as='font' type='font/woff2' crossorigin />\n";
					$i++;
				}
			}
		}
	}

	/**
	 * We're downloading the fonts, so preconnecting to Google is a waste of time. Literally.
	 * 
	 * @param array $urls 
	 * @return array 
	 */
	public function remove_preconnects($urls)
	{
		return array_diff($urls, ['fonts.googleapis.com']);
	}

	/**
	 * Start output buffer.
	 * 
	 * @action template_redirect
	 * 
	 * @return void 
	 */
	public function maybe_buffer_output()
	{
		$start = true;

		/**
		 * Allows us to quickly bypass fonts optimization.
		 */
		if (isset($_GET['nomgf'])) {
			$start = false;
		}

		/**
		 * Should we optimize for logged in Administrators/Editors?
		 */
		if (!OMGF_OPTIMIZE_EDIT_ROLES && current_user_can('edit_pages')) {
			$start = false;
		}

		/**
		 * Make sure Page Builder previews don't get optimized content.
		 */
		foreach ($this->page_builders as $page_builder) {
			if (array_key_exists($page_builder, $_GET)) {
				$start = false;
				break;
			}
		}

		/**
		 * Customizer previews shouldn't get optimized content.
		 */
		if (function_exists('is_customize_preview')) {
			$start = !is_customize_preview();
		}

		/**
		 * Let's GO!
		 */
		if ($start) {
			ob_start([$this, 'return_buffer']);
		}
	}

	/**
	 * Returns the buffer for filtering, so page cache doesn't break.
	 * 
	 * @since v4.3.1 Tested with:
	 *               - Cache Enabler v1.8.7
	 *                 - Default Settings
	 *               - LiteSpeed Cache
	 *                 - Don't know (Gal Baras tested it: @see https://wordpress.org/support/topic/completely-broke-wp-rocket-plugin/#post-15377538)
	 *               - W3 Total Cache v2.2.1:
	 *                 - Page Cache: Disk (basic)
	 *                 - Database/Object Cache: Off
	 *                 - JS/CSS minify/combine: On
	 *               - WP Fastest Cache v0.9.5
	 *                 - JS/CSS minify/combine: On
	 *                 - Page Cache: On
	 *               - WP Rocket v3.8.8:
	 *                 - Page Cache: Enabled
	 *                 - JS/CSS minify/combine: Enabled
	 *               - WP Super Cache v1.7.4
	 *                 - Page Cache: Enabled
	 * 
	 * @todo         Not tested (yet):
	 *               - Asset Cleanup Pro
	 *               - Kinsta Cache (Same as Cache Enabler?)
	 *               - Swift Performance
	 *  
	 * @return void 
	 */
	public function return_buffer($html)
	{
		if (!$html) {
			return $html;
		}

		return apply_filters('omgf_buffer_output', $html);
	}

	/**
	 * 
	 * @param string $html 
	 * 
	 * @return string 
	 */
	public function parse_source($html)
	{
		preg_match_all('/<link.*fonts\.googleapis\.com.*?[\/]?>/', $html, $links);

		if (!isset($links[0])) {
			return $html;
		}

		$google_fonts = [];

		foreach ($links[0] as $key => $link) {
			preg_match('/id=[\'"](?P<id>.*?)[\'"]/', $link, $id);

			$id = $id['id'] ?? "omgf-stylesheet-$key";

			preg_match('/href=[\'"](?P<href>.*?)[\'"]/', $link, $href);

			if (!isset($href['href'])) {
				continue;
			}

			$google_fonts[$key]['id']   = $id;
			$google_fonts[$key]['href'] = $href['href'];
		}

		$search  = [];
		$replace = [];

		foreach ($google_fonts as $key => $stack) {
			$updated_handle = $stack['id'];

			/**
			 * $updated_handle is used for caching. $stack['id'] contains the original handle.
			 */
			if ((OMGF::unloaded_fonts() && $cache_key = OMGF::get_cache_key($stack['id']))
				|| apply_filters('omgf_frontend_update_cache_key', false)
			) {
				$updated_handle = $cache_key;
			}

			if (file_exists(OMGF_CACHE_PATH . "/$updated_handle/$updated_handle.css")) {
				$search[$key]  = $stack['href'];
				$replace[$key] = content_url(OMGF_CACHE_DIR . "/$updated_handle/$updated_handle.css");

				continue;
			}

			$api_url    = $this->build_request_url(urldecode($stack['href']), $updated_handle, $stack['id']);
			$api_params = parse_url($api_url);

			parse_str($api_params['query'], $post_query);

			if (isset($api_params['fragment'])) {
				parse_str($api_params['fragment'], $additional_query);

				$post_query = array_merge($post_query, $additional_query);
			}

			/**
			 * Required parameters.
			 */
			if (!isset($post_query['family']) || !isset($post_query['handle']) || !isset($post_query['original_handle'])) {
				continue;
			}

			$download   = new OMGF_API_Download($post_query['family'], $post_query['handle'], $post_query['original_handle'], $post_query['subset'] ?? '');
			$cached_url = $download->process();

			if (!$cached_url) {
				continue;
			}

			$search[$key]  = $stack['href'];
			$replace[$key] = $cached_url;
		}

		if (empty($search) || empty($replace)) {
			return $html;
		}

		return str_replace($search, $replace, $html);
	}

	/**
	 * The generated request URL includes all required parameters for OMGF's Download API. 
	 *
	 * @param string $url            e.g. https://fonts.googleapis.com/css?family=Open+Sans
	 * @param string $updated_handle e.g. example-handle-xvfdo
	 * @param string $handle         e.g. example-handle
	 *
	 * @return string
	 */
	public function build_request_url($url, $updated_handle, $handle)
	{
		$parsed_url = parse_url($url);
		$query      = $parsed_url['query'] ?? '';

		parse_str($query, $original_query);

		$params = http_build_query(
			array_merge(
				$original_query,
				[
					'handle'          => $updated_handle,
					'original_handle' => $handle,
				]
			)
		);

		$request = $url . '?' . $params;

		return $request;
	}
}
