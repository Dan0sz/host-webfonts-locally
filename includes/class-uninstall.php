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
 * @copyright: © 2022 Daan van den Bergh
 * @url      : https://daan.dev
 * * * * * * * * * * * * * * * * * * * */

defined( 'ABSPATH' ) || exit;

class OMGF_Uninstall {
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
