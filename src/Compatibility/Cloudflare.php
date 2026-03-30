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

namespace OMGF\Compatibility;

/**
 * @codeCoverageIgnore
 */
class Cloudflare {
	const MU_PLUGIN_FILENAME = 'omgf-cloudflare-compatibility.php';

	const MU_PLUGIN_SOURCE = OMGF_PLUGIN_DIR . 'mu-plugin/' . self::MU_PLUGIN_FILENAME;

	/**
	 * Install the mu-plugin if it doesn't exist yet.
	 *
	 * @return void
	 */
	public static function maybe_install_mu_plugin() {
		if ( ! is_plugin_active( 'cloudflare/cloudflare.php' ) ) {
			return;
		}

		$destination = WPMU_PLUGIN_DIR . '/' . self::MU_PLUGIN_FILENAME;

		if ( file_exists( $destination ) ) {
			return;
		}

		wp_mkdir_p( WPMU_PLUGIN_DIR );
		copy( self::MU_PLUGIN_SOURCE, $destination );
	}

	/**
	 * Remove the mu-plugin when OMGF is deactivated.
	 *
	 * @return void
	 */
	public static function uninstall_mu_plugin() {
		$destination = WPMU_PLUGIN_DIR . '/' . self::MU_PLUGIN_FILENAME;

		if ( file_exists( $destination ) ) {
			unlink( $destination );
		}
	}
}
