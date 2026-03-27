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
	/** @var string $hook */
	protected $hook;

	/**
	 * Build class.
	 */
	public function __construct( string $hook = '' ) {
		if ( $hook ) {
			$this->hook = $hook;
		}

		add_action( $this->hook, [ $this, 'flush_third_party_cache' ] );
	}

	/**
	 * Flush 3rd party cache.
	 *
	 * @return void
	 */
	public function flush_third_party_cache() {
		OMGF::flush_third_party_cache();
	}
}
