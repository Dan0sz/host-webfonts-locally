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

namespace OMGF;

use OMGF\Admin\Settings;
use OMGF\Helper as OMGF;

class Filters {
	/**
	 * Generic filters.
	 */
	public function __construct() {
		add_filter( 'content_url', [ $this, 'force_ssl' ], 1000 );
		add_filter( 'home_url', [ $this, 'force_ssl' ], 1000, 2 );
		add_filter( 'omgf_optimize_user_agent', [ $this, 'maybe_do_legacy_mode' ] );
		add_filter( 'omgf_do_not_load_frontend_css', [ $this, 'maybe_load_frontend_assets' ] );
	}

	/**
	 * content_url uses is_ssl() to detect whether SSL is used. This fails for servers behind
	 * load balancers and/or reverse proxies. So, we double-check with this filter.
	 *
	 * @since v4.4.4
	 *
	 * @param mixed $url
	 *
	 * @return mixed
	 * @todo  Is this still needed, since we're using protocol relative URLs now?
	 */
	public function force_ssl( $url ) {
		/**
		 * Only rewrite URLs requested by this plugin. We don't want to interfere with other plugins.
		 */
		if ( ! str_contains( $url, OMGF_UPLOAD_URL ) ) {
			return $url;
		}

		/**
		 * If the user entered https:// in the Home URL option, it's safe to assume that SSL is used.
		 */
		if ( ! is_ssl() && str_contains( get_home_url(), 'https://' ) ) {
			$url = str_replace( 'http://', 'https://', $url ); // @codeCoverageIgnore
		}

		return $url;
	}

	/**
	 * @param $user_agent
	 *
	 * @return mixed|string[]
	 */
	public function maybe_do_legacy_mode( $user_agent ) {
		if ( ! empty( OMGF::get_option( Settings::OMGF_ADV_SETTING_LEGACY_MODE ) ) ) {
			return Optimize::USER_AGENT_COMPATIBILITY['woff2'];
		}

		return $user_agent;
	}

	/**
	 * Don't load frontend assets if the Disable Admin Bar Menu option is enabled.
	 *
	 * @filter omgf_do_not_load_frontend_css
	 * @see    Frontend\Actions::maybe_add_admin_bar_js()
	 *
	 * @since  v6.0.1
	 *
	 * @return bool
	 */
	public function maybe_load_frontend_assets( $value ) {
		if ( OMGF::get_option( Settings::OMGF_ADV_SETTING_DISABLE_ADMIN_BAR_MENU ) ) {
			return true; // Don't load.
		}

		return $value;
	}
}
