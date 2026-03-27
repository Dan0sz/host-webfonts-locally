<?php

namespace OMGF\Compatibility;

class CachingPlugins {
	/**
	 * Build class.
	 */
	public function __construct() {
		add_action( 'omgf_return_buffer', [ $this, 'maybe_flush_cache' ] );
	}

	/**
	 * Flush the entire cache of popular caching plugins after a successful OMGF optimize run.
	 *
	 * @return void
	 */
	public function maybe_flush_cache() {
		if ( ! isset( $_GET['omgf_optimize'] ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// FlyingPress
		if ( has_action( 'flying_press_purge_all' ) ) {
			do_action( 'flying_press_purge_all' );
		}

		// Kinsta
		if ( defined( 'KINSTAMU_VERSION' ) ) {
			wp_remote_get(
				home_url() . '/kinsta-clear-cache-all',
				[
					'timeout'  => 5,
					'blocking' => false,
				]
			);
		}

		// LiteSpeed Cache
		if ( class_exists( '\LiteSpeed\Purge' ) ) {
			do_action( 'litespeed_purge_all' );
		}

		// SiteGround Optimizer
		if ( function_exists( 'sg_cachepress_purge_cache' ) ) {
			sg_cachepress_purge_cache();
		}

		// W3 Total Cache
		if ( function_exists( 'w3tc_flush_all' ) ) {
			w3tc_flush_all();
		}

		// WP Fastest Cache
		if ( function_exists( 'wpfc_clear_all_cache' ) ) {
			wpfc_clear_all_cache();
		}

		// WP-Optimize
		if ( function_exists( 'wpo_cache_flush' ) ) {
			wpo_cache_flush();
		}

		// WP Rocket
		if ( function_exists( 'rocket_clean_domain' ) ) {
			rocket_clean_domain();
		}

		// WP Super Cache
		if ( function_exists( 'wp_cache_clear_cache' ) ) {
			wp_cache_clear_cache();
		}
	}
}
