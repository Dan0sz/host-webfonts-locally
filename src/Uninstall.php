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
		delete_option( 'omgf_settings' );
		delete_option( Settings::OMGF_OPTIMIZE_SETTING_OPTIMIZED_FONTS );
		delete_option( Settings::OMGF_OPTIMIZE_SETTING_PRELOAD_FONTS );
		delete_option( Settings::OMGF_OPTIMIZE_SETTING_UNLOAD_FONTS );
		delete_option( Settings::OMGF_OPTIMIZE_SETTING_CACHE_KEYS );
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
