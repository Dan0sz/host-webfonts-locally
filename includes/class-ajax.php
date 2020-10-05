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

class OMGF_AJAX
{
	/** @var string $plugin_text_domain */
	private $plugin_text_domain = 'host-webfonts-local';
	
	/**
	 * OMGF_AJAX constructor.
	 */
	public function __construct () {
		add_action( 'wp_ajax_omgf_ajax_empty_dir', [ $this, 'empty_directory' ] );
		add_action( 'wp_ajax_omgf_ajax_optimize', [ $this, 'optimize' ] );
	}
	
	/**
	 * Empty cache directory.
	 */
	public function empty_directory () {
		try {
			$entries = array_filter( (array) glob( OMGF_FONTS_DIR . '/*' ) );
			
			foreach ( $entries as $entry ) {
				OMGF::delete( $entry );
			}
			
			delete_option( OMGF_Admin_Settings::OMGF_OPTIMIZE_SETTING_OPTIMIZED_FONTS );
			delete_option( OMGF_Admin_Settings::OMGF_OPTIMIZE_SETTING_UNLOAD_FONTS );
			delete_option( OMGF_Admin_Settings::OMGF_OPTIMIZE_SETTING_PRELOAD_FONTS );
			
			OMGF_Admin_Notice::set_notice( __( 'Cache directory successfully emptied.', $this->plugin_text_domain ) );
		} catch ( \Exception $e ) {
			OMGF_Admin_Notice::set_notice(
				__( 'OMGF encountered an error while emptying the cache directory: ', $this->plugin_text_domain ) . $e->getMessage(),
				'omgf-cache-error',
				true,
				'error',
				$e->getCode()
			);
		}
	}
	
	/**
	 * Fetch Download API URLs from Frontend and go through them one by one, to trigger an uncached call to the Download API.
	 */
	public function optimize () {
		$front_html = wp_remote_get(
			$this->no_cache( site_url() ),
			[
				'timeout' => 30
			]
		);
		
		if ( is_wp_error( $front_html ) ) {
			update_option( OMGF_Admin_Settings::OMGF_OPTIMIZATION_COMPLETE, false );
			
			OMGF_Admin_Notice::set_notice(
				__( 'OMGF encountered an error while fetching this site\'s frontend HTML', $this->plugin_text_domain ) . ': ' . $front_html->get_error_message(),
				'omgf-fetch-failed',
				true,
				'error',
				$front_html->get_error_code()
			);
		}
		
		$urls     = [];
		$document = new DOMDocument();
		@$document->loadHtml( wp_remote_retrieve_body( $front_html ) );
		
		foreach ( $document->getElementsByTagName( 'link' ) as $link ) {
			/** @var $link DOMElement */
			if ( $link->hasAttribute( 'href' ) && strpos( $link->getAttribute( 'href' ), '/omgf/v1/download/css' ) ) {
				$urls[] = $link->getAttribute( 'href' );
			}
		}
		
		if ( empty( $urls ) ) {
			$message = __( 'No Google Fonts found to optimize. <a href="#" class="omgf-empty">Empty the Cache Directory</a> to start over.', $this->plugin_text_domain );
			$info    = sprintf( __( 'If you believe this is an error, <a href="%s" target="_blank">click here</a> to trigger the optimization manually in your site\'s frontend.', $this->plugin_text_domain ), $this->no_cache( site_url() ) );
			$info    .= ' ' . __( 'If this message keeps appearing,', $this->plugin_text_domain );
			
			if ( apply_filters( 'apply_omgf_pro_promo', true ) ) {
				$info .= ' ' . sprintf( __( 'head over to the Support Forum and <a target="_blank" href="%s">shoot me a ticket</a>.', $this->plugin_text_domain ), 'https://wordpress.org/support/plugin/host-webfonts-local/' );
			} else {
				$info .= ' ' . sprintf( __( '<a target="_blank" href="%s">send me a support ticket</a>.', $this->plugin_text_domain ), 'https://ffwp.dev/contact/' );
			}
			
			OMGF_Admin_Notice::unset_notice( 'omgf-optimize', 'success' );
			OMGF_Admin_Notice::unset_notice( 'omgf-optimize-plugin-notice' );
			OMGF_Admin_Notice::unset_notice( 'omgf-optimize-background' );
			
			OMGF_Admin_Notice::set_notice(
				$message,
				'omgf-fonts-not-found',
				false,
				'error',
				404
			);
			
			OMGF_Admin_Notice::set_notice(
				$info,
				'omgf-support-info',
				true,
				'info'
			);
		}
		
		foreach ( $urls as $url ) {
			$download = wp_remote_get(
				$this->no_cache( $url ),
				[
					'timeout' => 30
				]
			);
			
			if ( is_wp_error( $download ) ) {
				update_option( OMGF_Admin_Settings::OMGF_OPTIMIZATION_COMPLETE, false );
				
				OMGF_Admin_Notice::set_notice(
					__( 'OMGF encountered an error while downloading Google Fonts', $this->plugin_text_domain ) . ': ' . $download->get_error_message(),
					'omgf-download-failed',
					true,
					'error',
					$download->get_error_code()
				);
			}
		}
		
		update_option( OMGF_Admin_Settings::OMGF_OPTIMIZATION_COMPLETE, true );
	}
	
	/**
	 * @param $url
	 *
	 * @return string
	 */
	private function no_cache ( $url ) {
		return add_query_arg( [ 'nocache' => substr( md5( microtime() ), rand( 0, 26 ), 5 ) ], $url );
	}
}
