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
	
	/** @var mixed|void $action */
	private $action;
	
	/**
	 * OMGF_Frontend_Functions constructor.
	 */
	public function __construct () {
		$this->stylesheet_file = OMGF_FONTS_DIR . '/' . OMGF_FILENAME;
		$this->stylesheet_url  = OMGF_FONTS_URL . '/' . OMGF_FILENAME;
		$this->do_optimize     = $this->maybe_optimize_fonts();
		
		add_action( 'wp_print_styles', [ $this, 'process_fonts' ], PHP_INT_MAX - 1000 );
		
		if ( file_exists( $this->stylesheet_file ) ) {
			add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_stylesheet' ], OMGF_ENQUEUE_ORDER );
		}
		
		$this->db = new OMGF_DB();
		
		// Needs to be loaded before stylesheet.
		add_action( 'wp_head', [ $this, 'preload_fonts' ], 1 );
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
	 * Check if the Remove Google Fonts option is enabled.
	 */
	public function process_fonts () {
		if ( ! $this->do_optimize ) {
			return;
		}
		
		if ( apply_filters( 'omgf_pro_auto_processing_enabled', false ) ) {
			return;
		}
		
		if ( is_admin() ) {
			return;
		}
		
		switch ( OMGF_FONT_PROCESSING ) {
			case 'remove':
				add_action( 'wp_print_styles', [ $this, 'remove_fonts' ], PHP_INT_MAX - 500 );
				break;
			default:
				add_action( 'wp_print_styles', [ $this, 'replace_fonts' ], PHP_INT_MAX - 500 );
		}
	}
	
	/**
	 * This function contains a nice little hack, to avoid messing with potential dependency issues. We simply set the source to an empty string!
	 */
	public function remove_fonts () {
		global $wp_styles;
		
		$registered   = $wp_styles->registered;
		$fonts        = apply_filters( 'omgf_auto_remove', $this->detect_registered_google_fonts( $registered ) );
		
		foreach ( $fonts as $handle => $font ) {
			$wp_styles->registered [ $handle ]->src = '';
		}
	}
	
	/**
	 * Retrieve stylesheets from Google Fonts' API and modify the stylesheet for local storage.
	 */
	public function replace_fonts () {
		global $wp_styles;
		
		$registered = $wp_styles->registered;
		$fonts      = apply_filters( 'omgf_auto_replace', $this->detect_registered_google_fonts( $registered ) );
		
		foreach ( $fonts as $handle => $font ) {
			$cached_file = OMGF_CACHE_PATH . '/' . $handle . "/$handle.css";
			
			if ( file_exists( WP_CONTENT_DIR . $cached_file ) ) {
				$wp_styles->registered[ $handle ]->src = content_url( $cached_file );
				
				continue;
			}
			
			$wp_styles->registered[ $handle ]->src = str_replace( 'https://fonts.googleapis.com/', site_url( '/wp-json/omgf/v1/download/' ), $font->src ) . "&handle=$handle";
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
