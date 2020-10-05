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
	
	/** @var bool $do_optimize */
	private $do_optimize;
	
	/**
	 * OMGF_Frontend_Functions constructor.
	 */
	public function __construct () {
		$this->do_optimize = $this->maybe_optimize_fonts();
		
		add_filter( 'content_url', [ $this, 'rewrite_url' ], 10, 2 );
		add_action( 'wp_print_styles', [ $this, 'process_fonts' ], PHP_INT_MAX - 1000 );
		// TODO: Add Preload Fonts. Filter optimized fonts option by the selected preload fonts. Then get the woff2 URL for those fonts and preload them.
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
		
		// TODO: Add preload here.
		
		if ( apply_filters( 'omgf_pro_advanced_processing_enabled', false ) ) {
			return;
		}
		
		if ( is_admin() ) {
			return;
		}
		
		switch ( OMGF_FONT_PROCESSING ) {
			case 'remove':
				add_action( 'wp_print_styles', [ $this, 'remove_registered_fonts' ], PHP_INT_MAX - 500 );
				break;
			default:
				add_action( 'wp_print_styles', [ $this, 'replace_registered_fonts' ], PHP_INT_MAX - 500 );
		}
	}
	
	/**
	 * This function contains a nice little hack, to avoid messing with potential dependency issues. We simply set the source to an empty string!
	 */
	public function remove_registered_fonts () {
		global $wp_styles;
		
		$registered = $wp_styles->registered;
		$fonts      = apply_filters( 'omgf_auto_remove', $this->detect_registered_google_fonts( $registered ) );
		
		foreach ( $fonts as $handle => $font ) {
			$wp_styles->registered [ $handle ]->src = '';
		}
	}
	
	/**
	 * Retrieve stylesheets from Google Fonts' API and modify the stylesheet for local storage.
	 */
	public function replace_registered_fonts () {
		global $wp_styles;
		
		$registered = $wp_styles->registered;
		$fonts      = apply_filters( 'omgf_auto_replace', $this->detect_registered_google_fonts( $registered ) );
		
		foreach ( $fonts as $handle => $font ) {
			$updated_handle = $handle;
			
			if ( $unloaded_fonts = omgf_init()::unloaded_fonts() ) {
				$updated_handle = $handle . '-' . strlen(json_encode($unloaded_fonts));
			}
			
			$cached_file = OMGF_CACHE_PATH . '/' . $updated_handle . "/$updated_handle.css";
			
			if ( file_exists( WP_CONTENT_DIR . $cached_file ) ) {
				$wp_styles->registered[ $handle ]->src = content_url( $cached_file );
				
				continue;
			}
			
			if ( OMGF_OPTIMIZATION_MODE == 'auto' || ( OMGF_OPTIMIZATION_MODE == 'manual' && isset( $_GET['omgf_optimize'] ) ) ) {
				$wp_styles->registered[ $handle ]->src = str_replace( [ 'https://fonts.googleapis.com/', '//fonts.googleapis.com/' ], site_url( '/wp-json/omgf/v1/download/' ), $font->src ) . "&handle=$updated_handle&original_handle=$handle";
			}
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
	 * @param $url
	 * @param $path
	 *
	 * @return mixed
	 */
	public function rewrite_url ( $url, $path ) {
		/**
		 * Exit early if this isn't requested by OMGF.
		 */
		if ( strpos( $url, OMGF_CACHE_PATH ) === false ) {
			return $url;
		}
		
		/**
		 * If Relative URLs is enabled, overwrite URL with Path and continue execution.
		 */
		if ( OMGF_RELATIVE_URL ) {
			$content_dir = str_replace( site_url(), '', content_url() );
			
			$url = $content_dir . $path;
		}
		
		if ( OMGF_CDN_URL ) {
			$url = str_replace( site_url(), OMGF_CDN_URL, $url );
		}
		
		if ( OMGF_CACHE_URI ) {
			$url = str_replace( OMGF_CACHE_PATH, OMGF_CACHE_URI, $url );
		}
		
		return $url;
	}
}
