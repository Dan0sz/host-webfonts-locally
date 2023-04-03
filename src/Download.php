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
* @copyright: © 2023 Daan van den Bergh
* @url      : https://daan.dev
* * * * * * * * * * * * * * * * * * * */

namespace OMGF;

use OMGF\Admin\Notice;

defined( 'ABSPATH' ) || exit;

class Download {
	/** @var string $url */
	private $url;

	/** @var string $filename */
	private $filename;

	/** @var string $extension */
	private $extension;

	/** @var string $path */
	private $path;

	/**
	 * OMGF\Download constructor.
	 */
	public function __construct(
		string $url,
		string $filename,
		string $extension,
		string $path
	) {
		$this->url       = $url;
		$this->filename  = $filename;
		$this->extension = $extension;
		$this->path      = $path;
	}

	/**
	 * Download $url to $path and return OMGF_UPLOAD_URL to $filename.
	 * 
	 * @return string 
	 * 
	 * @throws SodiumException 
	 * @throws TypeError 
	 */
	public function download() {
		if ( ! function_exists( 'download_url' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		wp_mkdir_p( $this->path );

		$file     = $this->path . '/' . $this->filename . '.' . $this->extension;
		$file_url = OMGF_UPLOAD_URL . str_replace( OMGF_UPLOAD_DIR, '', $this->path ) . '/' . $this->filename . '.' . $this->extension;

		if ( file_exists( $file ) ) {
			return $file_url;
		}

		if ( strpos( $this->url, '//' ) === 0 ) {
			$this->url = 'https:' . $this->url;
		}

		$tmp = download_url( $this->url );

		if ( is_wp_error( $tmp ) ) {
			/** @var WP_Error $tmp */
			Notice::set_notice( __( 'OMGF encountered an error while downloading fonts', 'host-webfonts-local' ) . ': ' . $tmp->get_error_message(), 'omgf-download-failed', 'error', $tmp->get_error_code() );

			return '';
		}

		/** @var string $tmp */
		copy( $tmp, $file );
		@unlink( $tmp );

		return $file_url;
	}
}
