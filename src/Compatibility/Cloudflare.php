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

use OMGF\Helper as OMGF;

/**
 * @codeCoverageIgnore
 */
class Cloudflare {
	const MU_PLUGIN_FILENAME = 'omgf-cloudflare-compatibility.php';

	const MU_PLUGIN_SOURCE = OMGF_PLUGIN_DIR . 'mu-plugin/' . self::MU_PLUGIN_FILENAME;

	/**
	 * Install the mu-plugin if it doesn't exist yet.
	 *
	 * @return bool
	 */
	public static function maybe_install_mu_plugin() {
		if ( ! is_plugin_active( 'cloudflare/cloudflare.php' ) ) {
			return true;
		}

		$destination = WPMU_PLUGIN_DIR . '/' . self::MU_PLUGIN_FILENAME;

		if ( file_exists( $destination ) ) {
			return true;
		}

		if ( ! wp_mkdir_p( WPMU_PLUGIN_DIR ) ) {
			OMGF::debug( sprintf( __( 'Could not create directory %s.', 'host-webfonts-local' ), WPMU_PLUGIN_DIR ) );

			return false;
		}

		$copied = copy( self::MU_PLUGIN_SOURCE, $destination );

		if ( ! $copied ) {
			OMGF::debug( sprintf( __( 'Could not copy %1$s to %2$s.', 'host-webfonts-local' ), self::MU_PLUGIN_SOURCE, $destination ) );

			if ( file_exists( $destination ) ) {
				unlink( $destination );
			}

			return false;
		}

		return true;
	}

	/**
	 * Remove the mu-plugin when OMGF is deactivated or uninstalled.
	 *
	 * @return bool
	 */
	public static function uninstall_mu_plugin() {
		$destination = WPMU_PLUGIN_DIR . '/' . self::MU_PLUGIN_FILENAME;

		if ( ! file_exists( $destination ) ) {
			return true;
		}

		$unlinked = unlink( $destination );

		if ( ! $unlinked ) {
			OMGF::debug( sprintf( __( 'Could not remove %s.', 'host-webfonts-local' ), $destination ) );

			return false;
		}

		return true;
	}
}
