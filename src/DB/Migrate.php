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

namespace OMGF\DB;

use OMGF\Admin\Settings;
use OMGF\Helper as OMGF;

defined( 'ABSPATH' ) || exit;

class Migrate {

	/** @var string */
	private $current_version = '';

	/**
	 * DB Migration constructor.
	 */
	public function __construct() {
		/**
		 * Can be used to block migration scripts that shouldn't be run on a fresh install.
		 */
		$this->current_version = OMGF::get_option( Settings::OMGF_CURRENT_DB_VERSION, '1.0.0' );

		if ( $this->should_run_migration( '5.3.3' ) ) {
			new Migrate\V533();
		}

		if ( $this->should_run_migration( '5.3.4' ) ) {
			new Migrate\V534();
		}

		if ( $this->should_run_migration( '5.6.0' ) ) {
			new Migrate\V560();
		}
	}

	/**
	 * Checks whether migration script has been run.
	 *
	 * @param mixed $version
	 * @return bool
	 */
	private function should_run_migration( $version ) {
		return version_compare( $this->current_version, $version ) < 0;
	}
}
