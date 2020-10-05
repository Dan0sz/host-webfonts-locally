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

class OMGF_Optimize
{
	/** @var string $plugin_text_domain */
	private $plugin_text_domain = 'host-webfonts-local';
	
	/** @var mixed|string $optimization_mode */
	private $optimization_mode = '';
	
	/**
	 * OMGF_Optimize constructor.
	 */
	public function __construct () {
		$option_page             = $_POST['option_page'] ?? '';
		$this->optimization_mode = $_POST[ OMGF_Admin_Settings::OMGF_OPTIMIZE_SETTING_OPTIMIZATION_MODE ] ?? '';
		
		if ( $option_page != OMGF_Admin_Settings::OMGF_SETTINGS_FIELD_OPTIMIZE
		     && ! $this->optimization_mode ) {
			return;
		}
		
		// Will die when it fails.
		check_admin_referer( 'omgf-optimize-settings-options' );
		
		update_option(OMGF_Admin_Settings::OMGF_OPTIMIZE_SETTING_UNLOAD_FONTS, $_POST[OMGF_Admin_Settings::OMGF_OPTIMIZE_SETTING_UNLOAD_FONTS]);
		update_option(OMGF_Admin_Settings::OMGF_OPTIMIZE_SETTING_PRELOAD_FONTS, $_POST[OMGF_Admin_Settings::OMGF_OPTIMIZE_SETTING_PRELOAD_FONTS]);
		
		if ( $this->optimization_mode == 'manual' ) {
			$this->run_manual();
		}
		
		if ( $this->optimization_mode == 'auto' ) {
			$this->run_auto();
		}
	}
	
	/**
	 *
	 */
	private function run_manual () {
		$url = esc_url_raw( $_POST[ OMGF_Admin_Settings::OMGF_OPTIMIZE_SETTING_MANUAL_OPTIMIZE_URL ] );
		
		$front_html = wp_remote_get(
			$this->no_cache_optimize_url( $url ),
			[
				'timeout' => 30
			]
		);
		
		if ( is_wp_error( $front_html ) ) {
			$this->frontend_fetch_failed( $front_html );
		}
		
		$urls     = [];
		$document = new DOMDocument();
		@$document->loadHtml( wp_remote_retrieve_body( $front_html ) );
		
		foreach ( $document->getElementsByTagName( 'link' ) as $link ) {
			/** @var $link DOMElement */
			if ( $link->hasAttribute( 'href' ) && strpos( $link->getAttribute( 'href' ), '/omgf/v1/download/' ) ) {
				$urls[] = $link->getAttribute( 'href' );
			}
		}
		
		if ( empty( $urls ) ) {
			$this->no_urls_found();
		}
		
		foreach ( $urls as $url ) {
			$download = wp_remote_get(
				$this->no_cache_optimize_url( $url ),
				[
					'timeout' => 30
				]
			);
			
			if ( is_wp_error( $download ) ) {
				$this->download_failed( $download );
			}
		}
		
		$this->optimization_succeeded();
	}
	
	/**
	 *
	 */
	private function optimization_succeeded () {
		OMGF_Admin_Notice::set_notice(
			__( 'Manual optimization completed successfully.', $this->plugin_text_domain ),
			'omgf-optimization-success',
			false
		);
	}
	
	/**
	 * @param $download WP_Error
	 */
	private function download_failed ( $download ) {
		OMGF_Admin_Notice::set_notice(
			__( 'OMGF encountered an error while downloading Google Fonts', $this->plugin_text_domain ) . ': ' . $download->get_error_message(),
			'omgf-download-failed',
			false,
			'error',
			$download->get_error_code()
		);
	}
	
	/**
	 * @param $front_html WP_Error
	 */
	private function frontend_fetch_failed ( $front_html ) {
		OMGF_Admin_Notice::set_notice(
			__( 'OMGF encountered an error while fetching this site\'s frontend HTML', $this->plugin_text_domain ) . ': ' . $front_html->get_error_message(),
			'omgf-frontend-fetch-failed',
			false,
			'error',
			$front_html->get_error_code()
		);
	}
	
	/**
	 *
	 */
	private function no_urls_found () {
		OMGF_Admin_Notice::set_notice(
			__( 'No (additional) Google Fonts found to optimize. Did you run it already?', $this->plugin_text_domain ),
			'omgf-no-urls-found',
			false,
			'warning'
		);
	}
	
	/**
	 *
	 */
	private function run_auto () {
		OMGF_Admin_Notice::set_notice(
			__( 'OMGF Optimization is silently running in the background. After visiting a few pages, return here to manage the captured Google Fonts.' ),
			'omgf-auto-running',
			false
		);
	}
	
	/**
	 * @param $url
	 *
	 * @return string
	 */
	private function no_cache_optimize_url ( $url ) {
		return add_query_arg( [ 'omgf_optimize' => 1, 'nocache' => substr( md5( microtime() ), rand( 0, 26 ), 5 ) ], $url );
	}
}