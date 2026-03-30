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
	 * @see OMGF::flush_third_party_cache()
	 */
	const HOOK_FLUSH_THIRD_PARTY = 'flush_third_party_cache';

	/**
	 * @see OMGF::flush_cache()
	 */
	const HOOK_FLUSH_CACHE = 'flush_cache';

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

		foreach ( $this->hooks as $hook => $callback ) {
			add_action( $hook, [ OMGF::class, $callback ] );
		}
	}
}
