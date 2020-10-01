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
				$this->delete( $entry );
			}
			
			set_transient( OMGF_Admin_Settings::OMGF_OPTIMIZATION_COMPLETE, false );
			
			OMGF_Admin_Notice::set_notice( __( 'Cache directory successfully emptied.', $this->plugin_text_domain ) );
		} catch ( \Exception $e ) {
			OMGF_Admin_Notice::set_notice(
				__( 'Something went wrong while emptying the cache directory: ', $this->plugin_text_domain ) . $e->getMessage(),
				'omgf-cache-error',
				true,
				'error',
				$e->getCode()
			);
		}
	}
	
	/**
	 * @param $entry
	 */
	public function delete ( $entry ) {
		if ( is_dir( $entry ) ) {
			$file = new \FilesystemIterator( $entry );
			
			// If dir is empty, valid() returns false.
			while ( $file->valid() ) {
				$this->delete( $file->getPathName() );
				$file->next();
			}
			
			rmdir( $entry );
		} else {
			unlink( $entry );
		}
	}
	
	/**
	 * Fetch Download API URLs from Frontend and go through them one by one, to trigger an uncached call to the Download API.
	 */
	public function optimize () {
		$front_html = wp_remote_get(
			site_url(),
			[
				'timeout'   => 10
			]
		);
		
		if ( is_wp_error( $front_html ) ) {
			return;
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
			
			if ( apply_filters( 'apply_omgf_pro_promo', true ) ) {
				$message .= ' ' . sprintf( __( 'Or <a target="_blank" href="%s">Upgrade to OMGF Pro</a> to capture requests throughout the entire HTML document.', $this->plugin_text_domain ), OMGF_Admin_Settings::FFWP_WORDPRESS_PLUGINS_OMGF_PRO );
			}
			
			OMGF_Admin_Notice::set_notice(
				$message,
				'omgf-fonts-not-found',
				true,
				'error',
				404
			);
		}
		
		foreach ( $urls as $url ) {
			$download = wp_remote_get(
				add_query_arg( [ 'nocache' => substr( md5( microtime() ), rand( 0, 26 ), 5 ) ], $url ),
				[
					'timeout'   => 10
				]
			);
			
			if ( is_wp_error( $download ) ) {
				set_transient( OMGF_Admin_Settings::FFWP_WORDPRESS_PLUGINS_OMGF_PRO, false );
				
				OMGF_Admin_Notice::set_notice(
					__( 'Something went wrong while downloading Google Fonts', $this->plugin_text_domain ) . ': ' . $download->get_error_message(),
					'omgf-download-failed',
					true,
					'error',
					$download->get_error_code()
				);
			}
		}
		
		set_transient( OMGF_Admin_Settings::OMGF_OPTIMIZATION_COMPLETE, true );
		
		OMGF_Admin_Notice::set_notice(
			__( 'OMGF has finished optimizing your Google Fonts. Enjoy! :-)', $this->plugin_text_domain ),
			'omgf-optimize',
			false
		);
		
		OMGF_Admin_Notice::set_notice(
			'<em>' . __( 'If you\'re using any CSS minify/combine and/or Full Page Caching plugins, don\'t forget to flush their caches.', $this->plugin_text_domain ) . '</em>',
			'omgf-optimize-plugin-notice',
			false,
			'info'
		);
		
		OMGF_Admin_Notice::set_notice(
			__( 'OMGF will keep running silently in the background and will generate additional stylesheets when other Google Fonts are found on any of your pages.', $this->plugin_text_domain ),
			'omgf-optimize-background',
			true,
			'info'
		);
	}
}
