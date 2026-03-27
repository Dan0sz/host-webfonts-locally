<?php

namespace OMGF\Compatibility;

use OMGF\Helper as OMGF;

class Core {
	/**
	 * Build class.
	 */
	public function __construct() {
		add_action( 'switch_theme', [ $this, 'flush_cache' ] );
		add_action( 'upgrader_process_complete', [ $this, 'flush_third_party_cache' ] );
		add_action( 'permalink_structure_changed', [ $this, 'flush_third_party_cache' ] );
	}

	/**
	 * Flush cache.
	 *
	 * @return void
	 */
	public function flush_cache() {
		OMGF::flush_cache();
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
