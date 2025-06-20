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
* @copyright: © 2025 Daan van den Bergh
* @url      : https://daan.dev
* * * * * * * * * * * * * * * * * * * */

namespace OMGF\Frontend;

use OMGF\Admin\Dashboard;
use OMGF\Admin\Settings;
use OMGF\Helper as OMGF;
use OMGF\Optimize;

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

	const RESOURCE_HINTS_URLS  = [
		'fonts.googleapis.com',
		'fonts.gstatic.com',
		'fonts.bunny.net',
		'fonts-api.wp.com',
	];

	const RESOURCE_HINTS_ATTR  = [ 'dns-prefetch', 'preconnect', 'preload' ];

	/**
	 * Post types that still trigger template_redirect.
	 *
	 * @var array
	 */
	public static $post_types = [
		'tqb_quiz', // Thrive Quiz Builder
	];

	/**
	 * Populates ?edit= parameter. To make sure OMGF doesn't run while editing posts.
	 *
	 * @var string[]
	 */
	public static $edit_actions = [
		'edit',
		'elementor',
	];

	/**
	 * @var array $page_builders Array of keys set by page builders when they're displaying their previews.
	 */
	public static $page_builders = [
		'bt-beaverbuildertheme',
		'ct_builder',
		'elementor-preview',
		'et_fb',
		'fb-edit',
		'fl_builder',
		'op3editor', // OptimizePress 3
		'siteorigin_panels_live_editor',
		'tve',
		'vc_action', // WP Bakery
		'perfmatters', // Perfmatter's Frontend Script Manager.
	];

	/**
	 * Break out early, e.g. if we want to parse other resources and don't need to
	 * set up all the hooks and filters.
	 *
	 * @since v5.4.0
	 * @var bool $break
	 */
	private $break = false;

	/**
	 * @var string $timestamp
	 */
	private $timestamp;

	/**
	 * OMGF_Frontend_Functions constructor.
	 *
	 * @var $break bool
	 */
	public function __construct( $break = false ) {
		$this->break     = $break;
		$this->timestamp = OMGF::get_option( Settings::OMGF_CACHE_TIMESTAMP, '' );

		if ( ! $this->timestamp ) {
			$this->timestamp = $this->generate_timestamp(); // @codeCoverageIgnore
		}

		$this->init();
	}

	/**
	 * Generates a timestamp and stores it to the DB, which is appended to the stylesheet and fonts URLs.
	 *
	 * @see StylesheetGenerator::build_source_string()
	 * @see self::build_search_replace()
	 *
	 * @return int
	 *
	 * @codeCoverageIgnore
	 */
	private function generate_timestamp() {
		$timestamp = time();

		OMGF::update_option( Settings::OMGF_CACHE_TIMESTAMP, $timestamp ); // @codeCoverageIgnore

		return $timestamp;
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
		 * * Test Mode is enabled and the current user is not an admin.
		 * * Test Mode is enabled and the `omgf` GET-parameter is not set.
		 */
		$test_mode_enabled = ! empty( OMGF::get_option( Settings::OMGF_OPTIMIZE_SETTING_TEST_MODE ) );

		if ( $this->break ||
			isset( $_GET[ 'nomgf' ] ) ||
			( ( $test_mode_enabled && ! current_user_can( 'manage_options' ) && ! isset( $_GET[ 'omgf_optimize' ] ) ) && ( ! current_user_can( 'manage_options' ) && ! isset( $_GET[ 'omgf' ] ) ) ) ) {
			return;
		}

		add_action( 'wp_head', [ $this, 'add_preloads' ], 3 );
		add_action( 'template_redirect', [ $this, 'maybe_buffer_output' ], 3 );
		/**
		 * @since v5.3.10 parse() runs on priority 10. Run this afterward, to make sure e.g. the <preload> -> <noscript> approach some theme
		 *                developers use keeps working.
		 */
		add_filter( 'omgf_buffer_output', [ $this, 'remove_resource_hints' ], 11 );

		/** Only hook into our own filter if Smart Slider 3 or Groovy Menu aren't active, as they have their own output filter. */
		if ( ! function_exists( 'smart_slider_3_plugins_loaded' ) || ! function_exists( 'groovy_menu_init_classes' ) ) {
			add_filter( 'omgf_buffer_output', [ $this, 'parse' ] );
		}

		add_filter( 'omgf_buffer_output', [ $this, 'add_success_message' ] );
	}

	/**
	 * Add Preloads to wp_head().
	 * TODO: When setting all preloads at once (different stylesheet handles) combined with unloads, not all URLs are rewritten with their cache keys
	 * properly. When configured handle by handle, it works fine. PHP multi-threading issues?
	 */
	public function add_preloads() {
		$preloaded_fonts = apply_filters( 'omgf_frontend_preloaded_fonts', OMGF::preloaded_fonts() );

		if ( ! $preloaded_fonts ) {
			return; // @codeCoverageIgnore
		}

		$optimized_fonts = apply_filters( 'omgf_frontend_optimized_fonts', OMGF::optimized_fonts() );
		$i               = 0;

		foreach ( $optimized_fonts as $stylesheet_handle => $font_faces ) {
			foreach ( $font_faces as $font_face ) {
				$preloads_stylesheet = $preloaded_fonts[ $stylesheet_handle ] ?? [];

				if ( ! in_array( $font_face->id, array_keys( $preloads_stylesheet ) ) ) {
					continue; // @codeCoverageIgnore
				}

				$font_id          = $font_face->id;
				$preload_variants = array_filter(
					(array) $font_face->variants,
					function ( $variant ) use ( $preloads_stylesheet, $font_id ) {
						return in_array( $variant->id, $preloads_stylesheet[ $font_id ] );
					}
				);

				/**
				 * @since v5.3.0 Store all preloaded URLs temporarily to make sure no duplicate files (Variable Fonts) are preloaded.
				 */
				$preloaded = [];

				foreach ( $preload_variants as $variant ) {
					$url = rawurldecode( $variant->woff2 );

					/**
					 * @since v5.5.4 Since we're forcing relative URLs since v5.5.0, let's make sure $url is a relative URL to ensure
					 *               backwards compatibility.
					 */
					$url_parts = parse_url( $url );

					if ( ! empty( $url_parts[ 'host' ] ) && ! empty( $url_parts[ 'path' ] ) ) {
						$url = '//' . $url_parts[ 'host' ] . $url_parts[ 'path' ]; // @codeCoverageIgnore
					} else {
						$url = str_replace( [ 'http:', 'https:' ], '', $url );
					}

					/**
					 * @since v5.0.1 An extra check, because people tend to forget to flush their caches when changing fonts, etc.
					 */
					$file_path = str_replace(
						OMGF_UPLOAD_URL,
						OMGF_UPLOAD_DIR,
						apply_filters( 'omgf_frontend_process_url', $url )
					);

					if ( ! defined( 'DAAN_DOING_TESTS' ) && ! file_exists( $file_path ) || in_array( $url, $preloaded ) ) {
						continue; // @codeCoverageIgnore
					}

					$preloaded[] = $url;
					$timestamp   = OMGF::get_option( Settings::OMGF_CACHE_TIMESTAMP );
					$url         .= str_contains( $url, '?' ) ? "&ver=$timestamp" : "?ver=$timestamp";

					/**
					 * We can't use @see wp_kses_post() here, because it removes link elements.
					 */
					echo wp_kses(
						"<link id='omgf-preload-$i' rel='preload' href='$url' as='font' type='font/woff2' crossorigin />\n",
						self::PRELOAD_ALLOWED_HTML
					);

					$i ++;
				}
			}
		}
	}

	/**
	 * Start the output buffer.
	 *
	 * @action template_redirect
	 * @return bool|string valid HTML.
	 *
	 * @codeCoverageIgnore
	 */
	public function maybe_buffer_output() {
		if ( ! self::should_start() ) {
			return false;
		}

		do_action( 'omgf_frontend_process_before_ob_start' );

		return ob_start( [ $this, 'return_buffer' ] );
	}

	/**
	 * Should we start the buffer?
	 *
	 * @return bool
	 */
	public static function should_start() {
		/**
		 * Always run if the omgf_optimize parameter (added by Save & Optimize) is set.
		 */
		if ( self::query_param_exists( 'omgf_optimize' ) ) {
			return true;
		}

		/**
		 * Make sure Page Builder previews don't get optimized content.
		 */
		foreach ( self::$page_builders as $page_builder ) {
			if ( self::query_param_exists( $page_builder ) ) {
				return false;
			}
		}

		/**
		 * Make sure editors in post-types don't get optimized content.
		 */
		foreach ( self::$post_types as $post_type ) {
			if ( self::query_param_exists( $post_type ) ) {
				return false;
			}
		}

		/**
		 * Post edit actions
		 */
		if ( self::query_param_exists( 'action' ) ) {
			if ( in_array( $_GET[ 'action' ], self::$edit_actions, true ) ) {
				return false;
			}
		}

		/**
		 * Honor PageSpeed=off parameter as used by mod_pagespeed, in use by some pagebuilders,
		 *
		 * @see https://www.modpagespeed.com/doc/experiment#ModPagespeed
		 */
		if ( self::query_param_exists( 'PageSpeed' ) && 'off' === $_GET[ 'PageSpeed' ] ) {
			return false;
		}

		/**
		 * Customizer previews shouldn't get optimized content.
		 */
		if ( function_exists( 'is_customize_preview' ) && is_customize_preview() ) {
			return false; // @codeCoverageIgnore
		}

		return true;
	}

	/**
	 * A simple wrapper that makes sure the $_GET array is set, because in faulty setups, this might be the case.
	 *
	 * @see https://wordpress.org/support/topic/uncaught-typeerror-in-process-php/
	 *
	 * @param $array
	 *
	 * @return bool
	 */
	private static function query_param_exists( $key ) {
		return ! empty( $_GET ) && array_key_exists( $key, $_GET );
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
	 *                 - Don't know (Gal Baras tested it: https://wordpress.org/support/topic/completely-broke-wp-rocket-plugin/#post-15377538)
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
	 * Not tested (yet):
	 * TODO: [OMGF-41] - Swift Performance
	 * @return string Valid HTML
	 *
	 * @codeCoverageIgnore
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
	 * @param string $html Valid HTML.
	 *
	 * @return string Valid HTML.
	 */
	public function remove_resource_hints( $html ) {
		/**
		 * @since v5.1.5 Use a lookaround that matches all link elements, because otherwise
		 *               matches grow past their supposed boundaries.
		 */
		preg_match_all( '/(?=<link).+?(?<=>)/s', $html, $resource_hints );

		if ( empty( $resource_hints[ 0 ] ) ) {
			return $html; // @codeCoverageIgnore
		}

		/**
		 * @since v5.1.5 Filter out any resource hints with a href pointing to Google Fonts' APIs.
		 * @since v5.2.1 Use preg_match() to exactly match an element's attribute, since 3rd party
		 *               plugins (e.g. Asset Cleanup) also tend to include their own custom attributes,
		 *               e.g. data-wpacu-to-be-preloaded, which would also match in strpos('preload', $match).
		 */
		$search = array_filter(
			$resource_hints[ 0 ],
			function ( $resource_hint ) {
				preg_match( '/href=[\'"](https?:)?\/\/(.*?)[\'"\/]/', $resource_hint, $url );
				preg_match( '/rel=[\'"](.*?)[ \'"]/', $resource_hint, $attr );

				if ( empty( $url[ 2 ] ) || empty( $attr[ 1 ] ) ) {
					return false; // @codeCoverageIgnore
				}

				$url  = $url[ 2 ];
				$attr = $attr[ 1 ];

				return ! empty( preg_grep( "/$url/", self::RESOURCE_HINTS_URLS ) ) && in_array( $attr, self::RESOURCE_HINTS_ATTR );
			}
		);

		return str_replace( $search, '', $html );
	}

	/**
	 * This method uses Regular Expressions to parse the HTML. It's tested to be at least
	 * twice as fast compared to using Xpath.
	 * Test results (in seconds, with XDebug enabled)
	 * Uncached:    17.81094789505
	 *              18.687641859055
	 *              18.301512002945
	 * Cached:      0.00046515464782715
	 *              0.00037288665771484
	 *              0.00053095817565918
	 * Using Xpath proved to be untestable, because it varied anywhere between 38 seconds and, well, timeouts.
	 *
	 * @param string $html Valid HTML.
	 *
	 * @return string Valid HTML, filtered by @filter omgf_processed_html.
	 */
	public function parse( $html ) {
		if ( $this->is_amp() ) {
			return apply_filters( 'omgf_processed_html', $html, $this ); // @codeCoverageIgnore
		}

		/**
		 * @since v5.3.5 Use a generic regex and filter them separately.
		 */
		preg_match_all( '/<link.*?[\/]?>/s', $html, $links );

		if ( empty( $links[ 0 ] ) ) {
			return apply_filters( 'omgf_processed_html', $html, $this ); // @codeCoverageIgnore
		}

		/**
		 * @filter omgf_frontend_process_parse_links
		 *
		 * @since  v5.4.0 This approach is global on purpose. By just matching <link> elements containing the fonts.googleapis.com/css string
		 *                e.g., preload elements are also properly processed.
		 * @since  v5.4.0 Added compatibility for BunnyCDN's "GDPR compliant" Google Fonts API.
		 * @since  v5.4.1 Make sure hitting the domain, not a subfolder generated by some plugins.
		 * @since  v5.5.0 Added compatibility for WP.com's "GDPR compliant" Google Fonts API.
		 */
		$links = array_filter(
			$links[ 0 ],
			function ( $link ) {
				return apply_filters(
					'omgf_frontend_process_parse_links',
					str_contains( $link, 'fonts.googleapis.com/css' ) || str_contains( $link, 'fonts.bunny.net/css' ) || str_contains( $link, 'fonts-api.wp.com/css' ),
					$link
				);
			}
		);

		$google_fonts   = $this->build_fonts_set( $links );
		$search_replace = $this->build_search_replace( $google_fonts );

		if ( empty( $search_replace[ 'search' ] ) || empty( $search_replace[ 'replace' ] ) ) {
			return apply_filters( 'omgf_processed_html', $html, $this );
		}

		/**
		 * Use string position of $search to make sure only that instance of the string is replaced.
		 * This is to prevent duplicate replaces.
		 *
		 * @since v5.3.7
		 */
		foreach ( $search_replace[ 'search' ] as $key => $search ) {
			$position = strpos( $html, $search );

			if ( $position !== false && isset( $search_replace[ 'replace' ][ $key ] ) ) {
				$html = substr_replace( $html, $search_replace[ 'replace' ][ $key ], $position, strlen( $search ) );
			}
		}

		$this->parse_iframes( $html );

		return apply_filters( 'omgf_processed_html', $html, $this );
	}

	/**
	 * @since v5.0.5 Check if current page is AMP page.
	 * @return bool
	 */
	private function is_amp() {
		return ( function_exists( 'is_amp_endpoint' ) && is_amp_endpoint() ) || ( function_exists( 'ampforwp_is_amp_endpoint' ) && ampforwp_is_amp_endpoint() );
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
			 * @var array $id Fallback to empty string if no id attribute exists.
			 */
			$id = $this->strip_css_tag( $id[ 'id' ] ?? '' );

			preg_match( '/href=[\'"](?P<href>.*?)[\'"]/', $link, $href );

			/**
			 * No valid href attribute provide in link element.
			 */
			if ( ! isset( $href[ 'href' ] ) ) {
				continue; // @codeCoverageIgnore
			}

			/**
			 * If no valid id attribute was found, then this means that this stylesheet wasn't enqueued
			 * using proper WordPress conventions. We generate our own using the length of the href attribute
			 * to serve as a UID. This prevents clashes with other non-properly enqueued stylesheets on other pages.
			 *
			 * @since v5.1.4
			 *
			 * @var string $id
			 */
			if ( ! $id ) {
				$id = "$handle-" . strlen( $href[ 'href' ] ); // @codeCoverageIgnore
			}

			$google_fonts[ $key ][ 'id' ]   = apply_filters( 'omgf_frontend_process_fonts_set', $id, $href );
			$google_fonts[ $key ][ 'link' ] = $link;
			/**
			 * This is used for search/replace later on. This shouldn't be tampered with.
			 */
			$google_fonts[ $key ][ 'href' ] = apply_filters( 'omgf_frontend_process_fonts_set_href', $href[ 'href' ], $link );
		}

		return $google_fonts;
	}

	/**
	 * Strip "-css" from the end of the stylesheet id, which WordPress adds to properly enqueued stylesheets.
	 *
	 * @since v5.0.1 This eases the migration from v4.6.0.
	 *
	 * @param mixed $handle
	 *
	 * @return mixed
	 */
	private function strip_css_tag( $handle ) {
		if ( ! str_ends_with( $handle, '-css' ) ) {
			return $handle; // @codeCoverageIgnore
		}

		$pos = strrpos( $handle, '-css' );

		if ( $pos !== false ) {
			$handle = substr_replace( $handle, '', $pos, strlen( $handle ) );
		}

		return $handle;
	}

	/**
	 * Build a Search/Replace array for all found Google Fonts.
	 *
	 * @param mixed $google_fonts A processable set generated by $this->build_fonts_set().
	 *
	 * @return array
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
			$handle          = strtolower( $stack[ 'id' ] );
			$original_handle = $handle;

			/**
			 * If the stylesheet with $handle is completely marked for unloading, just remove the element
			 * to prevent it from loading.
			 */
			if ( apply_filters(
				'omgf_unloaded_stylesheets',
				OMGF::unloaded_stylesheets() && in_array( $handle, OMGF::unloaded_stylesheets() )
			) ) {
				$search[ $key ]  = $stack[ 'link' ]; // @codeCoverageIgnore
				$replace[ $key ] = ''; // @codeCoverageIgnore

				continue; // @codeCoverageIgnore
			}

			$cache_key = OMGF::get_cache_key( $stack[ 'id' ] );

			/**
			 * $cache_key is used for caching. $handle contains the original handle.
			 */
			if ( ( OMGF::unloaded_fonts() && $cache_key ) || apply_filters( 'omgf_frontend_update_cache_key', false ) ) {
				$handle = $cache_key;
			}

			/**
			 * Regular requests (in the frontend) will end here if the file exists.
			 */
			if ( ! isset( $_GET[ 'omgf_optimize' ] ) && file_exists( OMGF_UPLOAD_DIR . "/$handle/$handle.css" ) ) {
				$search[ $key ]  = $stack[ 'href' ];
				$replace[ $key ] = OMGF_UPLOAD_URL . "/$handle/$handle.css?ver=" . $this->timestamp;

				continue;
			}

			/**
			 * @since v5.3.7 decode URL and special HTML chars, to make sure all params are properly processed later on.
			 */
			$href  = urldecode( htmlspecialchars_decode( $stack[ 'href' ] ) );
			$query = wp_parse_url( $href, PHP_URL_QUERY );
			parse_str( $query, $query );

			/**
			 * If required parameters aren't set, this request is most likely invalid. Let's just remove it.
			 */
			if ( apply_filters( 'omgf_frontend_process_invalid_request', ! isset( $query[ 'family' ] ), $href ) ) {
				$search[ $key ]  = $stack[ 'link' ];
				$replace[ $key ] = '';

				continue;
			}

			$optimize = new Optimize( $stack[ 'href' ], $handle, $original_handle );

			/**
			 * @var string $cached_url Absolute URL or empty string.
			 */
			$cached_url = $optimize->process();

			$search[ $key ]  = $stack[ 'href' ];
			$replace[ $key ] = $cached_url ? $cached_url . '?ver=' . $this->timestamp : '';
		}

		return [
			'search'  => $search,
			'replace' => $replace,
		];
	}

	/**
	 * Parse $html for present iframes loading Google Fonts.
	 *
	 * @param $html
	 *
	 * @return void
	 */
	private function parse_iframes( $html ) {
		$found_iframes = OMGF::get_option( Settings::OMGF_FOUND_IFRAMES, [] );
		$count_iframes = count( $found_iframes );

		foreach ( Dashboard::IFRAMES_LOADING_FONTS as $script_id => $script ) {
			if ( str_contains( $html, $script ) && ! in_array( $script_id, $found_iframes ) ) {
				$found_iframes[] = $script_id; // @codeCoverageIgnore
			}
		}

		if ( $count_iframes !== count( $found_iframes ) ) {
			OMGF::update_option( Settings::OMGF_FOUND_IFRAMES, $found_iframes );
		}
	}

	/**
	 * Adds a little success message to the HTML, to create a more logic user flow when manually optimizing pages.
	 *
	 * @param string $html Valid HTML
	 *
	 * @return string
	 */
	public function add_success_message( $html ) {
		if ( ! isset( $_GET[ 'omgf_optimize' ] ) || wp_doing_ajax() || ! current_user_can( 'manage_options' ) ) {
			return $html;
		}

		$parts = preg_split( '/(<body.*?>)/', $html, - 1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE );

		if ( empty( $parts[ 0 ] ) || empty( $parts[ 1 ] ) || empty( $parts[ 2 ] ) ) {
			return $html;
		}

		$message_div = '<div class="omgf-optimize-success-message" style="padding: 25px 15px 15px; background-color: #fff; border-left: 3px solid #00a32a; border-top: 1px solid #c3c4c7; border-bottom: 1px solid #c3c4c7; border-right: 1px solid #c3c4c7; margin: 5px 20px 15px; font-family: Arial, \'Helvetica Neue\', sans-serif; font-weight: bold; font-size: 13px; color: #3c434a;"><span>%s</span></div>';
		$message     = sprintf(
			__( 'Google Fonts optimization completed. Return to the <a href="%s">settings screen</a> to see the results.', 'host-webfonts-local' ),
			admin_url( 'options-general.php?page=' . Settings::OMGF_ADMIN_PAGE )
		);

		return $parts[ 0 ] . $parts[ 1 ] . sprintf( $message_div, $message ) . $parts[ 2 ];
	}
}
