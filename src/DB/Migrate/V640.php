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
 * @since v6.4.0
 */
class V640 {
	/** @var $version string The version number this migration script was introduced with. */
	private $version = '6.4.0';

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

			$success = ! $this->has_autoloaded_options( $options );
		} else {
			$success = $this->set_options_autoload_legacy( $options ); // @codeCoverageIgnore
		}

		/**
		 * If autoload couldn't be disabled, we bail without storing the version number, so we try again on the next load.
		 */
		if ( ! $success ) {
			return;
		}

		/**
		 * Update stored version number.
		 */
		OMGF::update_option( Settings::OMGF_CURRENT_DB_VERSION, $this->version );
	}

	/**
	 * wp_set_options_autoload() returns false for both unchanged and failed options, so we check the result ourselves.
	 *
	 * @param array $options
	 *
	 * @return bool
	 */
	public function has_autoloaded_options( $options ) {
		global $wpdb;

		if ( empty( $options ) ) {
			return false;
		}

		$values       = function_exists( 'wp_autoload_values_to_autoload' ) ? wp_autoload_values_to_autoload() : [ 'yes' ];
		$placeholders = implode( ',', array_fill( 0, count( $options ), '%s' ) );
		$value_holder = implode( ',', array_fill( 0, count( $values ), '%s' ) );

		// phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
		$count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $wpdb->options WHERE option_name IN ($placeholders) AND autoload IN ($value_holder)", array_merge( $options, $values ) ) );

		return $count === null || (int) $count > 0;
	}

	/**
	 * Fallback for WordPress < 6.4, which doesn't have wp_set_options_autoload().
	 *
	 * @param array $options
	 *
	 * @return bool False if the query failed.
	 */
	public function set_options_autoload_legacy( $options ) {
		global $wpdb;

		if ( empty( $options ) ) {
			return true;
		}

		$placeholders = implode( ',', array_fill( 0, count( $options ), '%s' ) );

		// phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
		$result = $wpdb->query( $wpdb->prepare( "UPDATE $wpdb->options SET autoload = 'no' WHERE option_name IN ($placeholders)", $options ) );

		wp_cache_delete( 'alloptions', 'options' );

		foreach ( $options as $option ) {
			wp_cache_delete( $option, 'options' );
		}

		return $result !== false;
	}
}
