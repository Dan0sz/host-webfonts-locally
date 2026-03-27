<?php

namespace OMGF\Compatibility;

use OMGF\Helper as OMGF;

class BeaverBuilder {
	/**
	 * Build class.
	 */
	public function __construct() {
		add_action( 'fl_builder_after_save_layout', [ $this, 'flush_third_party_cache' ] );
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
