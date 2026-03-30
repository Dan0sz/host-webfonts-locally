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
	/**
	 * @var array $hooks [ 'hook' => 'callback' ] $hook provided by the 3rd party, $callback should be available in OMGF's Helper class.
	 */
	protected $hooks;

	/**
	 * Build class.
	 */
	public function __construct( $hooks = [] ) {
		if ( ! empty( $hooks ) ) {
			$this->hooks = $hooks;
		}

		if ( empty( $this->hooks ) ) {
			return;
		}

		/**
		 * @var string $callback In most cases, this will be:
		 *
		 * @see Helper::flush_third_party_cache() or,
		 * @see Helper::flush_cache()
		 */
		foreach ( $this->hooks as $hook => $callback ) {
			add_action( $hook, [ OMGF::class, $callback ] );
		}
	}
}
