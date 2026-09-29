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
* @copyright: © 2026 Daan van den Bergh
* @url      : https://daan.dev
* * * * * * * * * * * * * * * * * * * */

namespace OMGF;

use OMGF\Helper as OMGF;
use OMGF\Admin\Notice;
use OMGF\Admin\Settings;

class Download {
	/**
	 * Used to map Mime Types to file extensions.
	 * The most likely MIME type is at the top, so it can be used with array_search().
	 */
	const MIME_MAP = [
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
	 * Default maximum size (in bytes) of a downloaded font file.
	 *
	 * @since v6.3.12
	 */
	const MAX_FILE_SIZE = 25 * MB_IN_BYTES;

	/**
	 * Maximum number of failed downloads reported on the Dashboard.
	 *
	 * @since v6.3.12
	 */
	const MAX_FAILURES = 20;

	/**
	 * Reasons why a downloaded file was discarded.
	 */
	const FAILURE_EMPTY = 'empty';

	const FAILURE_TOO_LARGE = 'too_large';

	const FAILURE_INVALID = 'invalid';


	/** @var string $url */
	private $url;

	/** @var string $filename */
	private $filename;

	/** @var string $path */
	private $path;

	/**
	 * OMGF\Download constructor.
	 */
	public function __construct(
		string $url,
		string $filename,
		string $path
	) {
		$this->url = $url;
		/**
		 * @since v6.3.12 Sanitize the filename so a font family (which it's partly built from) can't contain a
		 *                path separator and write the downloaded file outside of $path.
		 */
		$this->filename = sanitize_file_name( $filename );
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

		/**
		 * @since v6.3.11 Use an unguessable name for the temporary file, because $this->path is inside the
		 *                uploads directory, i.e. it's publicly accessible.
		 */
		$temp_filename = $this->path . '/' . $this->filename . '-' . wp_generate_password( 12, false ) . '.tmp';

		$max_file_size = self::get_max_file_size();
		$response      = wp_safe_remote_get(
			$this->url,
			[
				'timeout'             => 300,
				'stream'              => true,
				'filename'            => $temp_filename,
				/**
				 * @since v6.3.12 Stop downloading once the file exceeds the maximum size. One extra byte is requested, so
				 *                a file which was cut off can be told apart from a file of exactly the maximum size.
				 */
				'limit_response_size' => $max_file_size + 1,
			]
		);

		if ( is_wp_error( $response ) ) {
			$this->delete_temp_file( $temp_filename );

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
			$this->delete_temp_file( $temp_filename );

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
			$this->delete_temp_file( $temp_filename );

			Notice::set_notice(
				__( 'OMGF couldn\'t determine the mime-type for the downloaded font file', 'host-webfonts-local' ) . ': ' . $this->filename,
				'omgf-download-mime-type-failed',
				'error',
				500
			);

			return '';
		}

		// Normalize Content-Type before lookup (strip parameters, lowercase)
		$content_type = strtolower( trim( explode( ';', $content_type )[0] ) );
		$extension    = self::MIME_MAP[ $content_type ] ?? '';

		if ( ! $extension ) {
			$this->delete_temp_file( $temp_filename );

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

		/**
		 * @since v6.3.12 Validate the downloaded file before it's stored.
		 */
		$reason = $this->validate_file( $temp_filename, $max_file_size );

		if ( $reason ) {
			$this->delete_temp_file( $temp_filename );

			/**
			 * Reported on the Dashboard (and in the Admin Bar), because downloads often run during a visitor's request.
			 *
			 * @see \OMGF\Admin\Dashboard::render_download_failures()
			 */
			self::add_failure( $this->url, $reason );

			return '';
		}

		if ( file_exists( $temp_filename ) ) {
			$final_path = $this->path . '/' . $this->filename . '.' . $extension;

			if ( ! rename( $temp_filename, $final_path ) ) {
				Notice::set_notice(
					__( 'OMGF failed to move downloaded file to final location', 'host-webfonts-local' ) . ': ' . $final_path,
					'omgf-rename-failed',
					'error',
					500
				);

				// Clean up the temp file
				$this->delete_temp_file( $temp_filename );

				return '';
			}
		}

		self::remove_failure( $this->url );

		return OMGF_UPLOAD_URL . str_replace( OMGF_UPLOAD_DIR, '', $this->path ) . '/' . $this->filename . '.' . $extension;
	}

	/**
	 * Maximum size (in bytes) of a downloaded font file.
	 *
	 * @since  v6.3.12
	 * @filter omgf_download_max_file_size
	 *
	 * @return int
	 */
	public static function get_max_file_size() {
		return max( 1, (int) apply_filters( 'omgf_download_max_file_size', self::MAX_FILE_SIZE ) );
	}

	/**
	 * Checks if $file is a complete font file, i.e. not empty, not cut off because it exceeds the maximum size, and
	 * starting with the signature of a font format.
	 *
	 * @since v6.3.12
	 *
	 * @param string $file
	 * @param int    $max_file_size
	 *
	 * @return string An error message if the file is invalid, an empty string if it's valid.
	 */
	private function validate_file( $file, $max_file_size ) {
		$size = file_exists( $file ) ? filesize( $file ) : 0;

		if ( ! $size ) {
			return self::FAILURE_EMPTY;
		}

		if ( $size > $max_file_size ) {
			return self::FAILURE_TOO_LARGE;
		}

		if ( ! self::is_font_file( $file ) ) {
			return self::FAILURE_INVALID;
		}

		return '';
	}

	/**
	 * Failed downloads, most recent first.
	 *
	 * @since v6.3.12
	 *
	 * @return array [ url => [ 'reason' => string, 'time' => int ] ]
	 */
	public static function get_failures() {
		return self::normalize_failures( get_option( Settings::OMGF_DB_DOWNLOAD_FAILURES, [] ) );
	}

	/**
	 * Removes invalid entries and sorts the failed downloads, most recent first.
	 *
	 * @since v6.3.12
	 *
	 * @param mixed $failures
	 *
	 * @return array
	 */
	private static function normalize_failures( $failures ) {
		if ( ! is_array( $failures ) ) {
			return []; // @codeCoverageIgnore
		}

		$failures = array_filter(
			$failures,
			function ( $failure, $url ) {
				return is_string( $url ) && is_array( $failure ) && in_array( $failure['reason'] ?? '', self::get_failure_reasons(), true );
			},
			ARRAY_FILTER_USE_BOTH
		);

		uasort(
			$failures,
			function ( $a, $b ) {
				return ( $b['time'] ?? 0 ) <=> ( $a['time'] ?? 0 );
			}
		);

		return $failures;
	}

	/**
	 * Stores a failed download, keeping at most MAX_FAILURES (the most recent ones).
	 *
	 * @since v6.3.12
	 *
	 * @param string $url
	 * @param string $reason One of the FAILURE_* constants.
	 *
	 * @return void
	 */
	public static function add_failure( $url, $reason ) {
		self::update_failures(
			function ( $failures ) use ( $url, $reason ) {
				unset( $failures[ $url ] );

				return array_slice( [ $url => [ 'reason' => $reason, 'time' => time() ] ] + $failures, 0, self::MAX_FAILURES, true );
			}
		);
	}

	/**
	 * Removes a failed download, e.g. once it succeeds.
	 *
	 * @since v6.3.12
	 *
	 * @param string $url
	 *
	 * @return void
	 */
	public static function remove_failure( $url ) {
		// This runs after every successful download, so only lock when there's something to remove.
		if ( ! isset( self::get_failures()[ $url ] ) ) {
			return;
		}

		self::update_failures(
			function ( $failures ) use ( $url ) {
				unset( $failures[ $url ] );

				return $failures;
			}
		);
	}

	/**
	 * Removes all failed downloads, i.e. when they're dismissed.
	 *
	 * @since v6.3.12
	 *
	 * @return void
	 */
	public static function clear_failures() {
		self::update_failures(
			function () {
				return [];
			}
		);
	}

	/**
	 * Updates the failed downloads while holding a (MySQL) lock, based on the value currently stored in the database,
	 * so concurrent downloads can't overwrite each other's changes.
	 *
	 * @since v6.3.12
	 *
	 * @param callable $callback Receives the current failed downloads and returns the new ones.
	 *
	 * @return void
	 */
	private static function update_failures( $callback ) {
		global $wpdb;

		$lock_name = $wpdb->prefix . Settings::OMGF_DB_DOWNLOAD_FAILURES;
		// Databases which don't support locks (e.g. SQLite) return null, in which case we proceed without one.
		$locked = (bool) $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, %d)', $lock_name, 5 ) );

		try {
			// Read the current value from the database, instead of a (possibly outdated) cached copy.
			$stored = $wpdb->get_var(
				$wpdb->prepare( "SELECT option_value FROM $wpdb->options WHERE option_name = %s", Settings::OMGF_DB_DOWNLOAD_FAILURES )
			);

			wp_cache_delete( Settings::OMGF_DB_DOWNLOAD_FAILURES, 'options' );
			wp_cache_delete( 'notoptions', 'options' );

			$failures = $callback( self::normalize_failures( $stored === null ? [] : maybe_unserialize( $stored ) ) );

			if ( empty( $failures ) ) {
				delete_option( Settings::OMGF_DB_DOWNLOAD_FAILURES );
			} else {
				update_option( Settings::OMGF_DB_DOWNLOAD_FAILURES, $failures, false );
			}
		} finally {
			if ( $locked ) {
				$wpdb->query( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock_name ) );
			}
		}
	}

	/**
	 * @since v6.3.12
	 *
	 * @return string[]
	 */
	private static function get_failure_reasons() {
		return [ self::FAILURE_EMPTY, self::FAILURE_TOO_LARGE, self::FAILURE_INVALID ];
	}

	/**
	 * A human-readable description of why a download failed, and what the user can do about it.
	 *
	 * @since v6.3.12
	 *
	 * @param string $reason One of the FAILURE_* constants.
	 *
	 * @return string
	 */
	public static function get_failure_message( $reason ) {
		switch ( $reason ) {
			case self::FAILURE_TOO_LARGE:
				return sprintf(
				/* translators: %s: maximum file size, e.g. 25 MB */
					__( 'The file exceeds the maximum file size of %s, so it isn\'t used and a fallback font is shown instead.', 'host-webfonts-local' ),
					size_format( self::get_max_file_size() )
				);
			case self::FAILURE_INVALID:
				return __( 'The downloaded file isn\'t a font file (e.g. an error page), so a fallback font is shown until it\'s downloaded successfully. Click Save & Optimize to try again.', 'host-webfonts-local' );
			default:
				return __( 'The downloaded file was empty, so a fallback font is shown until it\'s downloaded successfully. Click Save & Optimize to try again.', 'host-webfonts-local' );
		}
	}

	/**
	 * Checks if $file starts with the signature (magic bytes) of a WOFF2, WOFF, TrueType, OpenType or EOT file.
	 *
	 * @since v6.3.12
	 *
	 * @param string $file
	 *
	 * @return bool
	 */
	public static function is_font_file( $file ) {
		$handle = @fopen( $file, 'rb' ); // phpcs:ignore

		if ( ! $handle ) {
			return false; // @codeCoverageIgnore
		}

		$header = (string) fread( $handle, 36 );

		fclose( $handle );

		// WOFF2, WOFF, OpenType (CFF), TrueType, TrueType (Apple), TrueType Collection.
		if ( in_array( substr( $header, 0, 4 ), [ 'wOF2', 'wOFF', 'OTTO', "\x00\x01\x00\x00", 'true', 'ttcf' ], true ) ) {
			return true;
		}

		// EOT: magic number 0x504C at offset 34.
		return strlen( $header ) >= 36 && substr( $header, 34, 2 ) === 'LP';
	}

	/**
	 * Removes the temporary file the response was streamed to.
	 *
	 * @since v6.3.11 The temporary file lives inside the uploads directory, which is publicly accessible, so
	 *                it should never be left behind, no matter why the download was aborted.
	 *
	 * @param string $temp_filename
	 *
	 * @return void
	 */
	private function delete_temp_file( $temp_filename ) {
		if ( file_exists( $temp_filename ) ) {
			unlink( $temp_filename );
		}
	}
}
