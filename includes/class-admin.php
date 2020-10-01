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
 * @copyright: (c) 2020 Daan van den Bergh
 * @url      : https://daan.dev
 * * * * * * * * * * * * * * * * * * * */

defined( 'ABSPATH' ) || exit;

class OMGF_Admin
{
	const OMGF_ADMIN_JS_HANDLE  = 'omgf-admin-js';
	const OMGF_ADMIN_CSS_HANDLE = 'omgf-admin-css';
	
	/** @var array $show_notice */
	private $show_notice = [];
	
	/** @var string $plugin_text_domain */
	private $plugin_text_domain = 'host-webfonts-local';
	
	/**
	 * OMGF_Admin constructor.
	 */
	public function __construct () {
		/**
		 * Filterable list of options that require the cache to be emptied.
		 */
		$this->show_notice = apply_filters(
			'omgf_admin_options_show_notice',
			[
				OMGF_Admin_Settings::OMGF_BASIC_SETTING_CACHE_PATH,
				OMGF_Admin_Settings::OMGF_ADV_SETTING_CACHE_URI,
				OMGF_Admin_Settings::OMGF_ADV_SETTING_RELATIVE_URL,
				OMGF_Admin_Settings::OMGF_ADV_SETTING_CDN_URL
			]
		);
		
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ] );
		add_action( 'admin_notices', [ $this, 'print_notices' ] );
		
		$this->do_basic_settings();
		$this->do_advanced_settings();
		
		add_action( 'admin_init', [ $this, 'maybe_show_optimize_notice' ] );
		add_filter( 'pre_update_option', [ $this, 'settings_changed' ], 10, 3 );
		add_filter( 'update_option_' . OMGF_Admin_Settings::OMGF_OPTIMIZATION_COMPLETE, [ $this, 'optimization_finished' ], 10, 2 );
	}
	
	/**
	 * Enqueues the necessary JS and CSS and passes options as a JS object.
	 *
	 * @param $hook
	 */
	public function enqueue_admin_scripts ( $hook ) {
		wp_enqueue_script( self::OMGF_ADMIN_JS_HANDLE, plugin_dir_url( OMGF_PLUGIN_FILE ) . 'assets/js/omgf-admin.js', [ 'jquery' ], OMGF_STATIC_VERSION, true );
		wp_enqueue_style( self::OMGF_ADMIN_CSS_HANDLE, plugin_dir_url( OMGF_PLUGIN_FILE ) . 'assets/css/omgf-admin.css', [], OMGF_STATIC_VERSION );
	}
	
	/**
	 * @param $name
	 *
	 * @return mixed
	 */
	protected function get_template ( $name ) {
		return include OMGF_PLUGIN_DIR . 'templates/admin/block-' . $name . '.phtml';
	}
	
	/**
	 * Add notice to admin screen.
	 */
	public function print_notices () {
		OMGF_Admin_Notice::print_notices();
	}
	
	/**
	 * @return OMGF_Admin_Settings_Basic
	 */
	private function do_basic_settings () {
		return new OMGF_Admin_Settings_Basic();
	}
	
	/**
	 * @return OMGF_Admin_Settings_Advanced
	 */
	private function do_advanced_settings () {
		return new OMGF_Admin_Settings_Advanced();
	}
	
	/**
	 * @param $new_value
	 * @param $old_settings
	 *
	 * @return mixed
	 */
	public function settings_changed ( $value, $option_name, $old_value ) {
		if ( ! in_array( $option_name, $this->show_notice ) ) {
			return $value;
		}
		
		if ( $value != $old_value ) {
			OMGF_Admin_Notice::set_notice(
				__( 'Settings changed. <a href="#" class="omgf-empty">Click here</a> to empty OMGF\'s cache.', $this->plugin_text_domain ),
				'omgf-settings-changed',
				false
			);
		}
		
		return $value;
	}
	
	/**
	 * @param $old_value
	 * @param $new_value
	 */
	public function optimization_finished ( $old_value, $new_value ) {
		if ( $old_value == false && $new_value == true ) {
			OMGF_Admin_Notice::optimization_finished();
		}
	}
	
	/**
	 *
	 */
	public function maybe_show_optimize_notice () {
		if ( get_option( OMGF_Admin_Settings::OMGF_OPTIMIZATION_COMPLETE ) ) {
			// If any notices were set in a previous run, unset them.
			OMGF_Admin_Notice::unset_notice( 'omgf-optimize' , 'success' );
			
			return;
		}
		
		OMGF_Admin_Notice::set_notice(
			__( 'OMGF is ready to optimize your Google Fonts. <a href="#" id="omgf-optimize">Start optimization</a>.', $this->plugin_text_domain ),
			'omgf-optimize',
			false
		);
	}
}
