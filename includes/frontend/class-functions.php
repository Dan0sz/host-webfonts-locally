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

defined( 'ABSPATH' ) || exit;

class OMGF_Frontend_Functions
{
	const OMGF_STYLE_HANDLE = 'omgf-fonts';
	
	/** @var OMGF_DB */
	private $db;
	
	/** @var string */
	private $stylesheet_file;
	
	/** @var string */
	private $stylesheet_url;
	
	/** @var bool $do_optimize */
	private $do_optimize;
	
	/**
	 * OMGF_Frontend_Functions constructor.
	 */
	public function __construct () {
		$this->stylesheet_file = OMGF_FONTS_DIR . '/' . OMGF_FILENAME;
		$this->stylesheet_url  = OMGF_FONTS_URL . '/' . OMGF_FILENAME;
		$this->do_optimize     = $this->maybe_optimize_fonts();
		
		// @formatter:off
		add_action( 'wp_print_styles', [ $this, 'is_remove_google_fonts_enabled' ], PHP_INT_MAX - 1000 );
		
		if ( file_exists( $this->stylesheet_file ) ) {
			add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_stylesheet' ], OMGF_ENQUEUE_ORDER );
		}
		
		if ( OMGF_AUTO_DETECT_ENABLED ) {
			add_action( 'wp_print_styles', [ $this, 'auto_detect_fonts' ], PHP_INT_MAX - 10000 );
		}
		
		$this->db = new OMGF_DB();
		// Needs to be loaded before stylesheet.
		add_action( 'wp_head', [ $this, 'preload_fonts' ], 1 );
		// @formatter:on
	}
	
	/**
	 * Check if the Remove Google Fonts option is enabled.
	 */
	public function is_remove_google_fonts_enabled () {
		if ( ! $this->do_optimize ) {
			return;
		}
		
		if ( OMGF_REMOVE_GFONTS == 'on' && ! is_admin() ) {
			// @formatter:off
			add_action( 'wp_print_styles', [ $this, 'remove_google_fonts' ], PHP_INT_MAX - 500 );
			// Theme: Enfold
			add_filter( 'avf_output_google_webfonts_script', function () { return false; } );
			// @formatter:on
		}
	}
	
	/**
	 * Should we optimize for logged in editors/administrators?
	 *
	 * @return bool
	 */
	private function maybe_optimize_fonts () {
		if ( ! OMGF_OPTIMIZE_EDIT_ROLES && current_user_can( 'edit_pages' ) ) {
			return false;
		}
		
		return true;
	}
	
	/**
	 * Automatically dequeues any stylesheets loaded from fonts.gstatic.com or
	 * fonts.googleapis.com. Also checks for stylesheets dependant on Google Fonts and
	 * re-enqueues and registers them.
	 */
	public function remove_google_fonts () {
		if ( apply_filters( 'omgf_pro_auto_remove_enabled', false ) ) {
			return;
		}
		
		global $wp_styles;
		
		$registered   = $wp_styles->registered;
		$fonts        = $this->detect_registered_google_fonts( $registered );
		$dependencies = array_filter(
			$registered,
			function ( $contents ) use ( $fonts ) {
				return ! empty( array_intersect( array_keys( $fonts ), $contents->deps ) )
				       && $contents->handle !== 'wp-block-editor';
			}
		);
		
		foreach ( $fonts as $font ) {
			wp_deregister_style( $font->handle );
			wp_dequeue_style( $font->handle );
		}
		
		foreach ( $dependencies as $dependency ) {
			/**
			 * If Remove Google Fonts is enabled, but no stylesheet is generated, there's no need to add OMGF's stylesheet
			 * as a dependency.
			 */
			$deps = array_diff( $dependency->deps, array_keys( $fonts ) ) + ( isset( $registered['omgf-fonts'] ) ? [ 'omgf-fonts' ] : [] );
			wp_deregister_style( $dependency->handle );
			wp_dequeue_style( $dependency->handle );
			
			wp_register_style( $dependency->handle, $dependency->src, $deps );
			wp_enqueue_style( $dependency->handle, $dependency->src, $deps );
		}
	}
	
	/**
	 * @param $registered_styles
	 *
	 * @return array
	 */
	private function detect_registered_google_fonts ( $registered_styles ) {
		return array_filter(
			$registered_styles,
			function ( $contents ) {
				return strpos( $contents->src, 'fonts.googleapis.com/css' ) !== false
				       || strpos( $contents->src, 'fonts.gstatic.com' ) !== false;
			}
		);
	}
	
	/**
	 * Once the stylesheet is generated. We can enqueue it.
	 */
	public function enqueue_stylesheet () {
		if ( ! $this->do_optimize ) {
			return;
		}
		
		if ( OMGF_WEB_FONT_LOADER ) {
			$this->get_template( 'web-font-loader' );
		} else {
			wp_enqueue_style( self::OMGF_STYLE_HANDLE, OMGF_FONTS_URL . '/' . OMGF_FILENAME, [], ( OMGF_REMOVE_VERSION ? null : OMGF_STATIC_VERSION ) );
		}
	}
	
	/**
	 * @param $name
	 */
	public function get_template ( $name ) {
		include OMGF_PLUGIN_DIR . 'templates/frontend-' . $name . '.phtml';
	}
	
	/**
	 * Saves the used Google Fonts in the database, so it can be used by auto-detection.
	 */
	public function auto_detect_fonts () {
		if ( apply_filters( 'omgf_pro_auto_detect_enabled', false ) ) {
			return;
		}
		
		global $wp_styles;
		
		$registered = $wp_styles->registered;
		
		$fonts = $this->detect_registered_google_fonts( $registered );
		
		foreach ( $fonts as $font ) {
			$google_fonts_src[] = $font->src;
		}
		
		update_option( OMGF_Admin_Settings::OMGF_SETTING_DETECTED_FONTS, json_encode( $google_fonts_src ) );
	}
	
	/**
	 * Collect and render preload fonts in wp_head().
	 */
	public function preload_fonts () {
		if ( ! $this->do_optimize ) {
			return;
		}
		
		$preload_fonts = $this->db->get_preload_fonts();
		
		if ( ! $preload_fonts ) {
			return;
		}
		
		foreach ( $preload_fonts as $font ) {
			$font_urls[] = array_values(
				array_filter(
					(array) $font,
					function ( $properties ) {
						return strpos( $properties, 'woff2_local' ) !== false;
					},
					ARRAY_FILTER_USE_KEY
				)
			);
		}
		
		$urls = array_reduce( $font_urls, 'array_merge', [] );
		
		foreach ( $urls as $url ) {
			echo "<link rel='preload' href='$url' as='font' type='font/" . pathinfo( $url, PATHINFO_EXTENSION ) . "' crossorigin />\n";
		}
	}
}
