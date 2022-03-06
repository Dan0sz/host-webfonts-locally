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
	const RESOURCE_HINTS = ['fonts.googleapis.com', 'fonts.gstatic.com'];

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
		$this->init();
	}

	/**
	 * Actions and hooks.
	 * 
	 * @return void 
	 */
	private function init()
	{
		/**
		 * Halt if this parameter is set.
		 */
		if (isset($_GET['nomgf'])) {
			return;
		}

		add_action('template_redirect', [$this, 'maybe_buffer_output'], 3);
		add_filter('omgf_buffer_output', [$this, 'parse']);
		add_action('wp_head', [$this, 'add_preloads'], 3);
		add_filter('wp_resource_hints', [$this, 'remove_resource_hints']);

		/** Smart Slider 3 compatibility */
		add_filter('wordpress_prepare_output', [$this, 'parse'], 11);
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
	public function remove_resource_hints($urls)
	{
		foreach ($urls as $key => &$url) {
			if (is_array($url)) {
				$url = $this->remove_resource_hints($url);

				continue;
			}

			foreach (self::RESOURCE_HINTS as $hint) {
				if (strpos($url, $hint) !== false) {
					unset($urls[$key]);
				}
			}
		}

		return $urls;
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
		/**
		 * Always run, if the omgf_optimize (added by Save & Optimize) is set.
		 */
		if (isset($_GET['omgf_optimize'])) {
			return true;
		}

		/**
		 * Should we optimize for logged in Administrators/Editors?
		 */
		if (!OMGF_OPTIMIZE_EDIT_ROLES && current_user_can('edit_pages')) {
			return false;
		}

		/**
		 * Make sure Page Builder previews don't get optimized content.
		 */
		foreach ($this->page_builders as $page_builder) {
			if (array_key_exists($page_builder, $_GET)) {
				return false;
			}
		}

		/** 
		 * Honor PageSpeed=off parameter as used by mod_pagespeed, in use by some pagebuilders,
		 * 
		 * @see https://www.modpagespeed.com/doc/experiment#ModPagespeed
		 */
		if (array_key_exists('PageSpeed', $_GET) && 'off' === $_GET['PageSpeed']) {
			return false;
		}


		/**
		 * Customizer previews shouldn't get optimized content.
		 */
		if (function_exists('is_customize_preview') && is_customize_preview()) {
			return false;
		}

		/**
		 * Let's GO!
		 */
		ob_start([$this, 'return_buffer']);
	}

	/**
	 * Returns the buffer for filtering, so page cache doesn't break.
	 * 
	 * @since v5.0.0 Tested with:
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
	 * This method uses Regular Expressions to parse the HTML. It's tested to be at least 
	 * twice as fast compared to using Xpath.
	 * 
	 * Test results (in seconds, with XDebug enabled)
	 * 
	 * Uncached: 	17.81094789505
	 * 			 	18.687641859055
	 * 			 	18.301512002945
	 * Cached:		0.00046515464782715
	 * 				0.00037288665771484
	 * 				0.00053095817565918
	 * 
	 * Using Xpath proved to be untestable, because it varied anywhere between 38 seconds and, well, timeouts.
	 * 
	 * @param string $html 
	 * 
	 * @return string 
	 */
	public function parse($html)
	{
		preg_match_all('/<link.*fonts\.googleapis\.com\/css.*?[\/]?>/', $html, $links);

		if (!isset($links[0]) || empty($links[0])) {
			return $html;
		}

		$google_fonts   = $this->build_fonts_set($links[0]);
		$search_replace = $this->build_search_replace($google_fonts);

		if (empty($search_replace['search']) || empty($search_replace['replace'])) {
			return $html;
		}

		$html = str_replace($search_replace['search'], $search_replace['replace'], $html);

		return apply_filters('omgf_processed_html', $html, $this);
	}

	/**
	 * Builds a processable array of Google Fonts' ID and (external) URL.
	 * 
	 * @param array  $links 
	 * @param string $handle If an ID attribute is not defined, this will be used instead.
	 * 
	 * @return array [ 0 => [ 'id' => (string), 'href' => (string) ] ]
	 */
	public function build_fonts_set($links, $handle = 'omgf-stylesheet')
	{
		$google_fonts = [];

		OMGF::debug(sprintf(__('Building fonts set for handle %s, using $s', 'host-webfonts-local'), $handle, print_r($links, true)));

		foreach ($links as $key => $link) {
			preg_match('/id=[\'"](?P<id>.*?)[\'"]/', $link, $id);

			$id = $this->strip_css_tag($id['id'] ?? "$handle-$key");

			preg_match('/href=[\'"](?P<href>.*?)[\'"]/', $link, $href);

			if (!isset($href['href'])) {
				continue;
			}

			$google_fonts[$key]['id']   = $id;
			$google_fonts[$key]['href'] = $href['href'];
		}

		OMGF::debug(sprintf(__('Built set: %s', 'host-webfonts-local'), print_r($google_fonts, true)));

		return $google_fonts;
	}

	/**
	 * Strip "-css" from the end of the stylesheet id, which WordPress adds to properly enqueued stylesheets.
	 * 
	 * @since v5.0.1 This eases the migration from v4.6.0.
	 * 
	 * @param  mixed $handle 
	 * @return mixed 
	 */
	private function strip_css_tag($handle)
	{
		$pos = strrpos($handle, '-css');

		if ($pos !== false) {
			$handle = substr_replace($handle, '', $pos, strlen($handle));
		}

		return $handle;
	}

	/**
	 * Build a Search/Replace array for all found Google Fonts.
	 * 
	 * @param mixed $google_fonts A processable set generated by $this->build_fonts_set().
	 * 
	 * @return array 
	 * 
	 * @throws SodiumException 
	 * @throws SodiumException 
	 * @throws TypeError 
	 * @throws TypeError 
	 * @throws TypeError 
	 */
	public function build_search_replace($google_fonts)
	{
		$search  = [];
		$replace = [];

		OMGF::debug(__('Building Search/Replace set...', 'host-webfonts-local'));

		foreach ($google_fonts as $key => $stack) {
			$handle = $stack['id'];

			/**
			 * If stylesheet with $handle is completely marked for unload, just clean the 'href'
			 * attribute to prevent it from loading.
			 */
			if (OMGF::unloaded_stylesheets() && in_array($handle, OMGF::unloaded_stylesheets())) {
				$search[$key] = $stack['href'];
				$replace[$key] = '';

				continue;
			}

			/**
			 * $cache_key is used for caching. $handle contains the original handle.
			 */
			if ((OMGF::unloaded_fonts() && $cache_key = OMGF::get_cache_key($stack['id']))
				|| apply_filters('omgf_frontend_update_cache_key', false)
			) {
				$handle = $cache_key;
			}

			/**
			 * Regular requests (in the frontend) will end here if the file exists.
			 */
			if (!isset($_GET['omgf_optimize']) && file_exists(OMGF_CACHE_PATH . "/$handle/$handle.css")) {
				$search[$key]  = $stack['href'];
				$replace[$key] = content_url(OMGF_CACHE_DIR . "/$handle/$handle.css");

				continue;
			}

			$api_url    = $this->build_request_url(urldecode($stack['href']), $handle, $stack['id']);
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

			$optimize   = new OMGF_Optimize($post_query['family'], $post_query['handle'], $post_query['original_handle'], $post_query['subset'] ?? '');
			$cached_url = $optimize->process();

			if (!$cached_url) {
				continue;
			}

			$search[$key]  = $stack['href'];
			$replace[$key] = $cached_url;
		}

		return ['search' => $search, 'replace' => $replace];
	}

	/**
	 * The generated request URL includes all required parameters for OMGF's Download API. 
	 *
	 * @param string $url            e.g. https://fonts.googleapis.com/css?family=Open+Sans:100,200,300|Roboto:100,200,300 etc.
	 * @param string $updated_handle e.g. example-handle-xvfdo
	 * @param string $handle         e.g. example-handle
	 *
	 * @return string
	 */
	public function build_request_url($url, $updated_handle, $handle)
	{
		$parsed_url = parse_url($url);
		$query      = $parsed_url['query'] ?? '';

		if ($parsed_url['path'] == '/css2') {
			// Request to fonts.googleapis.com/css2?etc.
			$original_query = $this->parse_css2($query);
		} elseif (strpos($parsed_url['path'], 'earlyaccess') !== false) {
			// Request to https://fonts.googleapis.com/earlyaccess/etc. should be left for OMGF Pro to deal with.
			$original_query = ['family' => ''];
		} else {
			/**
			 * Request to fonts.googleapis.com/css?etc. (default)
			 * 
			 * Decode, just to be sure.
			 */
			parse_str(html_entity_decode($query), $original_query);
		}

		$params = http_build_query(
			array_merge(
				$original_query,
				[
					'handle'          => $updated_handle,
					'original_handle' => $handle,
				]
			)
		);

		$request = 'https://fonts.googleapis.com/css?' . $params;

		return apply_filters('omgf_request_url', $request);
	}

	/**
	 * Convert CSS2 query to regular CSS API query.
	 * 
	 * @param string $query 
	 * 
	 * @return array 
	 */
	private function parse_css2($query)
	{
		// array_filter() removes empty elements.
		$families = array_filter(explode('&', $query));

		foreach ($families as $param) {
			if (strpos($param, 'family') === false) {
				continue;
			}

			parse_str($param, $parts);

			$font_families[] = $parts['family'];
		}

		if (empty($font_families)) {
			return $query;
		}

		$weights = '';

		foreach ($font_families as $font_family) {
			if (strpos($font_family, ':') !== false) {
				list($family, $weights) = explode(':', $font_family);
			} else {
				$family  = $font_family;
				$weights = '';
			}

			/**
			 * @var array|string $weights [ '300', '400', '500', etc. ] | ''
			 */
			$weights = strpos($weights, ';') !== false ? explode(';', substr($weights, strpos($weights, '@') + 1)) : '';

			if (!$weights) {
				$fonts[] = $family;

				continue;
			}

			foreach ($weights as &$weight) {
				$properties = explode(',', $weight);
				$weight     = $properties[0] == '1' && isset($properties[1]) ? $properties[1] . 'italic' : ($properties[0] != '0' ? $properties[0] : $properties[1]);
			}

			$fonts[] = $family . ':' . implode(',', $weights);
		}

		return ['family' => implode('|', $fonts)];
	}
}
