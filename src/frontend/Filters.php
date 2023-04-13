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
 * @copyright: © 2017 - 2023 Daan van den Bergh
 * @url      : https://daan.dev
 * * * * * * * * * * * * * * * * * * * */

namespace OMGF\Frontend;

class Filters {
	/**
	 * Filter frontend content.
	 */
	public function __construct() {
		add_filter( 'omgf_optimize_url', [ $this, 'decode_url' ] );
	}

	/**
	 * @since v5.3.3 Decode HTML entities to prevent URL decoding issues on some systems.
	 *
	 * @since v5.4.3 With encoded URLs the Google Fonts API is much more lenient when it comes to invalid requests,
	 *               but we need the URL to be decoded in order to properly parsed (parse_str() and parse_url()), etc.
	 *               So, as of now, we're trimming invalid characters from the end of the URL. The list will expand
	 *               as I run into to them. I'm not going to make any assumptions on what theme/plugin developers
	 *               might be doing wrong.
	 *
	 * @filter omgf_optimize_url
	 *
	 * @param mixed $url
	 *
	 * @return string
	 */
	public function decode_url( $url ) {
		return rtrim( html_entity_decode( $url ), ',' );
	}
}
