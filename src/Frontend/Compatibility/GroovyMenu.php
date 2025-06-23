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

use OMGF\Frontend\Process;

/**
 * @codeCoverageIgnore Because it depends on a 3rd party plugin.
 */
class GroovyMenu {
	/**
	 * @var Process $process
	 */
	private $process;

	/**
	 * Build class.
	 */
	public function __construct() {
		$this->process = new Process( true );

		$this->init();
	}

	/**
	 * Action/filter hooks.
	 *
	 * @return void
	 */
	private function init() {
		/** Groovy Menu compatibility */
		add_filter( 'groovy_menu_final_output', [ $this->process, 'parse' ], 11 );
	}
}
