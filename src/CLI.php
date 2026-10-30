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

use OMGF\Admin\ImportExport;

/**
 * Export and import OMGF's settings.
 *
 * @since v6.4.0
 *
 * @codeCoverageIgnore Thin wrapper around ImportExport, which requires WP-CLI to run.
 */
class CLI {
	/**
	 * Export OMGF's settings (and those of its add-ons) as JSON. License keys are never included.
	 *
	 * ## OPTIONS
	 *
	 * [<file>]
	 * : Write the export to this file, instead of STDOUT.
	 *
	 * [--include-fonts]
	 * : Include the Optimize Local Fonts data.
	 *
	 * ## EXAMPLES
	 *
	 *     wp omgf export omgf-settings.json --include-fonts
	 *
	 * @param array $args
	 * @param array $assoc_args
	 *
	 * @return void
	 */
	public function export( $args, $assoc_args ) {
		$json = ImportExport::export_json( \WP_CLI\Utils\get_flag_value( $assoc_args, 'include-fonts', false ) );

		if ( empty( $args[0] ) ) {
			\WP_CLI::line( $json );

			return;
		}

		if ( file_put_contents( $args[0], $json ) === false ) { // phpcs:ignore
			\WP_CLI::error( __( 'The export file couldn\'t be written.', 'host-webfonts-local' ) );
		}

		/* translators: %s: file path. */
		\WP_CLI::success( sprintf( __( 'Settings exported to %s.', 'host-webfonts-local' ), $args[0] ) );
	}

	/**
	 * Import OMGF's settings from a JSON file created by `wp omgf export` or the Help tab.
	 *
	 * ## OPTIONS
	 *
	 * <file>
	 * : The export file to import.
	 *
	 * ## EXAMPLES
	 *
	 *     wp omgf import omgf-settings.json
	 *
	 * @param array $args
	 *
	 * @return void
	 */
	public function import( $args ) {
		$file = $args[0];

		if ( ! is_file( $file ) || ! is_readable( $file ) ) {
			/* translators: %s: file path. */
			\WP_CLI::error( sprintf( __( 'File %s doesn\'t exist or isn\'t readable.', 'host-webfonts-local' ), $file ) );
		}

		$skipped = [];
		$options = ImportExport::parse( (string) file_get_contents( $file, false, null, 0, ImportExport::MAX_FILE_SIZE + 1 ), $skipped ); // phpcs:ignore

		if ( is_wp_error( $options ) ) {
			\WP_CLI::error( wp_strip_all_tags( $options->get_error_message() ) );
		}

		ImportExport::import( $options );

		$warning = ImportExport::get_skipped_message( $skipped );

		if ( $warning ) {
			\WP_CLI::warning( wp_strip_all_tags( $warning ) );
		}

		\WP_CLI::success( __( 'Settings imported. Run Save & Optimize to apply them.', 'host-webfonts-local' ) );
	}
}
