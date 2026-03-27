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
 * @copyright: © 2017 - 2025 Daan van den Bergh
 * @url      : https://daan.dev
 * * * * * * * * * * * * * * * * * * * */

namespace OMGF\Compatibility;

use OMGF\Helper as OMGF;

/**
 * @codeCoverageIgnore because it depends on 3rd party plugins.
 */
class CompatibilityHookRegistrar {
	/** @var array $hooks */
	protected $hooks;

	/**
	 * Build class.
	 */
	public function __construct( string $hooks = '' ) {
		if ( $hooks ) {
			$this->hooks = $hooks;
		}

		if ( empty( $this->hooks ) ) {
			return;
		}

		foreach ( $this->hooks as $hook ) {
			add_action( $hook, [ $this, 'flush_third_party_cache' ] );
		}
	}

	/**
	 * Flush 3rd party cache.
	 *
	 * @return void
	 */
	public function flush_third_party_cache() {
		static $flushed = false;

		if ( $flushed ) {
			return;
		}

		$flushed = true;

		OMGF::flush_third_party_cache();
	}
}
