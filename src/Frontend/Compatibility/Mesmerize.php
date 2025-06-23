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
class Mesmerize {
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
		add_filter( 'omgf_frontend_process_fonts_set_href', [ $this, 'maybe_replace_href' ], 10, 2 );
		add_filter( 'style_loader_tag', [ $this, 'maybe_remove_data_attribute' ], 12, 1 );
	}

	/**
	 * Mesmerize Theme compatibility
	 *
	 * @filter omgf_frontend_process_fonts_set_href
	 * @see    \OMGF\Frontend\Process::build_fonts_set()
	 */
	public function maybe_replace_href( $href, $link ) {
		if ( $href === '#' ) {
			preg_match( '/data-href=[\'"](?P<href>.*?)[\'"]/', $link, $matches ); // @codeCoverageIgnore

			if ( isset( $matches[ 'href' ] ) ) {
				$href = $matches[ 'href' ];
			}
		}

		return $href;
	}

	/**
	 * Because all great themes come packed with extra Cumulative Layout Shifting.
	 *
	 * @filter style_loader_tag
	 *
	 * @param string $tag
	 *
	 * @return string
	 */
	public function maybe_remove_data_attribute( $tag ) {
		if ( str_contains( $tag, 'fonts.googleapis.com' ) ) {
			return str_replace( 'href="" data-href', 'href', $tag );
		}

		return $tag;
	}
}
