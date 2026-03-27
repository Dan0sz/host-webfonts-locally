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

	/**
	 * Load Global 3rd party compatibility fixes.
	 *
	 * @return void
	 */
	public function load_global_plugin_compatibility_fixes() {
		new Compatibility\Core();

		if ( defined( 'ELEMENTOR_VERSION' ) ) {
			new Compatibility\Elementor();
		}

		if ( defined( 'FL_BUILDER_VERSION' ) ) {
			new Compatibility\BeaverBuilder();
		}

		if ( defined( 'BRICKS_VERSION' ) ) {
			new Compatibility\Bricks();
		}

		if ( defined( 'ET_BUILDER_VERSION' ) ) {
			new Compatibility\Divi();
		}

		if ( defined( 'SHOW_CT_BUILDER' ) ) {
			new Compatibility\Oxygen();
		}

		if ( defined( 'WPB_VC_VERSION' ) ) {
			new Compatibility\VisualComposer();
		}
	}
}
