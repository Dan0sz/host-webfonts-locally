<?php

namespace OMGF;

class Compatibility {
	/**
	 * Build class.
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Action & Filter hooks.
	 * @return void
	 */
	private function init() {
		add_filter( 'omgf_optimize_user_agent', [ $this, 'avada_compatibility' ] );
	}

	/**
	 * Compatibility with Avada, to make sure we both use the same user agents.
	 * @return string
	 */
	public function avada_compatibility( $user_agent ) {
		$theme = wp_get_theme();

		if ( $theme->name !== 'Avada' && $theme->parent() !== 'Avada' ) {
			return $user_agent;
		}

		return 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/88.0.4324.190 Safari/537.36';
	}
}