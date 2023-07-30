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

namespace OMGF;

use OMGF\Helper as OMGF;
use OMGF\Admin\Notice;
use OMGF\Admin\Settings;
use OMGF\Admin\Updates;

defined( 'ABSPATH' ) || exit;

class Admin {

	const OMGF_ADMIN_JS_HANDLE  = 'omgf-admin-js';
	const OMGF_ADMIN_CSS_HANDLE = 'omgf-admin-css';

	/** @var array $stale_cache_options */
	private $stale_cache_options = [];

	/**
	 * OMGF_Admin constructor.
	 */
	public function __construct() {
		 /**
		 * Filterable list of options that marks the cache as stale.
		 */
		$this->stale_cache_options = apply_filters(
			'omgf_admin_stale_cache_options',
			[
				Settings::OMGF_ADV_SETTING_SUBSETS,
			]
		);

		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ] );
		add_action( 'admin_notices', [ $this, 'print_notices' ] );

		$this->do_optimize_settings();
		$this->do_detection_settings();
		$this->do_advanced_settings();
		$this->do_help();
		$this->maybe_handle_failed_premium_plugin_updates();
		$this->maybe_do_after_update_notice();

		add_filter( 'alloptions', [ $this, 'force_optimized_fonts_from_db' ] );
		add_filter( 'pre_update_option_omgf_cache_keys', [ $this, 'clean_up_cache' ], 10, 3 );
		add_filter( 'pre_update_option', [ $this, 'settings_changed' ], 10, 3 );
	}

	/**
	 * Enqueues the necessary JS and CSS and passes options as a JS object.
	 *
	 * @param $hook
	 */
	public function enqueue_admin_scripts( $hook ) {
		if ( $hook == 'settings_page_optimize-webfonts' ) {
			wp_enqueue_script( self::OMGF_ADMIN_JS_HANDLE, plugin_dir_url( OMGF_PLUGIN_FILE ) . 'assets/js/omgf-admin.js', [ 'jquery' ], filemtime( OMGF_PLUGIN_DIR . 'assets/js/omgf-admin.js' ), true );
			wp_enqueue_style( self::OMGF_ADMIN_CSS_HANDLE, plugin_dir_url( OMGF_PLUGIN_FILE ) . 'assets/css/omgf-admin.css', [], filemtime( OMGF_PLUGIN_DIR . 'assets/css/omgf-admin.css' ) );
		}
	}

	/**
	 * Add notice to admin screen.
	 */
	public function print_notices() {
		Notice::print_notices();
	}

	/**
	 * Local Fonts tab
	 */
	private function do_optimize_settings() {
		new Admin\Settings\Optimize();
	}

	/**
	 * Detection Settings tab
	 *
	 * @return void
	 */
	private function do_detection_settings() {
		new Admin\Settings\Detection();
	}

	/**
	 * Advanced Settings tab
	 *
	 * @return void
	 */
	private function do_advanced_settings() {
		new Admin\Settings\Advanced();
	}

	/**
	 * Help Tab
	 *
	 * @return void
	 */
	private function do_help() {
		new Admin\Settings\Help();
	}

	/**
	 * Add failsafe for failing premium plugin updates.
	 *
	 * @return Updates
	 */
	private function maybe_handle_failed_premium_plugin_updates() {
		return new Admin\Updates(
			[
				'4027' => [
					'slug'            => 'host-google-fonts-pro',
					'basename'        => 'host-google-fonts-pro/host-google-fonts-pro.php',
					'transient_label' => 'omgf_pro',
				],
				'8887' => [
					'slug'            => 'omgf-additional-fonts',
					'basename'        => 'omgf-additional-fonts/omgf-additional-fonts.php',
					'transient_label' => 'omgf_af',
				],
			],
			'host-webfonts-local',
			'omgf'
		);
	}

	/**
	 * Checks if an update notice should be displayed after updating.
	 */
	private function maybe_do_after_update_notice() {
		if ( OMGF_CURRENT_DB_VERSION != false && version_compare( OMGF_CURRENT_DB_VERSION, OMGF_DB_VERSION, '<' ) ) {
			Notice::set_notice(
				sprintf(
					__( 'Thank you for updating OMGF to v%1$s! This version contains database changes. <a href="%2$s">Verify your settings</a> and make sure everything is as you left it or, <a href="%3$s">view the changelog</a> for details. ', 'host-webfonts-local' ),
					OMGF_DB_VERSION,
					admin_url( Settings::OMGF_OPTIONS_GENERAL_PAGE_OPTIMIZE_WEBFONTS ),
					admin_url( Settings::OMGF_PLUGINS_INSTALL_CHANGELOG_SECTION )
				),
				'omgf-post-update'
			);
		}
	}

	/**
	 * @since  v5.0.5 Forces get_option() to fetch a fresh copy of omgf_optimized_fonts from the database,
	 *               we're doing plenty to limit reads from the DB already. So, this is warranted.
	 *
	 * @see    OMGF::optimized_fonts()
	 *
	 * @param  array $alloptions
	 *
	 * @return array
	 */
	public function force_optimized_fonts_from_db( $alloptions ) {
		if (
			isset( $alloptions[ Settings::OMGF_OPTIMIZE_SETTING_OPTIMIZED_FONTS ] )
			&& $alloptions[ Settings::OMGF_OPTIMIZE_SETTING_OPTIMIZED_FONTS ] == false
		) {
			unset( $alloptions[ Settings::OMGF_OPTIMIZE_SETTING_OPTIMIZED_FONTS ] );
		}

		return $alloptions;
	}

	/**
	 * Triggered when unload settings is changed, cleans up old cache files.
	 *
	 * TODO: Clean up doesn't work on 2nd run?
	 *
	 * @param $old_value
	 * @param $value
	 * @param $option_name
	 */
	public function clean_up_cache( $value, $old_value ) {
		if ( $old_value == $value ) {
			return $value;
		}

		if ( $old_value == null ) {
			return $value;
		}

		$cache_keys = explode( ',', $old_value );

		foreach ( $cache_keys as $key ) {
			$entries = array_filter( (array) glob( OMGF_UPLOAD_DIR . "/*$key" ) );

			foreach ( $entries as $entry ) {
				OMGF::delete( $entry );
			}
		}

		return $value;
	}

	/**
	 * Shows notice if $option_name is in $show_notice array.
	 *
	 * @param $new_value
	 * @param $old_settings
	 *
	 * @see $show_notice
	 *
	 * @return mixed
	 */
	public function settings_changed( $value, $option_name, $old_value ) {
		/**
		 * Don't show this message on the Main tab.
		 */
		if ( array_key_exists( 'tab', $_GET ) && $_GET['tab'] === Settings::OMGF_SETTINGS_FIELD_OPTIMIZE ) {
			return $value;
		}

		if ( ! in_array( $option_name, $this->stale_cache_options ) ) {
			return $value;
		}

		/**
		 * If $old_value equals false, that means it's never been set before.
		 */
		if ( $value != $old_value && $old_value !== false ) {
			global $wp_settings_errors;

			$show_message = true;

			if ( ! empty( $wp_settings_errors ) ) {
				foreach ( $wp_settings_errors as $error ) {
					if ( strpos( $error['code'], 'omgf' ) !== false ) {
						$show_message = false;

						break;
					}
				}

				if ( $show_message ) {
					$wp_settings_errors = [];
				}
			}

			if ( $show_message ) {
				OMGF::update_option( Settings::OMGF_CACHE_IS_STALE, true );

				add_settings_error(
					'general',
					'omgf_cache_style',
					sprintf(
						__( 'OMGF\'s cached stylesheets don\'t reflect the current settings. Refresh the cache from the <a href="%s">Task Manager</a>.', 'host-webfonts-local' ),
						admin_url( Settings::OMGF_OPTIONS_GENERAL_PAGE_OPTIMIZE_WEBFONTS )
					),
					'success'
				);
			}
		}

		return $value;
	}
}
