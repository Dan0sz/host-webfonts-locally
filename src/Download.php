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
	 *
	 * @codeCoverageIgnore Because too many edge cases and error handling. We'll notice soon enough if downloads fail.
	 */
	public function download() {
		wp_mkdir_p( $this->path );

		if ( str_starts_with( $this->url, '//' ) ) {
			$this->url = 'https:' . $this->url;
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
			if ( file_exists( $temp_filename ) ) {
				unlink( $temp_filename );
			}

			Notice::set_notice(
				__( 'OMGF encountered an error while downloading font files', 'host-webfonts-local' ) . ': ' . $response->get_error_message(),
				'omgf-download-failed',
				'error',
				$response->get_error_code()
			);

			return '';
		}

		$code = wp_remote_retrieve_response_code( $response );

		// Handle non-success HTTP status codes.
		if ( $code < 200 || $code >= 300 ) {
			if ( file_exists( $temp_filename ) ) {
				unlink( $temp_filename );
			}

			Notice::set_notice(
				__( 'OMGF received a non-success HTTP status while downloading', 'host-webfonts-local' ) . ': ' . $code . ' ' . $this->url,
				'omgf-file-download-failed',
				'error',
				$code ?: 500
			);

			return '';
		}

		$content_type = wp_remote_retrieve_header( $response, 'content-type' );

		if ( ! $content_type ) {
			Notice::set_notice(
				__( 'OMGF couldn\'t determine the mime-type for the downloaded font file', 'host-webfonts-local' ) . ': ' . $this->filename,
				'omgf-download-mime-type-failed',
				'error',
				500
			);

			return '';
		}

		// Normalize Content-Type before lookup (strip parameters, lowercase)
		$content_type = strtolower( trim( explode( ';', $content_type )[ 0 ] ) );
		$extension    = $this->mime_map[ $content_type ] ?? '';

		if ( ! $extension ) {
			OMGF::debug(
				sprintf(
					'Unexpected Content-Type "%s" for font file "%s" from URL "%s"',
					$content_type,
					$this->filename,
					$this->url
				)
			);

			Notice::set_notice(
				__( 'OMGF couldn\'t determine the file extension for the downloaded font file', 'host-webfonts-local' ) . ': ' . $this->filename,
				'omgf-download-extension-failed',
				'error',
				500
			);

			return '';
		}

		if ( file_exists( $temp_filename ) ) {
			$final_path = $this->path . '/' . $this->filename . '.' . $extension;

			if ( ! rename( $temp_filename, $final_path ) ) {
				Notice::set_notice(
					__( 'OMGF failed to move downloaded file to final location', 'host-webfonts-local' ) . ': ' . $this->filename,
					'omgf-rename-failed',
					'error',
					500
				);

				// Clean up the temp file
				if ( file_exists( $temp_filename ) ) {
					unlink( $temp_filename );
				}

				return '';
			}
		}

		return OMGF_UPLOAD_URL . str_replace( OMGF_UPLOAD_DIR, '', $this->path ) . '/' . $this->filename . '.' . $extension;
	}
}
