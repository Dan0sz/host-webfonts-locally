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
		
		add_action( 'wp_print_styles', [ $this, 'process_fonts' ], PHP_INT_MAX - 1000 );
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
	 * @param $name
	 */
	public function get_template ( $name ) {
		include OMGF_PLUGIN_DIR . 'templates/frontend-' . $name . '.phtml';
	}
}
