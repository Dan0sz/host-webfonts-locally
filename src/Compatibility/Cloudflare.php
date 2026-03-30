<?php

namespace OMGF\Compatibility;

class Cloudflare {
	const MU_PLUGIN_FILENAME = 'omgf-cloudflare-compatibility.php';

	const MU_PLUGIN_SOURCE = OMGF_PLUGIN_DIR . 'mu-plugin/' . self::MU_PLUGIN_FILENAME;

	public function __construct() {
		add_action( 'admin_init', [ $this, 'maybe_install_mu_plugin' ] );
	}

	/**
	 * Remove the mu-plugin when OMGF is deactivated.
	 *
	 * @return void
	 */
	public static function uninstall_mu_plugin( $plugin_file = null ) {
		$destination = WPMU_PLUGIN_DIR . '/' . self::MU_PLUGIN_FILENAME;

		if ( file_exists( $destination ) ) {
			unlink( $destination );
		}
	}

	/**
	 * Install the mu-plugin if it doesn't exist yet.
	 *
	 * @return void
	 */
	public function maybe_install_mu_plugin() {
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
}
