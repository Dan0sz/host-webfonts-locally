<?php

namespace OMGF\Compatibility;

use OMGF\Helper as OMGF;

class Divi {
	/**
	 * Build class.
	 */
	public function __construct() {
		add_action( 'et_save_post', [ $this, 'flush_third_party_cache' ] );
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
