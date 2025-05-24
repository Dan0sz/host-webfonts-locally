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
* @copyright: © 2025 Daan van den Bergh
* @url      : https://daan.dev
* * * * * * * * * * * * * * * * * * * */

namespace OMGF\DB\Migrate;

use OMGF\Admin\Settings;
use OMGF\Admin\Notice;
use OMGF\Helper as OMGF;

/**
 * @codeCoverageIgnore
 */
class V600 {
	/** @var $version string The version number this migration script was introduced with. */
	private $version = '6.0.0';

	/**
	 * Build class.
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * This migration script doesn't do much, besides showing a notice after updating.
	 *
	 * @return void
	 */
	private function init() {
		add_action( 'init', [ $this, 'set_upgrade_notice' ] );

		/**
		 * Update stored version number.
		 */
		OMGF::update_option( Settings::OMGF_CURRENT_DB_VERSION, $this->version );
	}

	/**
	 * Sets an upgrade notice if the OMGF Pro plugin is not active.
	 *
	 * @return void
	 */
	public function set_upgrade_notice() {
		Notice::set_notice(
			sprintf(
				__(
					'Thanks for upgrading to OMGF v6! 🎉 <a href="%s" target="_blank">Click here to learn about all the exciting, new features in this release!</a>',
					'host-webfonts-local'
				),
				'https://daan.dev/blog/wordpress/omgf-v6-omgf-pro-v4/'
			)
		);
	}
}
