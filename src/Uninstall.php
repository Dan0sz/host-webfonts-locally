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

use OMGF\Admin\Settings;

defined( 'ABSPATH' ) || exit;

class Uninstall {
	/** @var string $cache_dir */
	private $cache_dir;

	/**
	 *
	 */
	public function __construct() {
		$this->cache_dir = OMGF_UPLOAD_DIR;

		$this->remove_db_entries();
		$this->delete_files();
		$this->delete_dir();
	}

	/**
	 * Remove all settings stored in the wp_options table.
	 */
	private function remove_db_entries() {
		$db_entries = apply_filters(
			'omgf_uninstall_db_entries',
			[
				'omgf_settings',
				Settings::OMGF_OPTIMIZE_SETTING_OPTIMIZED_FONTS,
				Settings::OMGF_OPTIMIZE_SETTING_PRELOAD_FONTS,
				Settings::OMGF_OPTIMIZE_SETTING_UNLOAD_FONTS,
				Settings::OMGF_AVAILABLE_USED_SUBSETS,
				Settings::OMGF_NEWS_REEL,
				Settings::OMGF_OPTIMIZE_HAS_RUN,
				Settings::OMGF_CACHE_IS_STALE,
				Settings::OMGF_CURRENT_DB_VERSION,
				Settings::OMGF_CACHE_TIMESTAMP,
				Settings::OMGF_FOUND_IFRAMES,
				Settings::OMGF_HIDDEN_NOTICES,
			]
		);

		foreach ( $db_entries as $entry ) {
			delete_option( $entry );
		}
	}

	/**
	 * Delete all files stored in the cache directory.
	 *
	 * @return array
	 */
	private function delete_files() {
		array_map( 'unlink', glob( $this->cache_dir . '/*.*' ) );
	}

	/**
	 * Delete the cache directory.
	 *
	 * @return bool
	 */
	private function delete_dir() {
		rmdir( $this->cache_dir );
	}
}
