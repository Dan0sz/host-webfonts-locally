<?php

namespace OMGF\Compatibility;

use OMGF\Helper as OMGF;

class Oxygen {
	/**
	 * Build class.
	 */
	public function __construct() {
		add_action( 'oxygen_vsb_post_compiled', [ $this, 'flush_third_party_cache' ] );
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
