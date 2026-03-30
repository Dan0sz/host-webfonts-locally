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
use OMGF\Compatibility\Cloudflare;
use OMGF\Helper as OMGF;

/**
 * @codeCoverageIgnore
 */
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
		$this->uninstall_mu_plugin();
	}

	/**
	 * Remove all settings stored in the wp_options table.
	 * @throws \ReflectionException
	 */
	private function remove_db_entries() {
		$db_rows    = OMGF::get_db_rows_by( [ 'OMGF_FLAG_', 'OMGF_DB_', 'OMGF_OPTIMIZE_SETTING_', 'OMGF_CURRENT_DB_VERSION', 'OMGF_HIDDEN_NOTICES', 'OMGF_NEWS_REEL' ] );
		$db_entries = apply_filters( 'omgf_uninstall_db_entries', array_merge( $db_rows, [ 'omgf_settings' ] ) );

		foreach ( $db_entries as $entry ) {
			delete_option( $entry );
		}
	}

	/**
	 * Delete all files stored in the cache directory.
	 *
	 * @return void
	 */
	private function delete_files() {
		array_map( 'unlink', glob( $this->cache_dir . '/*.*' ) );
	}

	/**
	 * Delete the cache directory.
	 *
	 * @return void
	 */
	private function delete_dir() {
		if ( is_dir( $this->cache_dir ) ) {
			rmdir( $this->cache_dir );
		}
	}

	/**
	 * Remove the Cloudflare MU plugin if it exists.
	 *
	 * @return void
	 */
	private function uninstall_mu_plugin() {
		Cloudflare::uninstall_mu_plugin();
	}
}
