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

namespace OMGF\Frontend;

/**
 * @codeCoverageIgnore Because these can't be tested on their own.
 */
class Compatibility {
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
		add_action( 'plugins_loaded', [ $this, 'load_plugin_compatibility_fixes' ] );
		add_action( 'plugins_loaded', [ $this, 'load_theme_compatibility_fixes' ] );
	}

	/**
	 * Load plugin compatibility fixes.
	 *
	 * @return void
	 */
	public function load_plugin_compatibility_fixes() {
		if ( class_exists( 'Woo_Category_Slider' ) ) {
			new Compatibility\CategorySliderPro();
		}

		if ( function_exists( 'cp_load_convertpro' ) ) {
			new Compatibility\ConvertPro();
		}

		/**
		 * @TODO Will this still be needed after Elementor v3.30?
		 */
		if ( defined( 'ELEMENTOR_VERSION' ) ) {
			new Compatibility\Elementor();
		}

		/**
		 * The rest of this compatibility fix is located @see \OMGF\Frontend\Compatibility::init().
		 */
		if ( function_exists( 'groovy_menu_init_classes' ) ) {
			new Compatibility\GroovyMenu();
		}

		if ( class_exists( 'SP_Logo_Carousel' ) ) {
			new Compatibility\LogoCarouselPro();
		}

		/**
		 * The rest of this compatibility fix is located @see \OMGF\Frontend\Compatibility::init().
		 */
		if ( function_exists( 'smart_slider_3_plugins_loaded' ) ) {
			new Compatibility\SmartSlider3();
		}

		/**
		 * Some themes/plugins use the WPTT framework to load their webfonts locally, this adds global compatibility with those themes/plugins.
		 */
		if ( function_exists( 'wptt_get_webfont_url' ) ) {
			new Compatibility\WPTT();
		}
	}

	/**
	 * Load theme compatibility fixes.
	 *
	 * @return void
	 */
	public function load_theme_compatibility_fixes() {
		if ( $this->current_theme_is( 'Divi' ) ) {
			new Compatibility\Divi();
		}

		if ( $this->current_theme_is( 'fruitful' ) ) {
			new Compatibility\Fruitful();
		}

		if ( $this->current_theme_is( 'mesmerize' ) || $this->current_theme_is( 'highlight-pro' ) || $this->current_theme_is( 'mesmerize-pro' ) ) {
			new Compatibility\Mesmerize();
		}
	}

	/**
	 * Checks if $name is the current (parent) theme.
	 *
	 * @param $name
	 *
	 * @return bool
	 */
	private function current_theme_is( $name ) {
		$theme  = wp_get_theme();
		$parent = $theme->parent();

		return ( $theme instanceof \WP_Theme && $theme->get_template() === $name ) || ( $parent instanceof \WP_Theme && $parent->get( 'Name' ) === $name );
	}
}
