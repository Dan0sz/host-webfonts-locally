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

use OMGF\Helper as OMGF;
use OMGF\Admin\Notice;
use OMGF\Admin\Settings;
use OMGF\Admin\Updates;

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
		$this->do_advanced_settings();
		$this->do_help();
		$this->maybe_handle_failed_premium_plugin_updates();

		add_filter( 'alloptions', [ $this, 'force_optimized_fonts_from_db' ] );
		add_action( 'update_option_omgf_cache_keys', [ $this, 'clean_up_cache' ], 10, 2 );
		add_action( 'update_option_omgf_settings', [ $this, 'maybe_show_stale_cache_notice' ], 10, 2 );
	}

	/**
	 * Local Fonts tab
	 */
	private function do_optimize_settings() {
		new Admin\Settings\Optimize();
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
			], 'host-webfonts-local', 'omgf'
		);
	}

	/**
	 * Enqueues the necessary JS and CSS and passes options as a JS object.
	 *
	 * @param $hook
	 *
	 * @codeCoverageIgnore because we don't want to test core functions.
	 */
	public function enqueue_admin_scripts( $hook ) {
		if ( $hook == 'settings_page_' . Settings::OMGF_ADMIN_PAGE ) {
			wp_enqueue_script(
				self::OMGF_ADMIN_JS_HANDLE,
				plugin_dir_url( OMGF_PLUGIN_FILE ) . 'assets/js/omgf-admin.js',
				[ 'jquery' ],
				filemtime( OMGF_PLUGIN_DIR . 'assets/js/omgf-admin.js' ),
				true
			);
			wp_enqueue_style(
				self::OMGF_ADMIN_CSS_HANDLE,
				plugin_dir_url( OMGF_PLUGIN_FILE ) . 'assets/css/omgf-admin.css',
				[],
				filemtime( OMGF_PLUGIN_DIR . 'assets/css/omgf-admin.css' )
			);
		}
	}

	/**
	 * Add notice to admin screen.
	 *
	 * @codeCoverageIgnore
	 */
	public function print_notices() {
		Notice::print_notices();
	}

	/**
	 * @see    OMGF::admin_optimized_fonts()
	 * @since  v5.0.5 Forces get_option() to fetch a fresh copy of omgf_optimized_fonts from the database,
	 *               we're doing plenty to limit reads from the DB already. So, this is warranted.
	 *
	 * @param array $alloptions
	 *
	 * @return array
	 */
	public function force_optimized_fonts_from_db( $alloptions ) {
		if ( isset( $alloptions[ Settings::OMGF_OPTIMIZE_SETTING_OPTIMIZED_FONTS ] ) && ! $alloptions[ Settings::OMGF_OPTIMIZE_SETTING_OPTIMIZED_FONTS ] ) {
			unset( $alloptions[ Settings::OMGF_OPTIMIZE_SETTING_OPTIMIZED_FONTS ] );
		}

		return $alloptions;
	}

	/**
	 * Triggered when unload settings is changed, cleans up old cache files.
	 * TODO: Clean up doesn't work on 2nd run?
	 */
	public function clean_up_cache( $value, $old_value ) {
		if ( $old_value == $value ) {
			return; // @codeCoverageIgnore
		}

		if ( $old_value == null ) {
			return; // @codeCoverageIgnore
		}

		$cache_keys = explode( ',', $old_value );

		foreach ( $cache_keys as $key ) {
			$entries = array_filter( (array) glob( OMGF_UPLOAD_DIR . "/*$key" ) );

			foreach ( $entries as $entry ) {
				OMGF::delete( $entry );
			}
		}
	}

	/**
	 * Shows notice if $option_name is in $show_notice array.
	 *
	 * @see $show_message
	 *
	 * @param $old_values
	 * @param $values
	 *
	 * @return void
	 */
	public function maybe_show_stale_cache_notice( $old_values, $values ) {
		/**
		 * Don't show this message on the Main tab.
		 */
		if ( ! array_key_exists( 'tab', $_GET ) || ( $_GET[ 'tab' ] === Settings::OMGF_SETTINGS_FIELD_OPTIMIZE ) ) {
			return; // @codeCoverageIgnore
		}

		/**
		 * If either of these isn't an array, this means they haven't been set before.
		 */
		if ( ! is_array( $old_values ) || ! is_array( $values ) ) {
			return; // @codeCoverageIgnore
		}

		/**
		 * Fetch options from array, so we can compare both.
		 */
		$old = array_filter(
			$old_values,
			function ( $key ) {
				return in_array( $key, $this->stale_cache_options, true );
			},
			ARRAY_FILTER_USE_KEY
		);
		$new = array_filter(
			$values,
			function ( $key ) {
				return in_array( $key, $this->stale_cache_options, true );
			},
			ARRAY_FILTER_USE_KEY
		);

		$diff = $this->array_diff( $new, $old );

		if ( empty( $diff ) ) {
			return;
		}

		global $wp_settings_errors;

		$show_message = true;

		if ( ! empty( $wp_settings_errors ) ) {
			foreach ( $wp_settings_errors as $error ) {
				if ( str_contains( $error[ 'code' ], 'omgf' ) ) {
					$show_message = false;

					break;
				}
			}

			if ( $show_message ) {
				$wp_settings_errors = []; // @codeCoverageIgnore
			}
		}

		if ( $show_message ) {
			OMGF::update_option( Settings::OMGF_CACHE_IS_STALE, true );

			add_settings_error(
				'general',
				'omgf_cache_stale',
				sprintf(
					__(
						'OMGF\'s cached stylesheets don\'t reflect the current settings. Refresh the cache from the <a href="%s">Dashboard</a>.',
						'host-webfonts-local'
					),
					admin_url( Settings::OMGF_OPTIONS_GENERAL_PAGE_OPTIMIZE_WEBFONTS )
				),
				'success'
			);
		}
	}

	/**
	 * Recursively compares two arrays.
	 *
	 * @param array $array1
	 * @param array $array2
	 *
	 * @return bool Whether $array1 contains different values, $compared to array2.
	 */
	private function array_diff( $array1, $array2 ) {
		$diff = false;

		foreach ( $array1 as $key => $value ) {
			if ( is_array( $value ) ) {
				$diff = empty( $array2[ $key ] ) ? [] : $this->array_diff( $value, $array2[ $key ] );

				if ( $diff ) {
					break;
				}

				continue;
			}

			$diff = ! isset( $array2[ $key ] ) || $value !== $array2[ $key ];

			if ( $diff ) {
				break;
			}
		}

		return $diff;
	}
}
