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
* @copyright: © 2023 Daan van den Bergh
* @url      : https://daan.dev
* * * * * * * * * * * * * * * * * * * */

namespace OMGF\DB\Migrate;

use OMGF\Helper as OMGF;
use OMGF\Admin\Settings;

defined( 'ABSPATH' ) || exit;

class V534 {

	/** @var $version string The version number this migration script was introduced with. */
	private $version = '5.3.4';

	/**
	 * Buid
	 *
	 * @return void
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Initialize
	 *
	 * @return void
	 */
	private function init() {
		$optimized_fonts = OMGF::optimized_fonts() ?? [];
		$upgrade_req     = false;

		foreach ( $optimized_fonts as $stylesheet => $fonts ) {
			foreach ( $fonts as $font ) {
				$variants = $font->variants ?? [];

				foreach ( $variants as $key => $variant ) {
					/**
					 * Optimized Fonts needs upgrading if $variants is still an indexed array.
					 *
					 * @since v5.3.0 $variants should be an associative array.
					 */
					if ( is_numeric( $key ) ) {
						$upgrade_req = true;

						break;
					}
				}

				if ( $upgrade_req ) {
					break;
				}
			}

			if ( $upgrade_req ) {
				break;
			}
		}

		/**
		 * Mark cache as stale if upgrade is required.
		 */
		if ( $upgrade_req ) {
			OMGF::update_option( Settings::OMGF_CACHE_IS_STALE, $upgrade_req );
		}

		/**
		 * Update stored version number.
		 */
		OMGF::update_option( Settings::OMGF_CURRENT_DB_VERSION, $this->version );
	}
}
