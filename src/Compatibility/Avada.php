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
* @copyright: © 2026 Daan van den Bergh
* @url      : https://daan.dev
* * * * * * * * * * * * * * * * * * * */

namespace OMGF\Compatibility;

use OMGF\Helper as OMGF;

/**
 * @codeCoverageIgnore
 */
class Avada extends CompatibilityHookRegistrar {
	/** @var array $hooks */
	protected $hooks = [ 'fusion_cache_reset_after' ];

	/**
	 * Build class.
	 */
	public function __construct() {
		parent::__construct();

		add_action( 'wp_after_insert_post', [ $this, 'maybe_flush_third_party_cache' ], 10, 3 );
	}

	/**
	 * Only flush the cache if this is a published post.
	 *
	 * @param $post_id
	 * @param $post
	 * @param $update
	 *
	 * @return void
	 */
	public function maybe_flush_third_party_cache( $post_id, $post, $update ) {
		if ( $post->post_status !== 'publish' || wp_is_post_revision( $post_id ) ) {
			return;
		}

		OMGF::flush_third_party_cache();
	}
}
