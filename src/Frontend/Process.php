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
* @copyright: © 2023 Daan van den Bergh
* @url      : https://daan.dev
* * * * * * * * * * * * * * * * * * * */

namespace OMGF\Frontend;

use OMGF\Helper as OMGF;
use OMGF\Admin\Settings;
use OMGF\Optimize;
use OMGF\TaskManager;

defined( 'ABSPATH' ) || exit;

class Process {
	const PRELOAD_ALLOWED_HTML = [
		'link' => [
			'id'          => true,
			'rel'         => true,
			'href'        => true,
			'as'          => true,
			'type'        => true,
			'crossorigin' => true,
		],
	];

	const RESOURCE_HINTS_URLS = [ 'fonts.googleapis.com', 'fonts.gstatic.com', 'fonts.bunny.net', 'fonts-api.wp.com' ];

	const RESOURCE_HINTS_ATTR = [ 'dns-prefetch', 'preconnect', 'preload' ];

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
		'vc_action', // WP Bakery
		'perfmatters', // Perfmatter's Frontend Script Manager.
	];

	/**
	 * Populates ?edit= parameter. To make sure OMGF doesn't run while editing posts.
	 *
	 * @var string[]
	 */
	private $edit_actions = [
		'edit',
		'elementor',
	];

	/** @var string $timestamp */
	private $timestamp = '';

	/**
	 * Break out early, e.g. if we want to parse other resources and don't need to
	 * setup all the hooks and filters.
	 *
	 * @since v5.4.0
	 *
	 * @var bool $break
	 */
	private $break = false;

	/**
	 * OMGF_Frontend_Functions constructor.
	 *
	 * @var $break bool
	 */
	public function __construct( $break = false ) {
		$this->timestamp = OMGF::get_option( Settings::OMGF_CACHE_TIMESTAMP, '' );
		$this->break     = $break;

		if ( ! $this->timestamp ) {
			$this->timestamp = time();

			OMGF::update_option( Settings::OMGF_CACHE_TIMESTAMP, $this->timestamp );
		}

		$this->init();
	}

	/**
	 * Actions and hooks.
	 *
	 * @return void
	 */
	private function init() {
		/**
		 * Halt execution if:
		 * * $break parameter is set.
		 * * `nomgf` GET-parameter is set.
		 * * Test Mode is enabled and current user is not an admin.
		 * * Test Mode is enabled and `omgf` GET-parameter is not set.
		 */
		$test_mode_enabled = ! empty( OMGF::get_option( Settings::OMGF_OPTIMIZE_SETTING_TEST_MODE ) );

		if (
			$this->break
			|| isset( $_GET['nomgf'] )
			|| ( ( $test_mode_enabled && ! current_user_can( 'manage_options' ) && ! isset( $_GET['omgf_optimize'] ) )
				&& ( $test_mode_enabled && ! current_user_can( 'manage_options' ) && ! isset( $_GET['omgf_optimize'] ) && ! isset( $_GET['omgf'] ) ) )
		) {
			return;
		}

		add_action( 'wp_head', [ $this, 'add_preloads' ], 3 );
		add_action( 'template_redirect', [ $this, 'maybe_buffer_output' ], 3 );
		/**
		 * @since v5.3.10 parse() runs on priority 10. Run this afterwards, to make sure e.g. the <preload> -> <noscript> approach some theme
		 *                developers use keeps working.
		 */
		add_filter( 'omgf_buffer_output', [ $this, 'remove_resource_hints' ], 11 );

		/** Only hook into our own filter if Smart Slider 3 isn't active, as it has its own filter. */
		if (
			! function_exists( 'smart_slider_3_plugins_loaded' )
			|| ! function_exists( 'groovy_menu_init_classes' )
		) {
			add_filter( 'omgf_buffer_output', [ $this, 'parse' ] );
		}

		add_filter( 'omgf_buffer_output', [ $this, 'add_success_message' ] );

		/** Groovy Menu compatibility */
		add_filter( 'groovy_menu_final_output', [ $this, 'parse' ], 11 );

		/** Smart Slider 3 compatibility */
		add_filter( 'wordpress_prepare_output', [ $this, 'parse' ], 11 );

		/** Mesmerize Pro theme compatibility */
		add_filter( 'style_loader_tag', [ $this, 'remove_mesmerize_filter' ], 12, 1 );
	}

	/**
	 * Add Preloads to wp_head().
	 *
	 * TODO: When setting all preloads at once (different stylesheet handles) combined with unloads, not all URLs are rewritten with their cache keys properly.
	 *       When configured handle by handle, it works fine. PHP multi-threading issues?
	 */
	public function add_preloads() {
		$preloaded_fonts = apply_filters( 'omgf_frontend_preloaded_fonts', OMGF::preloaded_fonts() );

		if ( ! $preloaded_fonts ) {
			return;
		}

		$optimized_fonts = apply_filters( 'omgf_frontend_optimized_fonts', OMGF::optimized_fonts() );
		$i               = 0;

		foreach ( $optimized_fonts as $stylesheet_handle => $font_faces ) {
			foreach ( $font_faces as $font_face ) {
				$preloads_stylesheet = $preloaded_fonts[ $stylesheet_handle ] ?? [];

				if ( ! in_array( $font_face->id, array_keys( $preloads_stylesheet ) ) ) {
					continue;
				}

				$font_id          = $font_face->id;
				$preload_variants = array_filter(
					(array) $font_face->variants,
					function ( $variant ) use ( $preloads_stylesheet, $font_id ) {
						return in_array( $variant->id, $preloads_stylesheet[ $font_id ] );
					}
				);

				/**
				 * @since v5.3.0 Store all preloaded URLs temporarily, to make sure no duplicate files (Variable Fonts) are preloaded.
				 */
				$preloaded = [];

				foreach ( $preload_variants as $variant ) {
					$url = rawurldecode( $variant->woff2 );

					/**
					 * @since v5.5.4 Since we're forcing relative URLs since v5.5.0, let's make sure $url is a relative URL to ensure
					 *               backwards compatibility.
					 */
					$url = str_replace( [ 'http:', 'https:' ], '', $url );

					/**
					 * @since v5.0.1 An extra check, because people tend to forget to flush their caches when changing fonts, etc.
					 */
					$file_path = str_replace( OMGF_UPLOAD_URL, OMGF_UPLOAD_DIR, $url );

					if ( ! file_exists( $file_path ) || in_array( $url, $preloaded ) ) {
						continue;
					}

					$preloaded[] = $url;

					echo wp_kses(
						"<link id='omgf-preload-$i' rel='preload' href='$url' as='font' type='font/woff2' crossorigin />\n",
						self::PRELOAD_ALLOWED_HTML
					);
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
	public function maybe_buffer_output() {
		/**
		* Always run, if the omgf_optimize parameter (added by Save & Optimize) is set.
		*/
		if ( isset( $_GET['omgf_optimize'] ) ) {
			do_action( 'omgf_frontend_process_before_ob_start' );

			return ob_start( [ $this, 'return_buffer' ] );
		}

		/**
		 * Make sure Page Builder previews don't get optimized content.
		 */
		foreach ( $this->page_builders as $page_builder ) {
			if ( array_key_exists( $page_builder, $_GET ) ) {
				return false;
			}
		}

		/**
		 * Post edit actions
		 */
		if ( array_key_exists( 'action', $_GET ) ) {
			foreach ( $this->edit_actions as $action ) {
				if ( $_GET['action'] === $action ) {
					return false;
				}
			}
		}

		/**
		 * Honor PageSpeed=off parameter as used by mod_pagespeed, in use by some pagebuilders,
		 *
		 * @see https://www.modpagespeed.com/doc/experiment#ModPagespeed
		 */
		if ( array_key_exists( 'PageSpeed', $_GET ) && 'off' === $_GET['PageSpeed'] ) {
			return false;
		}

		/**
		 * Customizer previews shouldn't get optimized content.
		 */
		if ( function_exists( 'is_customize_preview' ) && is_customize_preview() ) {
			return false;
		}

		do_action( 'omgf_frontend_process_before_ob_start' );

		/**
		 * Let's GO!
		 */
		ob_start( [ $this, 'return_buffer' ] );
	}

	/**
	 * Returns the buffer for filtering, so page cache doesn't break.
	 *
	 * @since v5.0.0 Tested with:
	 *               - Asset Cleanup Pro
	 *                 - Works
	 *               - Cache Enabler v1.8.7
	 *                 - Default Settings
	 *               - Kinsta Cache (Same as Cache Enabler?)
	 *                 - Works on Daan.dev
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
	public function return_buffer( $html ) {
		if ( ! $html ) {
			return $html;
		}

		return apply_filters( 'omgf_buffer_output', $html );
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
	public function remove_resource_hints( $html ) {
		/**
		 * @since v5.1.5 Use a lookaround that matches all link elements, because otherwise
		 *               matches grow past their supposed boundaries.
		 */
		preg_match_all( '/(?=\<link).+?(?<=>)/s', $html, $resource_hints );

		if ( ! isset( $resource_hints[0] ) || empty( $resource_hints[0] ) ) {
			return $html;
		}

		$search = [];

		foreach ( $resource_hints[0] as $key => $match ) {
			/**
			 * @since v5.1.5 Filter out any resource hints with a href pointing to Google Fonts' APIs.
			 * @since v5.2.1 Use preg_match() to exactly match an element's attribute, since 3rd party
			 *               plugins (e.g. Asset Cleanup) also tend to include their own custom attributes,
			 *               e.g. data-wpacu-to-be-preloaded
			 *
			 * TODO: [OMGF-42] I think I should be able to use an array_filter here or something?
			 */
			foreach ( self::RESOURCE_HINTS_URLS as $url ) {
				if ( strpos( $match, $url ) !== false ) {
					foreach ( self::RESOURCE_HINTS_ATTR as $attr ) {
						if ( preg_match( "/['\"]{$attr}['\"]/", $match ) === 1 ) {
							$search[] = $match;
						}
					}
				}
			}
		}

		return str_replace( $search, '', $html );
	}

	/**
	 * This method uses Regular Expressions to parse the HTML. It's tested to be at least
	 * twice as fast compared to using Xpath.
	 *
	 * Test results (in seconds, with XDebug enabled)
	 *
	 * Uncached:    17.81094789505
	 *              18.687641859055
	 *              18.301512002945
	 * Cached:      0.00046515464782715
	 *              0.00037288665771484
	 *              0.00053095817565918
	 *
	 * Using Xpath proved to be untestable, because it varied anywhere between 38 seconds and, well, timeouts.
	 *
	 * @param string $html Valid HTML.
	 *
	 * @return string Valid HTML, filtered by @filter omgf_processed_html.
	 */
	public function parse( $html ) {
		if ( $this->is_amp() ) {
			return apply_filters( 'omgf_processed_html', $html, $this );
		}

		/**
		 * @since v5.3.5 Use a generic regex and filter them separately.
		 */
		preg_match_all( '/<link.*?[\/]?>/s', $html, $links );

		if ( ! isset( $links[0] ) || empty( $links[0] ) ) {
			return apply_filters( 'omgf_processed_html', $html, $this );
		}

		/**
		 * @since v5.4.0 This approach is global on purpose. By just matching <link> elements containing the fonts.googleapis.com/css string,
		 *                e.g. preload elements are also properly processed.
		 *
		 * @since v5.4.0 Added compatibility for BunnyCDN's "GDPR compliant" Google Fonts API.
		 *
		 * @since v5.4.1 Make sure hitting the domain, not a subfolder generated by some plugins.
		 *
		 * @since v5.5.0 Added compatibility for WP.com's "GDPR compliant" Google Fonts API.
		 */
		$links = array_filter(
			$links[0],
			function ( $link ) {
				return strpos( $link, 'fonts.googleapis.com/css' ) !== false || strpos( $link, 'fonts.bunny.net/css' ) !== false || strpos( $link, 'fonts-api.wp.com/css' ) !== false;
			}
		);

		$google_fonts   = $this->build_fonts_set( $links );
		$search_replace = $this->build_search_replace( $google_fonts );

		if ( empty( $search_replace['search'] ) || empty( $search_replace['replace'] ) ) {
			return apply_filters( 'omgf_processed_html', $html, $this );
		}

		/**
		 * Use string position of $search to make sure only that instance of the string is replaced.
		 *
		 * This is to prevent duplicate replaces.
		 *
		 * @since v5.3.7
		 */
		foreach ( $search_replace['search'] as $key => $search ) {
			$position = strpos( $html, $search );

			if ( $position !== false && isset( $search_replace['replace'][ $key ] ) ) {
				$html = substr_replace( $html, $search_replace['replace'][ $key ], $position, strlen( $search ) );
			}
		}

		$found_iframes = OMGF::get_option( Settings::OMGF_FOUND_IFRAMES, [] );

		foreach ( TaskManager::IFRAMES_LOADING_FONTS as $script_id => $script ) {
			if ( strpos( $html, $script ) !== false && ! in_array( $script_id, $found_iframes ) ) {
				$found_iframes[] = $script_id;
			}
		}

		OMGF::update_option( Settings::OMGF_FOUND_IFRAMES, $found_iframes );

		return apply_filters( 'omgf_processed_html', $html, $this );
	}

	/**
	 * Adds a little success message to the HTML, to create a more logic user flow when manually optimizing pages.
	 *
	 * @param string $html Valid HTML
	 *
	 * @return string
	 */
	public function add_success_message( $html ) {
		if ( ! isset( $_GET['omgf_optimize'] ) || wp_doing_ajax() ) {
			return $html;
		}

		$parts = preg_split( '/(<body.*?>)/', $html, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE );

		if ( ! isset( $parts[0] ) || ! isset( $parts[1] ) || ! isset( $parts[2] ) ) {
			return $html;
		}

		$message_div = '<div class="omgf-optimize-success-message" style="padding: 25px 15px 15px; background-color: #fff; border-left: 3px solid #00a32a; border-top: 1px solid #c3c4c7; border-bottom: 1px solid #c3c4c7; border-right: 1px solid #c3c4c7; margin: 5px 20px 15px; font-family: Arial, \'Helvetica Neue\', sans-serif; font-weight: bold; font-size: 13px; color: #3c434a;"><span>%s</span></div>';

		$html = $parts[0] . $parts[1] . sprintf( $message_div, __( 'Optimization completed successfully. You can close this tab/window.', 'host-webfonts-local' ) ) . $parts[2];

		return $html;
	}

	/**
	 * @since v5.0.5 Check if current page is AMP page.
	 *
	 * @return bool
	 */
	private function is_amp() {
		return ( function_exists( 'is_amp_endpoint' ) && is_amp_endpoint() )
			|| ( function_exists( 'ampforwp_is_amp_endpoint' ) && ampforwp_is_amp_endpoint() );
	}

	/**
	 * Builds a processable array of Google Fonts' ID and (external) URL.
	 *
	 * @param array  $links
	 * @param string $handle If an ID attribute is not defined, this will be used instead.
	 *
	 * @return array [ 0 => [ 'id' => (string), 'href' => (string) ] ]
	 */
	public function build_fonts_set( $links, $handle = 'omgf-stylesheet' ) {
		$google_fonts = [];

		foreach ( $links as $key => $link ) {
			preg_match( '/id=[\'"](?P<id>.*?)[\'"]/', $link, $id );

			/**
			 * @var string $id Fallback to empty string if no id attribute exists.
			 */
			$id = $this->strip_css_tag( $id['id'] ?? '' );

			preg_match( '/href=[\'"](?P<href>.*?)[\'"]/', $link, $href );

			/**
			 * No valid href attribute provide in link element.
			 */
			if ( ! isset( $href['href'] ) ) {
				continue;
			}

			/**
			 * Mesmerize Theme compatibility
			 */
			if ( $href['href'] === '#' ) {
				preg_match( '/data-href=[\'"](?P<href>.*?)[\'"]/', $link, $href );
			}

			/**
			 * If no valid id attribute was found then this means that this stylesheet wasn't enqueued
			 * using proper WordPress conventions. We generate our own using the length of the href attribute
			 * to serve as a UID. This prevents clashes with other non-properly enqueued stylesheets on other pages.
			 *
			 * @since v5.1.4
			 */
			if ( ! $id ) {
				$id = "$handle-" . strlen( $href['href'] );
			}

			/**
			 * Compatibility fix for Divi Builder
			 *
			 * @since v5.1.3 Because Divi Builder uses the same handle for Google Fonts on each page,
			 *               even when these contain Google Fonts, let's append a (kind of) unique
			 *               identifier to the string, to make sure we can make a difference between
			 *               different Google Fonts configurations.
			 *
			 * @since v5.2.0 Allow Divi/Elementor compatibility fixes to be disabled, for those who have too
			 *               many different Google Fonts stylesheets configured throughout their pages and
			 *               blame OMGF for the fact that it detects all those different stylesheets. :-/
			 */
			if ( OMGF::get_option( Settings::OMGF_ADV_SETTING_COMPATIBILITY ) && strpos( $id, 'et-builder-googlefonts' ) !== false ) {
				$google_fonts[ $key ]['id'] = $id . '-' . strlen( $href['href'] );
			} elseif ( OMGF::get_option( Settings::OMGF_ADV_SETTING_COMPATIBILITY ) && $id === 'google-fonts-1' ) {
				/**
				 * Compatibility fix for Elementor
				 *
				 * @since v5.1.4 Because Elementor uses the same (annoyingly generic) handle for Google Fonts
				 *               stylesheets on each page, even when these contain different Google Fonts than
				 *               other pages, let's append a (kind of) unique identifier to the string, to make
				 *               sure we can make a difference between different Google Fonts configurations.
				 */
				$google_fonts[ $key ]['id'] = str_replace( '-1', '-' . strlen( $href['href'] ), $id );
			} elseif ( strpos( $id, 'sp-wpcp-google-fonts' ) !== false ) {
				/**
				 * Compatibility fix for Category Slider Pro for WooCommerce by ShapedPlugin
				 *
				 * @since v5.3.7 This plugin finds it necessary to provide each Google Fonts stylesheet with a
				 *               unique identifier on each pageload, to make sure its never cached. The worst idea ever.
				 *               On top of that, it throws OMGF off the rails entirely, eventually crashing the site.
				 */
				$google_fonts[ $key ]['id'] = 'sp-wpcp-google-fonts';
			} elseif ( strpos( $id, 'sp-lc-google-fonts' ) !== false ) {
				/**
				 * Compatibility fix for Logo Carousel Pro by ShapedPlugin
				 *
				 * @since v5.3.8 Same reason as above.
				 */
				$google_fonts[ $key ]['id'] = 'sp-lc-google-fonts';
			} elseif ( apply_filters( 'omgf_frontend_process_convert_pro_compatibility', strpos( $id, 'cp-google-fonts' ) !== false ) ) {
				/**
				 * Compatibility fix for Convert Pro by Brainstorm Force
				 *
				 * @since v5.5.4 Same reason as above, although it kind of makes sense in this case (since Convert Pro allows
				 *               to create pop-ups and people tend to get creative. I just hope the ID isn't random.)
				 *
				 * @filter omgf_frontend_process_convert_pro_compatibility Allows people to disable this feature, in case the different
				 *         stylesheets are actually needed.
				 */
				$google_fonts[ $key ]['id'] = 'cp-google-fonts';
			} else {
				$google_fonts[ $key ]['id'] = $id;
			}

			$google_fonts[ $key ]['link'] = $link;
			/**
			 * This is used for search/replace later on. This shouldn't be tampered with.
			 */
			$google_fonts[ $key ]['href'] = $href['href'];
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
	private function strip_css_tag( $handle ) {
		if ( ! $this->ends_with( $handle, '-css' ) ) {
			return $handle;
		}

		$pos = strrpos( $handle, '-css' );

		if ( $pos !== false ) {
			$handle = substr_replace( $handle, '', $pos, strlen( $handle ) );
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
	private function ends_with( $string, $end ) {
		$len = strlen( $end );

		if ( $len === 0 ) {
			return true;
		}

		return ( substr( $string, -$len ) === $end );
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
	public function build_search_replace( $google_fonts ) {
		$search  = [];
		$replace = [];

		foreach ( $google_fonts as $key => $stack ) {
			/**
			 * Handles should be all lowercase to prevent duplication issues on some filesystems.
			 */
			$handle          = strtolower( $stack['id'] );
			$original_handle = $handle;

			/**
			 * If stylesheet with $handle is completely marked for unload, just remove the element
			 * to prevent it from loading.
			 */
			if ( OMGF::unloaded_stylesheets() && in_array( $handle, OMGF::unloaded_stylesheets() ) ) {
				$search[ $key ]  = $stack['link'];
				$replace[ $key ] = '';

				continue;
			}

			$cache_key = OMGF::get_cache_key( $stack['id'] );

			/**
			 * $cache_key is used for caching. $handle contains the original handle.
			 */
			if ( ( OMGF::unloaded_fonts() && $cache_key )
				|| apply_filters( 'omgf_frontend_update_cache_key', false )
			) {
				$handle = $cache_key;
			}

			/**
			 * Regular requests (in the frontend) will end here if the file exists.
			 */
			if ( ! isset( $_GET['omgf_optimize'] ) && file_exists( OMGF_UPLOAD_DIR . "/$handle/$handle.css" ) ) {
				$search[ $key ]  = $stack['href'];
				$replace[ $key ] = OMGF_UPLOAD_URL . "/$handle/$handle.css?ver=" . $this->timestamp;

				continue;
			}

			/**
			 * @since v5.3.7 decode URL and special HTML chars, to make sure all params are properly processed later on.
			 */
			$href  = urldecode( htmlspecialchars_decode( $stack['href'] ) );
			$query = wp_parse_url( $href, PHP_URL_QUERY );
			parse_str( $query, $query );

			/**
			 * If required parameters aren't set, this request is most likely invalid. Let's just remove it.
			 */
			if ( ! isset( $query['family'] ) ) {
				$search[ $key ]  = $stack['link'];
				$replace[ $key ] = '';

				continue;
			}

			$optimize = new Optimize( $stack['href'], $handle, $original_handle );

			/**
			 * @var string $cached_url Absolute URL or empty string.
			 */
			$cached_url = $optimize->process();

			$search[ $key ]  = $stack['href'];
			$replace[ $key ] = $cached_url ? $cached_url . '?ver=' . $this->timestamp : '';
		}

		return [
			'search'  => $search,
			'replace' => $replace,
		];
	}

	/**
	 * Because all great themes come packed with extra Cumulative Layout Shifting.
	 *
	 * @since v5.4.3 Added compatibility for Highlight Pro; a Mesmerize based theme and Mesmerize,
	 *               the non-premium theme.
	 *
	 * @param string $tag
	 *
	 * @return string
	 */
	public function remove_mesmerize_filter( $tag ) {
		if (
			( wp_get_theme()->template === 'mesmerize-pro'
				|| wp_get_theme()->template === 'highlight-pro'
				|| wp_get_theme()->template === 'mesmerize' )
			&& strpos( $tag, 'fonts.googleapis.com' ) !== false
		) {
			return str_replace( 'href="" data-href', 'href', $tag );
		}

		return $tag;
	}
}
