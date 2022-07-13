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
 * @url      : https://daan.dev
 * * * * * * * * * * * * * * * * * * * */
class OMGF_Frontend_Process
{
	const RESOURCE_HINTS_URLS = ['fonts.googleapis.com', 'fonts.gstatic.com'];

	const RESOURCE_HINTS_ATTR = ['dns-prefetch', 'preconnect', 'preload'];

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

	/** @var string $timestamp */
	private $timestamp = '';

	/**
	 * OMGF_Frontend_Functions constructor.
	 */
	public function __construct()
	{
		$this->timestamp = get_option(OMGF_Admin_Settings::OMGF_CACHE_TIMESTAMP, '');

		if (!$this->timestamp) {
			$this->timestamp = time();

			update_option(OMGF_Admin_settings::OMGF_CACHE_TIMESTAMP, $this->timestamp);
		}

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
		 * Halt execution if:
		 * * `nomgf` GET-parameter is set.
		 * * Test Mode is enabled and current user is not an admin.
		 * * Test Mode is enabled and `omgf` GET-parameter is not set.
		 */
		if (
			isset($_GET['nomgf'])
			|| ((OMGF_TEST_MODE == 'on' && !current_user_can('manage_options') && !isset($_GET['omgf_optimize']))
				&& (OMGF_TEST_MODE == 'on' && !current_user_can('manage_options') && !isset($_GET['omgf_optimize']) && !isset($_GET['omgf'])))
		) {
			return;
		}

		add_action('wp_head', [$this, 'add_preloads'], 3);
		add_action('template_redirect', [$this, 'maybe_buffer_output'], 3);
		add_filter('omgf_buffer_output', [$this, 'remove_resource_hints'], 9);
		add_filter('omgf_buffer_output', [$this, 'parse']);

		/** Smart Slider 3 compatibility */
		add_filter('wordpress_prepare_output', [$this, 'parse'], 11);

		/** Mesmerize Pro theme compatibility */
		add_filter('style_loader_tag', [$this, 'remove_mesmerize_filter'], 12, 1);
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
		$i               = 0;

		foreach ($optimized_fonts as $stylesheet_handle => $font_faces) {
			foreach ($font_faces as $font_face) {
				$preloads_stylesheet = $preloaded_fonts[$stylesheet_handle] ?? [];

				if (!in_array($font_face->id, array_keys($preloads_stylesheet))) {
					continue;
				}

				$font_id          = $font_face->id;
				// TODO: [OMGF-44] Add Preload Subsets feature.
				$preload_variants = array_filter(
					(array) $font_face->variants,
					function ($variant) use ($preloads_stylesheet, $font_id) {
						return in_array($variant->id, $preloads_stylesheet[$font_id]);
					}
				);

				foreach ($preload_variants as $variant) {
					$url = rawurldecode($variant->woff2);

					/**
					 * @since v5.0.1 An extra check, because people tend to forget to flush their caches when changing fonts, etc.
					 */
					if (!file_exists(str_replace(OMGF_UPLOAD_URL, OMGF_UPLOAD_DIR, $url))) {
						continue;
					}

					echo "<link id='omgf-preload-$i' rel='preload' href='$url' as='font' type='font/woff2' crossorigin />\n";
					$i++;
				}
			}
		}
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
		 * Always run, if the omgf_optimize parameter (added by Save & Optimize) is set.
		 */
		if (isset($_GET['omgf_optimize'])) {
			return ob_start([$this, 'return_buffer']);
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
	 *               - Asset Cleanup Pro
	 * 				   - Works
	 *               - Cache Enabler v1.8.7
	 *                 - Default Settings
	 *               - Kinsta Cache (Same as Cache Enabler?)
	 * 				   - Works on Daan.dev
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
	 * Not tested (yet):
	 * TODO: [OMGF-41] - Swift Performance
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
	 * We're downloading the fonts, so preconnecting to Google is a waste of time. Literally.
	 * 
	 * @since v5.0.5 Use a regular expression to match all resource hints.
	 * 
	 * @param  string $html Valid HTML.
	 *  
	 * @return string Valid HTML.
	 */
	public function remove_resource_hints($html)
	{
		/**
		 * @since v5.1.5 Use a lookaround that matches all link elements, because otherwise
		 * 				 matches grow past their supposed boundaries.
		 */
		preg_match_all('/(?=\<link).+?(?<=>)/', $html, $resource_hints);

		if (!isset($resource_hints[0]) || empty($resource_hints[0])) {
			return $html;
		}

		$search = [];

		foreach ($resource_hints[0] as $key => $match) {
			/**
			 * @since v5.1.5 Filter out any resource hints with a href pointing to Google Fonts' APIs.
			 * @since v5.2.1 Use preg_match() to exactly match an element's attribute, since 3rd party
			 * 				 plugins (e.g. Asset Cleanup) also tend to include their own custom attributes, 
			 * 				 e.g. data-wpacu-to-be-preloaded
			 * 
			 * TODO: [OMGF-42] I think I should be able to use an array_filter here or something?
			 */
			foreach (self::RESOURCE_HINTS_URLS as $url) {
				if (strpos($match, $url) !== false) {
					foreach (self::RESOURCE_HINTS_ATTR as $attr) {
						if (preg_match("/['\"]{$attr}['\"]/", $match) === 1) {
							$search[] = $match;
						}
					}
				}
			}
		}

		return str_replace($search, '', $html);
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
	 * @param string $html Valid HTML.
	 * 
	 * @return string Valid HTML, filtered by @filter omgf_processed_html.
	 */
	public function parse($html)
	{
		if ($this->is_amp()) {
			return apply_filters('omgf_processed_html', $html, $this);
		}

		/**
		 * Use positive lookaround for stricter matching.
		 */
		preg_match_all('/(?=\<link).+?href=[\'"](https:)?\/\/fonts\.googleapis\.com\/css.+?[\'"].+?(?<=>)/', $html, $links);

		if (!isset($links[0]) || empty($links[0])) {
			return apply_filters('omgf_processed_html', $html, $this);
		}

		$google_fonts   = $this->build_fonts_set($links[0]);
		$search_replace = $this->build_search_replace($google_fonts);

		if (empty($search_replace['search']) || empty($search_replace['replace'])) {
			return apply_filters('omgf_processed_html', $html, $this);
		}

		$html = str_replace($search_replace['search'], $search_replace['replace'], $html);

		return apply_filters('omgf_processed_html', $html, $this);
	}

	/**
	 * @since v5.0.5 Check if current page is AMP page.
	 * 
	 * @return bool 
	 */
	private function is_amp()
	{
		return (function_exists('is_amp_endpoint') && is_amp_endpoint())
			|| (function_exists('ampforwp_is_amp_endpoint') && ampforwp_is_amp_endpoint());
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

		foreach ($links as $key => $link) {
			preg_match('/id=[\'"](?P<id>.*?)[\'"]/', $link, $id);

			/**
			 * @var string $id Fallback to empty string if no id attribute exists.
			 */
			$id = $this->strip_css_tag($id['id'] ?? '');

			preg_match('/href=[\'"](?P<href>.*?)[\'"]/', $link, $href);

			/**
			 * No valid href attribute provide in link element.
			 */
			if (!isset($href['href'])) {
				continue;
			}

			/**
			 * If no valid id attribute was found then this means that this stylesheet wasn't enqueued
			 * using proper WordPress conventions. We generate our own using the length of the href attribute
			 * to serve as a UID. This prevents clashes with other non-properly enqueued stylesheets on other pages.
			 * 
			 * @since v5.1.4
			 */
			if (!$id) {
				$id = "$handle-" . strlen($href['href']);
			}

			/**
			 * Compatibility fix for Divi Builder
			 * 
			 * @since v5.1.3 Because Divi Builder uses the same handle for Google Fonts on each page,
			 * 			   	 even when these contain Google Fonts, let's append a (kind of) unique
			 * 				 identifier to the string, to make sure we can make a difference between 
			 * 				 different Google Fonts configurations.
			 * 
			 * @since v5.2.0 Allow Divi/Elementor compatibility fixes to be disabled, for those who have too 
			 * 				 many different Google Fonts stylesheets configured throughout their pages and 
			 * 				 blame OMGF for the fact that it detects all those different stylesheets. :-/
			 */
			if (OMGF_COMPATIBILITY && strpos($id, 'et-builder-googlefonts') !== false) {
				$google_fonts[$key]['id'] = $id . '-' . strlen($href['href']);
			} elseif (OMGF_COMPATIBILITY && $id === 'google-fonts-1') {
				/**
				 * Compatibility fix for Elementor
				 * 
				 * @since v5.1.4 Because Elementor uses the same (annoyingly generic) handle for Google Fonts 
				 * 				 stylesheets on each page, even when these contain different Google Fonts than 
				 * 				 other pages, let's append a (kind of) unique identifier to the string, to make 
				 * 			 	 sure we can make a difference between different Google Fonts configurations.
				 */
				$google_fonts[$key]['id'] = str_replace('-1', '-' . strlen($href['href']), $id);
			} else {
				$google_fonts[$key]['id'] = $id;
			}

			$google_fonts[$key]['link'] = $link;
			$google_fonts[$key]['href'] = $href['href'];
		}

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
		if (!$this->ends_with($handle, '-css')) {
			return $handle;
		}

		$pos = strrpos($handle, '-css');

		if ($pos !== false) {
			$handle = substr_replace($handle, '', $pos, strlen($handle));
		}

		return $handle;
	}

	/**
	 * Checks if a $string ends with $end.
	 * 
	 * @since v5.0.2
	 * 
	 * @param string $string 
	 * @param string $end
	 *  
	 * @return bool 
	 */
	private function ends_with($string, $end)
	{
		$len = strlen($end);

		if ($len == 0) {
			return true;
		}

		return (substr($string, -$len) === $end);
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

		foreach ($google_fonts as $key => $stack) {
			/**
			 * Handles should be all lowercase to prevent duplication issues on some filesystems.
			 */
			$handle          = strtolower($stack['id']);
			$original_handle = $handle;

			/**
			 * If stylesheet with $handle is completely marked for unload, just clean the 'href'
			 * attribute to prevent it from loading.
			 */
			if (OMGF::unloaded_stylesheets() && in_array($handle, OMGF::unloaded_stylesheets())) {
				$search[$key]  = $stack['link'];
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
			if (!isset($_GET['omgf_optimize']) && file_exists(OMGF_UPLOAD_DIR . "/$handle/$handle.css")) {
				$search[$key]  = $stack['href'];
				$replace[$key] = OMGF_UPLOAD_URL . "/$handle/$handle.css?ver=" . $this->timestamp;

				continue;
			}

			$query = parse_url($stack['href'], PHP_URL_QUERY);
			parse_str($query, $query);

			/**
			 * If required parameters aren't set, this request is most likely invalid. Let's just remove it.
			 * 
			 * TODO: [OMGF-40] Remove entire HTML element 
			 */
			if (!isset($query['family'])) {
				$search[$key]  = $stack['link'];
				$replace[$key] = '';

				continue;
			}

			$optimize   = new OMGF_Optimize($stack['href'], $handle, $original_handle);
			$cached_url = $optimize->process();

			if (!$cached_url) {
				continue;
			}

			$search[$key]  = $stack['href'];
			$replace[$key] = $cached_url . '?ver=' . $this->timestamp;
		}

		return ['search' => $search, 'replace' => $replace];
	}

	/**
	 * Because all great themes come packed with extra Cumulative Layout Shifting.
	 * 
	 * @param string $tag
	 *  
	 * @return string 
	 */
	public function remove_mesmerize_filter($tag)
	{
		if (wp_get_theme()->template == 'mesmerize-pro' && strpos($tag, 'fonts.googleapis.com') !== false) {
			return str_replace('href="" data-href', 'href', $tag);
		}

		return $tag;
	}
}
