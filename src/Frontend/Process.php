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
* @copyright: © 2026 Daan van den Bergh
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

	const RESOURCE_HINTS_URLS = [
		'fonts.googleapis.com',
		'fonts.gstatic.com',
		'fonts.bunny.net',
		'fonts-api.wp.com',
	];

	const RESOURCE_HINTS_ATTR = [ 'dns-prefetch', 'preconnect', 'preload' ];

	/**
	 * The hosts of the Google Fonts API (and its GDPR compliant alternatives) OMGF processes, along with
	 * the path(s) a stylesheet request to each of them starts with.
	 *
	 * @see   self::is_font_api_url()
	 * @since v6.3.11
	 */
	const FONT_API_HOSTS = [
		'fonts.googleapis.com' => [ '/css' ],
		'fonts.bunny.net'      => [ '/css' ],
		'fonts-api.wp.com'     => [ '/css' ],
	];

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
	 * @var bool $break
	 * @since v5.4.0
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
		$this->timestamp = OMGF::get_option( Settings::OMGF_DB_CACHE_TIMESTAMP, '' );

		if ( ! $this->timestamp ) {
			$this->timestamp = $this->generate_timestamp(); // @codeCoverageIgnore
		}

		$this->init();
	}

	/**
	 * Generates a timestamp and stores it to the DB, which is appended to the stylesheet and fonts URLs.
	 *
	 * @see self::build_search_replace()
	 *
	 * @see StylesheetGenerator::build_source_string()
	 * @return int
	 *
	 * @codeCoverageIgnore
	 */
	private function generate_timestamp() {
		$timestamp = time();

		OMGF::update_option( Settings::OMGF_DB_CACHE_TIMESTAMP, $timestamp ); // @codeCoverageIgnore

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
		 * * `nomgf` GET-parameter is set.
		 * * Test Mode is enabled and the current user is not an admin.
		 * * Test Mode is enabled and the `omgf` GET-parameter is not set.
		 */
		$test_mode_enabled = ! empty( OMGF::get_option( Settings::OMGF_OPTIMIZE_SETTING_TEST_MODE ) );
		$is_admin          = current_user_can( 'manage_options' );
		$is_test_request   = isset( $_GET['omgf'] );

		if ( $this->break ||
		     isset( $_GET['nomgf'] ) ||
		     ( $test_mode_enabled && ! $is_admin && ! $is_test_request && ! OMGF::is_running_optimize() ) ) {
			return;
		}

		add_action( 'wp_head', [ $this, 'add_preloads' ], 3 );
		add_action( 'template_redirect', [ $this, 'maybe_buffer_output' ], 3 );
		add_action( 'template_redirect', [ $this, 'maybe_set_optimize_has_run' ] );
		add_action( 'login_init', [ $this, 'maybe_buffer_output' ], 3 );
		/**
		 * @since v5.3.10 parse() runs on priority 10. Run this afterward, to make sure e.g., the <preload> -> <noscript> approaches some theme
		 *                developers use keep working.
		 */
		add_filter( 'omgf_buffer_output', [ $this, 'remove_resource_hints' ], 11 );

		/** Only hook into our own filter if Smart Slider 3 and Groovy Menu aren't active, as they have their own output filter. */
		if ( ! function_exists( 'smart_slider_3_plugins_loaded' ) && ! function_exists( 'groovy_menu_init_classes' ) ) {
			add_filter( 'omgf_buffer_output', [ $this, 'process' ] );
		}

		add_filter( 'omgf_buffer_output', [ $this, 'add_success_message' ] );
	}

	/**
	 * Add Preloads to wp_head().
	 * TODO: When setting all preloads at once (different stylesheet handles) combined with unloads, not all URLs are rewritten with their cache keys
	 * properly. When configured handle by handle, it works fine. PHP multi-threading issues?
	 */
	public function add_preloads() {
		do_action( 'omgf_frontend_process_preloads' );

		$preloaded_fonts = OMGF::preloaded_fonts();

		if ( ! $preloaded_fonts ) {
			return; // @codeCoverageIgnore
		}

		$optimized_fonts = OMGF::optimized_fonts();
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

					if ( ! empty( $url_parts['host'] ) && ! empty( $url_parts['path'] ) ) {
						$url = '//' . $url_parts['host'] . $url_parts['path']; // @codeCoverageIgnore
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
					$timestamp   = OMGF::get_option( Settings::OMGF_DB_CACHE_TIMESTAMP );
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
	 * Adds a little success message to the HTML to create a more logic user flow when manually optimizing pages.
	 *
	 * @param string $html Valid HTML
	 *
	 * @return string
	 */
	public function add_success_message( $html ) {
		if ( ! current_user_can( 'manage_options' ) || ! OMGF::is_running_optimize() || wp_doing_ajax() ) {
			return $html;
		}

		$parts = preg_split( '/(<body.*?>)/', $html, - 1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE );

		if ( empty( $parts[0] ) || empty( $parts[1] ) || empty( $parts[2] ) ) {
			return $html;
		}

		$message_div = '<div class="omgf-optimize-success-message" style="padding: 25px 15px 15px; background-color: #fff; border-left: 3px solid #00a32a; border-top: 1px solid #c3c4c7; border-bottom: 1px solid #c3c4c7; border-right: 1px solid #c3c4c7; margin: 5px 20px 15px; font-family: Arial, \'Helvetica Neue\', sans-serif; font-weight: bold; font-size: 13px; color: #3c434a;"><span>%s</span></div>';
		$message     = sprintf(
			__( 'Google Fonts optimization completed. Return to the <a href="%s">settings screen</a> to see the results.', 'host-webfonts-local' ),
			admin_url( 'options-general.php?page=' . Settings::OMGF_ADMIN_PAGE )
		);

		return $parts[0] . $parts[1] . sprintf( $message_div, $message ) . $parts[2];
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
		 * Always run if Save & Optimize is running.
		 */
		if ( OMGF::is_running_optimize() ) {
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
			if ( in_array( $_GET['action'], self::$edit_actions, true ) ) {
				return false;
			}
		}

		/**
		 * Honor PageSpeed=off parameter as used by mod_pagespeed, in use by some pagebuilders,
		 *
		 * @see https://www.modpagespeed.com/doc/experiment#ModPagespeed
		 */
		if ( self::query_param_exists( 'PageSpeed' ) && 'off' === $_GET['PageSpeed'] ) {
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
	 * Sets the Optimize Has Run flag after the first run, i.e.,
	 * - when OMGF is running optimize;
	 * - the flag isn't set yet, and,
	 * - @see OMGF::admin_optimized_fonts() returns empty.
	 *
	 * @since v6.2.0
	 *
	 * @return void
	 */
	public function maybe_set_optimize_has_run() {
		if ( OMGF::is_running_optimize() && ! OMGF::optimize_succeeded() ) {
			update_option( Settings::OMGF_FLAG_OPTIMIZE_HAS_RUN, true );
		}
	}

	/**
	 * This method uses Regular Expressions to process the HTML produced by the buffer. It's tested to be at least
	 * twice as fast compared to using Xpath.
	 *
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
	public function process( $html ) {
		if ( $this->is_amp() ) {
			return apply_filters( 'omgf_processed_html', $html, $this ); // @codeCoverageIgnore
		}

		/**
		 * @since v5.3.5 Use a generic regex and filter them separately.
		 */
		preg_match_all( '/<link.*?[\/]?>/s', $html, $links );

		if ( empty( $links[0] ) ) {
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
			$links[0],
			function ( $link ) {
				/**
				 * @since v6.3.10 Run the detection against a sanitized copy of the element, because a line break
				 *                (or tab) inside the href attribute could split the string we're looking for.
				 *                $link itself is left untouched, because it's passed to the filter below and
				 *                used for search/replace later on.
				 * @since v6.3.11 Detect the API by parsing the element's attribute values, instead of running a
				 *                substring match against the serialized element. Only elements which actually
				 *                point at the API should be processed, not elements which merely mention it.
				 */
				$urls  = $this->get_element_urls( $link );
				$found = false;

				foreach ( $urls as $url ) {
					if ( $this->is_font_api_url( $url ) ) {
						$found = true;

						break;
					}
				}

				/**
				 * @since v6.3.11 The element's (sanitized) attribute values are passed along, so 3rd parties can
				 *                validate the URLs themselves, instead of matching the element's markup.
				 */
				return apply_filters( 'omgf_frontend_process_parse_links', $found, $link, $urls );
			}
		);

		$google_fonts   = $this->build_fonts_set( $links );
		$search_replace = $this->build_search_replace( $google_fonts );

		if ( empty( $search_replace['search'] ) || empty( $search_replace['replace'] ) ) {
			return apply_filters( 'omgf_processed_html', $html, $this );
		}

		/**
		 * Use the string position of $search to make sure only that instance of the string is replaced.
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
	 * Returns the (sanitized, non-empty) values of all quoted attributes in a HTML element.
	 *
	 * Which attribute holds the stylesheet's URL isn't set in stone: themes and plugins are known to
	 * park it in e.g. a data-href attribute and fill the href attribute in later on.
	 *
	 * @see   self::sanitize_url()
	 * @since v6.3.11
	 *
	 * @param string $element A serialized HTML element, e.g. <link href="..." rel="stylesheet" />.
	 *
	 * @return array
	 */
	public function get_element_urls( $element ) {
		preg_match_all( '/[a-zA-Z0-9_:.-]+=([\'"])(?P<value>[^\'"]*)\1/', (string) $element, $attributes );

		if ( empty( $attributes['value'] ) ) {
			return []; // @codeCoverageIgnore
		}

		return array_values( array_filter( array_map( [ $this, 'sanitize_url' ], $attributes['value'] ) ) );
	}

	/**
	 * Is $url a request to one of the Google Fonts API (compatible) endpoints OMGF processes?
	 *
	 * The host is matched in full (never as a substring) and the path has to be the endpoint serving
	 * the stylesheets.
	 *
	 * @see   self::FONT_API_HOSTS
	 * @since v6.3.11
	 *
	 * @param string $url
	 *
	 * @return bool
	 */
	public function is_font_api_url( $url ) {
		if ( ! is_string( $url ) || $url === '' ) {
			return false;
		}

		/**
		 * Validate the URL in the same shape it's requested in later on.
		 *
		 * @see \OMGF\Frontend\Filters::decode_url()
		 */
		$url = $this->sanitize_url( html_entity_decode( $url ) );

		/**
		 * A scheme less URL (e.g. fonts.googleapis.com/css?family=Roboto) isn't parsed into a host by
		 * wp_parse_url(), while a scheme relative URL (//fonts.googleapis.com/css?family=Roboto) is.
		 */
		if ( ! preg_match( '~^(https?:)?//~i', $url ) ) {
			$url = '//' . ltrim( $url, '/' );
		}

		$parts = wp_parse_url( $url );

		if ( empty( $parts['host'] ) ) {
			return false; // @codeCoverageIgnore
		}

		/**
		 * @filter omgf_font_api_hosts Allows add-ons to process additional endpoints, e.g. Material Icons.
		 */
		$hosts = apply_filters( 'omgf_font_api_hosts', self::FONT_API_HOSTS );
		$host  = strtolower( rtrim( $parts['host'], '.' ) );

		if ( ! isset( $hosts[ $host ] ) ) {
			return false;
		}

		$path = strtolower( $parts['path'] ?? '' );

		foreach ( (array) $hosts[ $host ] as $prefix ) {
			if ( str_starts_with( $path, $prefix ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Strips characters from a URL which browsers ignore when requesting it.
	 *
	 * HTML allows line breaks (and tabs) inside attribute values, e.g., to keep a long Google Fonts API request
	 * readable in the editor. Browsers remove those characters before requesting the URL, which means the
	 * stylesheet loads just fine, while the same URL would be rejected when used as-is in a HTTP request.
	 *
	 * @see   https://url.spec.whatwg.org/#concept-basic-url-parser (tab/newline removal)
	 * @since v6.3.10
	 *
	 * @param string $url
	 *
	 * @return string
	 */
	private function sanitize_url( $url ) {
		return trim( str_replace( [ "\r", "\n", "\t" ], '', $url ) );
	}

	/**
	 * Builds a processable array of Google Fonts' ID and (external) URL.
	 *
	 * @param array  $links
	 * @param string $handle If an ID attribute is not defined, this will be used instead.
	 *
	 * @return array [ 0 => [ 'id' => (string), 'href' => (string), 'url' => (string) ] ]
	 */
	public function build_fonts_set( $links, $handle = 'omgf-stylesheet' ) {
		$google_fonts = [];

		foreach ( $links as $key => $link ) {
			preg_match( '/id=[\'"](?P<id>.*?)[\'"]/', $link, $id );

			/**
			 * @var array $id Fallback to empty string if no id attribute exists.
			 */
			$id = $this->strip_css_tag( $id['id'] ?? '' );

			/**
			 * @since v6.3.10 Match the quote character which opens the attribute and capture everything up to its
			 *                counterpart, because HTML allows line breaks inside attribute values. The previously
			 *                used .*? didn't match line breaks, which meant a <href> attribute containing one was
			 *                never matched at all and the entire link element was skipped, while browsers loaded it
			 *                just fine. Using a negated character class (instead of adding the /s modifier) makes
			 *                sure the match can never run past a quote character.
			 * @since v6.3.12 Only match the href attribute at a name boundary, so an attribute whose name ends
			 *                in "href" (e.g. data-href) isn't captured as the href.
			 */
			preg_match( '/(?<![-\w])href=([\'"])(?P<href>[^\'"]*)\1/', $link, $href );

			/**
			 * No valid href attribute provide in link element.
			 */
			if ( ! isset( $href['href'] ) ) {
				continue; // @codeCoverageIgnore
			}

			/**
			 * If no valid id attribute was found, then this means that this stylesheet wasn't enqueued
			 * using proper WordPress conventions. We generate our own using the length of the href attribute
			 * to serve as a UID. This prevents clashes with other non-properly enqueued stylesheets on other pages.
			 *
			 * @var string $id
			 * @since v5.1.4
			 *
			 */
			if ( ! $id ) {
				// @codeCoverageIgnoreStart
				/**
				 * @since v6.3.10 Use the sanitized URL's length to make sure the generated handle doesn't change
				 *                when insignificant whitespace is added to (or removed from) the href attribute.
				 */
				$id = "$handle-" . strlen( $this->sanitize_url( $href['href'] ) );
				// @codeCoverageIgnoreEnd
			}

			$google_fonts[ $key ]['id']   = apply_filters( 'omgf_frontend_process_fonts_set', $id, $href );
			$google_fonts[ $key ]['link'] = $link;
			/**
			 * This is used for search/replace later on. This shouldn't be tampered with.
			 */
			$google_fonts[ $key ]['href'] = apply_filters( 'omgf_frontend_process_fonts_set_href', $href['href'], $link );
			/**
			 * The URL used for the actual request to the Google Fonts API. Browsers strip tabs and line breaks
			 * from URLs before requesting them, so we do the same. The href element is left untouched, because
			 * it's used for search/replace in the HTML.
			 *
			 * @since v6.3.10
			 */
			$google_fonts[ $key ]['url'] = $this->sanitize_url( $google_fonts[ $key ]['href'] );
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
	 * @param array $google_fonts A processable set generated by build_fonts_set().
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
			$handle          = strtolower( $stack['id'] );
			$original_handle = $handle;

			/**
			 * The URL is used for parsing and requesting the stylesheet, while $stack['href'] is used for
			 * search/replace and should always match the HTML verbatim.
			 *
			 * @since v6.3.10 Fallback to href, for font sets built by 3rd parties (or older versions of Pro.)
			 *                That fallback is sanitized here, because those sets don't contain a sanitized URL.
			 *                Sanitizing $stack['url'] again is a no-op.
			 */
			$url = $this->sanitize_url( $stack['url'] ?? $stack['href'] );

			/**
			 * @since v6.3.12 Only process (and request) stylesheets whose URL is actually hosted on the Google
			 *                Fonts API (or a compatible endpoint). The element was kept because one of its
			 *                attribute values points at the API (see get_element_urls()), but the href used
			 *                here could point at a different — e.g. internal — host. Checking here, before the
			 *                branches below, leaves a non-API link untouched (not removed, not swapped for a
			 *                cached file) and makes sure it's never requested, which prevents SSRF.
			 */
			if ( ! $this->is_font_api_url( $url ) ) {
				continue;
			}

			/**
			 * If the stylesheet with $handle is completely marked for unloading, just remove the element
			 * to prevent it from loading.
			 */
			if ( apply_filters(
				'omgf_unloaded_stylesheets',
				OMGF::unloaded_stylesheets() && in_array( $handle, OMGF::unloaded_stylesheets() )
			) ) {
				$search[ $key ]  = $stack['link']; // @codeCoverageIgnore
				$replace[ $key ] = ''; // @codeCoverageIgnore

				continue; // @codeCoverageIgnore
			}

			$cache_key = OMGF::get_cache_key( $stack['id'] );

			/**
			 * $cache_key is used for caching. $handle contains the original handle.
			 */
			if ( ( OMGF::unloaded_fonts() && $cache_key ) || apply_filters( 'omgf_frontend_update_cache_key', false ) ) {
				$handle = $cache_key;
			}

			/**
			 * Regular requests (in the frontend) will end here if the file exists.
			 */
			if ( ! OMGF::is_running_optimize() && file_exists( OMGF_UPLOAD_DIR . "/$handle/$handle.css" ) ) {
				$search[ $key ]  = $stack['href'];
				$replace[ $key ] = OMGF_UPLOAD_URL . "/$handle/$handle.css?ver=" . $this->timestamp;

				continue;
			}

			/**
			 * @since v5.3.7 decode URL and special HTML chars to make sure all params are properly processed later on.
			 */
			$href         = urldecode( htmlspecialchars_decode( $url ) );
			$parsed_query = wp_parse_url( $href, PHP_URL_QUERY );
			$query        = [];

			if ( $parsed_query ) {
				parse_str( $parsed_query, $query );
			}

			/**
			 * If required parameters aren't set, this request is most likely invalid. Let's just remove it.
			 */
			if ( apply_filters( 'omgf_frontend_process_invalid_request', ! isset( $query['family'] ), $href ) ) {
				$search[ $key ]  = $stack['link'];
				$replace[ $key ] = '';

				continue; // @codeCoverageIgnore
			}

			$optimize = new Optimize( $url, $handle, $original_handle, 'url', false, '', true );

			/**
			 * @var string $cached_url Absolute URL or empty string.
			 */
			$cached_url = $optimize->process();

			$search[ $key ]  = $stack['href'];
			/**
			 * @since v6.3.12 If optimization produced no URL (e.g. the request was blocked or redirected, the
			 *                fetch failed, or the response wasn't a stylesheet), leave the original link
			 *                untouched instead of removing it, so the fonts keep loading.
			 */
			$replace[ $key ] = $cached_url ? $cached_url . '?ver=' . $this->timestamp : $stack['href'];
		}

		return apply_filters( 'omgf_process_search_replace', [
			'search'  => $search,
			'replace' => $replace,
		] );
	}

	/**
	 * Parse $html for present iframes loading Google Fonts.
	 *
	 * @param $html
	 *
	 * @return void
	 */
	private function parse_iframes( $html ) {
		$found_iframes = OMGF::get_option( Settings::OMGF_DB_FOUND_IFRAMES, [] );
		$count_iframes = count( $found_iframes );

		foreach ( Dashboard::IFRAMES_LOADING_FONTS as $script_id => $script ) {
			if ( str_contains( $html, $script ) && ! in_array( $script_id, $found_iframes ) ) {
				$found_iframes[] = $script_id; // @codeCoverageIgnore
			}
		}

		if ( $count_iframes !== count( $found_iframes ) ) {
			OMGF::update_option( Settings::OMGF_DB_FOUND_IFRAMES, $found_iframes ); // @codeCoverageIgnore
		}
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

		if ( empty( $resource_hints[0] ) ) {
			return $html; // @codeCoverageIgnore
		}

		/**
		 * @since v5.1.5 Filter out any resource hints with a href pointing to Google Fonts' APIs.
		 * @since v5.2.1 Use preg_match() to exactly match an element's attribute, since 3rd party
		 *               plugins (e.g. Asset Cleanup) also tend to include their own custom attributes,
		 *               e.g. data-wpacu-to-be-preloaded, which would also match in strpos('preload', $match).
		 */
		$search = array_filter(
			$resource_hints[0],
			function ( $resource_hint ) {
				preg_match( '/href=[\'"](https?:)?\/\/(.*?)[\'"\/]/', $resource_hint, $url );
				preg_match( '/rel=[\'"](.*?)[ \'"]/', $resource_hint, $attr );

				if ( empty( $url[2] ) || empty( $attr[1] ) ) {
					return false; // @codeCoverageIgnore
				}

				$url  = $url[2];
				$attr = $attr[1];

				return ! empty( preg_grep( "/$url/", self::RESOURCE_HINTS_URLS ) ) && in_array( $attr, self::RESOURCE_HINTS_ATTR );
			}
		);

		return str_replace( $search, '', $html );
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

		do_action( 'omgf_return_buffer' );

		return apply_filters( 'omgf_buffer_output', $html );
	}
}
