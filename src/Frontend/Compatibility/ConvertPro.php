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
 * @codeCoverageIgnore Because it depends on a 3rd party plugin.
 */
class ConvertPro {
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
	 * Compatibility fix for Convert Pro by Brainstorm Force
	 *
	 * @since  v5.5.4 It kind of makes sense in this case, since Convert Pro allows
	 *               to create pop-ups and people tend to get creative. I just hope the ID isn't random.
	 *
	 * @filter omgf_frontend_process_convert_pro_compatibility Allows people to disable this feature, in case the different
	 *         stylesheets are actually needed.
	 */
	public function maybe_modify_id( $id ) {
		if ( apply_filters( 'omgf_frontend_process_convert_pro_compatibility', str_contains( $id, 'cp-google-fonts' ) ) ) {
			return 'cp-google-fonts';
		}

		return $id;
	}
}
