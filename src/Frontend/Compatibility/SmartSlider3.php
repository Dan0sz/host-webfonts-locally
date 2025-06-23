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
class SmartSlider3 {
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
		/** Smart Slider 3 compatibility */
		add_filter( 'wordpress_prepare_output', [ $this->process, 'parse' ], 11 );
	}
}
