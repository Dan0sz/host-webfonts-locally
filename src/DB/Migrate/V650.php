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

namespace OMGF\DB\Migrate;

use OMGF\Admin\Settings;
use OMGF\Helper as OMGF;

/**
 * @since v6.5.0
 */
class V650 {
	/** @var $version string The version number this migration script was introduced with. */
	private $version = '6.5.0';

	/**
	 * Build class.
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * OMGF doesn't keep track of when stylesheets were last seen anymore, because pages served from a page cache don't
	 * report them. The option used for it (Settings::OMGF_DB_STYLESHEETS_LAST_SEEN until v6.5.0) is removed.
	 *
	 * @return void
	 */
	private function init() {
		delete_option( 'omgf_stylesheets_last_seen' );

		OMGF::update_option( Settings::OMGF_CURRENT_DB_VERSION, $this->version );
	}
}
