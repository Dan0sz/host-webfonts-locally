<?php

namespace OMGF\Compatibility;

use OMGF\Helper as OMGF;

class Bricks {
	/**
	 * Build class.
	 */
	public function __construct() {
		add_action( 'bricks/builder/save_post', [ $this, 'flush_third_party_cache' ] );
	}

	/**
	 * @return void
	 */
	public function flush_third_party_cache() {
		OMGF::flush_third_party_cache();
	}
}
