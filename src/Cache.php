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

use OMGF\Admin\Settings;
use OMGF\Helper as OMGF;

class Cache {
	/**
	 * Flush the entire OMGF cache directory and all related database entries.
	 *
	 * @return void
	 * @throws \ReflectionException
	 */
	public function flush( $initiator ) {
		$entries    = array_filter( (array) glob( OMGF_UPLOAD_DIR . '/*' ) );
		$flush_rows = OMGF::get_db_rows_by( [ 'OMGF_FLAG_', 'OMGF_DB_', 'OMGF_OPTIMIZE_SETTING_' ], [ Settings::OMGF_OPTIMIZE_SETTING_TEST_MODE, Settings::OMGF_OPTIMIZE_SETTING_DISPLAY_OPTION ] );

		$instructions = apply_filters(
			'omgf_clean_up_instructions',
			[
				'init'    => $initiator,
				'exclude' => [],
				'queue'   => $flush_rows,
			]
		);

		foreach ( $entries as $entry ) {
			if ( in_array( $entry, $instructions['exclude'] ) ) {
				continue; // @codeCoverageIgnore
			}

			OMGF::delete( $entry );
		}

		foreach ( $instructions['queue'] as $option ) {
			OMGF::delete_option( $option );
		}

		do_action( 'omgf_after_clean_up' );
	}

	/**
	 * Flush only 3rd party stylesheet cache directories — i.e., all directories
	 * in OMGF_UPLOAD_DIR that are NOT present in OMGF::cache_keys().
	 *
	 * @return void
	 */
	public function flush_third_party() {
		$cache_keys = array_values( OMGF::cache_keys() );
		$entries    = array_filter( (array) glob( OMGF_UPLOAD_DIR . '/*', GLOB_ONLYDIR ) );

		foreach ( $entries as $entry ) {
			$folder_name = basename( $entry );

			if ( in_array( $folder_name, $cache_keys ) ) {
				continue;
			}

			OMGF::delete( $entry );
		}
	}
}
