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
			
			OMGF_Admin_Notice::set_notice( __( 'Cache directory successfully emptied.', $this->plugin_text_domain ) );
		} catch ( \Exception $e ) {
			OMGF_Admin_Notice::set_notice(
				__( 'Something went wrong while emptying the cache directory: ', $this->plugin_text_domain ) . $e->getMessage(),
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
}
