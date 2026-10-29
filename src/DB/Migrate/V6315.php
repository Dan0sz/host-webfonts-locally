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

namespace OMGF\DB\Migrate;

use OMGF\Admin\Settings;
use OMGF\Helper as OMGF;

/**
 * @since v6.3.15
 */
class V6315 {
	/** @var $version string The version number this migration script was introduced with. */
	private $version = '6.3.15';

	/**
	 * Build class.
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Disable autoload for options which aren't needed on (most) frontend requests.
	 *
	 * @return void
	 */
	private function init() {
		$options = OMGF::non_autoloaded_options();

		if ( function_exists( 'wp_set_options_autoload' ) ) {
			wp_set_options_autoload( $options, false );
		} else {
			$this->set_options_autoload_legacy( $options ); // @codeCoverageIgnore
		}

		/**
		 * Update stored version number.
		 */
		OMGF::update_option( Settings::OMGF_CURRENT_DB_VERSION, $this->version );
	}

	/**
	 * Fallback for WordPress < 6.4, which doesn't have wp_set_options_autoload().
	 *
	 * @param array $options
	 *
	 * @return void
	 */
	public function set_options_autoload_legacy( $options ) {
		global $wpdb;

		if ( empty( $options ) ) {
			return;
		}

		$placeholders = implode( ',', array_fill( 0, count( $options ), '%s' ) );

		// phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
		$wpdb->query( $wpdb->prepare( "UPDATE $wpdb->options SET autoload = 'no' WHERE option_name IN ($placeholders)", $options ) );

		wp_cache_delete( 'alloptions', 'options' );

		foreach ( $options as $option ) {
			wp_cache_delete( $option, 'options' );
		}
	}
}
