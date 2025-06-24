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

namespace OMGF\Frontend\Compatibility;

/**
 * @codeCoverageIgnore Because it depends on a 3rd party.
 */
class CategorySliderPro {
	/**
	 * Build class.
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Action/filter hooks.
	 *
	 * @return void
	 */
	private function init() {
		add_filter( 'omgf_frontend_process_fonts_set', [ $this, 'maybe_modify_id' ] );
	}

	/**
	 * Compatibility fix for Category Slider Pro for WooCommerce by ShapedPlugin
	 *
	 * @since v5.3.7 This plugin finds it necessary to provide each Google Fonts stylesheet with a
	 *               unique identifier on each page load, to make sure it's never cached. The worst idea ever.
	 *               On top of that, it throws OMGF off the rails entirely, eventually crashing the site.
	 */
	public function maybe_modify_id( $id ) {
		if ( str_contains( $id, 'sp-wpcp-google-fonts' ) ) {
			return 'sp-wpcp-google-fonts';
		}

		return $id;
	}
}
