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
* @copyright: © 2025 Daan van den Bergh
* @url      : https://daan.dev
* * * * * * * * * * * * * * * * * * * */

namespace OMGF;

use OMGF\Admin\Notice;

class Download {
	/** @var string $url */
	private $url;

	/** @var string $filename */
	private $filename;

	/** @var string $path */
	private $path;

	private $mime_map = [
		'font/woff2'                    => 'woff2',
		'application/font-woff2'        => 'woff2',
		'font/woff'                     => 'woff',
		'application/font-woff'         => 'woff',
		'font/ttf'                      => 'ttf',
		'application/x-font-ttf'        => 'ttf',
		'font/sfnt'                     => 'ttf', // Can be WOFF2 or TTF, but we pick TTF.
		'application/font-sfnt'         => 'ttf',
		'font/otf'                      => 'otf',
		'application/x-font-opentype'   => 'otf',
		'application/vnd.ms-fontobject' => 'eot',
	];

	/**
	 * OMGF\Download constructor.
	 */
	public function __construct(
		string $url,
		string $filename,
		string $path
	) {
		$this->url      = $url;
		$this->filename = $filename;
		$this->path     = $path;
	}

	/**
	 * Download $url to $path and return OMGF_UPLOAD_URL to $filename.
	 *
	 * @return string
	 * @throws SodiumException
	 * @throws TypeError
	 */
	public function download() {
		wp_mkdir_p( $this->path );

		if ( str_starts_with( $this->url, '//' ) ) {
			$this->url = 'https:' . $this->url; // @codeCoverageIgnore
		}

		$temp_filename = $this->path . '/' . $this->filename . '.tmp';

		$response = wp_safe_remote_get(
			$this->url,
			[
				'timeout'  => 300,
				'stream'   => true,
				'filename' => $temp_filename,
			]
		);

		if ( is_wp_error( $response ) ) {
			Notice::set_notice(
				__( 'OMGF encountered an error while downloading font files', 'host-webfonts-local' ) . ': ' . $response->get_error_message(),
				'omgf-download-failed',
				'error',
				$response->get_error_code()
			);

			return '';
		}

		$content_type = wp_remote_retrieve_header( $response, 'content-type' );
		$extension    = $this->mime_map[ $content_type ] ?? 'woff2';

		if ( file_exists( $temp_filename ) ) {
			rename( $temp_filename, $this->path . '/' . $this->filename . '.' . $extension );
		}

		return OMGF_UPLOAD_URL . str_replace( OMGF_UPLOAD_DIR, '', $this->path ) . '/' . $this->filename . '.' . $extension;
	}
}
