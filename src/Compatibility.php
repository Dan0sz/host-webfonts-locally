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

namespace OMGF;

/**
 * @codeCoverageIgnore Because it depends on 3rd party plugins.
 */
class Compatibility {
	/**
	 * Build class.
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Action/filter hooks for global compatibility fixes, required in front-/back-end.
	 *
	 * @return void
	 */
	private function init() {
		add_action( 'plugins_loaded', [ $this, 'load_global_plugin_compatibility_fixes' ] );
	}

	public function load_global_plugin_compatibility_fixes() {
		if ( defined( 'WPB_VC_VERSION' ) ) {
			new Compatibility\VisualComposer();
		}
	}
}
