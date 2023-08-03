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
 * @copyright: © 2017 - 2023 Daan van den Bergh
 * @url      : https://daan.dev
 * * * * * * * * * * * * * * * * * * * */

namespace OMGF;

use OMGF\Admin\Settings;
use OMGF\Download;
use OMGF\StylesheetGenerator;

class Helper {
	/**
	 * Property to hold all settings.
	 *
	 * @var mixed
	 */
	private static $settings;

	/**
	 * Gets all settings for OMGF.
	 *
	 * @filter omgf_settings
	 *
	 * @since 5.5.7
	 *
	 * @return array
	 */
	public static function get_settings() {
		$defaults = apply_filters(
			'omgf_settings_defaults',
			[
				Settings::OMGF_OPTIMIZE_SETTING_AUTO_SUBSETS => '',
				Settings::OMGF_OPTIMIZE_SETTING_DISPLAY_OPTION => '',
				Settings::OMGF_OPTIMIZE_SETTING_TEST_MODE => '',
				Settings::OMGF_ADV_SETTING_COMPATIBILITY  => '',
				Settings::OMGF_ADV_SETTING_SUBSETS        => [],
				Settings::OMGF_ADV_SETTING_DEBUG_MODE     => '',
				Settings::OMGF_ADV_SETTING_UNINSTALL      => '',
			]
		);

		if ( empty( self::$settings ) ) {
			self::$settings = get_option( 'omgf_settings', [] );
		}

		return apply_filters( 'omgf_settings', wp_parse_args( self::$settings, $defaults ) );
	}

	/**
	 * Method to retrieve OMGF's settings from database.
	 *
	 * WARNING: DO NOT ATTEMPT TO RETRIEVE WP CORE SETTINGS USING THIS METHOD. IT WILL FAIL.
	 *
	 * @filter omgf_setting_{$name}
	 *
	 * @param string $name
	 * @param mixed  $default (optional)
	 *
	 * @since v5.6.0
	 */
	public static function get_option( $name, $default = null ) {
		// If $name starts with 'omgf_' it means it is saved in a separate row.
		if ( strpos( $name, 'omgf_' ) === 0 ) {
			$value = get_option( $name, $default );

			return apply_filters( 'omgf_setting_' . str_replace( 'omgf_', '', $name ), $value );
		}

		$value = self::get_settings()[ $name ] ?? '';

		if ( empty( $value ) && ! $default && $name === Settings::OMGF_ADV_SETTING_SUBSETS ) {
			$default = [ 'latin', 'latin-ext' ];
		}

		if ( empty( $value ) && $default !== null ) {
			$value = $default;
		}

		return apply_filters( "omgf_setting_$name", $value );
	}

	/**
	 * This is basically a wrapper around update_option() to offer a centralized interface for
	 * storing OMGF's settings in the wp_options table.
	 *
	 * @since v5.6.0
	 *
	 * @param string $setting
	 * @param mixed  $value
	 *
	 * @return void
	 */
	public static function update_option( $setting, $value ) {
		// If $setting starts with 'omgf_' it should be saved in a separate row.
		if ( strpos( $setting, 'omgf_' ) === 0 ) {
			update_option( $setting, $value );

			return;
		}

		if ( self::$settings === null ) {
			self::$settings = self::get_settings();
		}

		self::$settings[ $setting ] = $value;

		return update_option( 'omgf_settings', self::$settings );
	}

	/**
	 * This is basically a wrapper around delete_option() to offer a centralized interface for
	 * removing OMGF's settings in the wp_options table.
	 *
	 * @since v5.6.0
	 *
	 * @param string $setting
	 *
	 * @return void
	 */
	public static function delete_option( $setting ) {
		if ( strpos( $setting, 'omgf_' ) === 0 || apply_filters( 'omgf_delete_option', false, $setting ) ) {
			return delete_option( $setting );
		}

		// This prevents settings from 'mysteriously' returning after being unset.
		if ( empty( self::$settings ) ) {
			self::$settings = self::get_settings();
		}

		unset( self::$settings[ $setting ] );

		return update_option( 'omgf_settings', self::$settings );
	}

	/**
	 * Optimized Local Fonts to be displayed in the Optimize Local Fonts table.
	 *
	 * Use a static variable to reduce database reads/writes.
	 *
	 * @since v4.5.7
	 *
	 * @param array $maybe_add If it doesn't exist, it's added to the cache layer.
	 * @param bool  $force_add
	 *
	 * @return array
	 */
	public static function optimized_fonts( $maybe_add = [], $force_add = false ) {
		/** @var array $optimized_fonts Cache layer */
		static $optimized_fonts;

		/**
		 * Get a fresh copy from the database if $optimized_fonts is empty|null|false (on 1st run)
		 */
		if ( empty( $optimized_fonts ) ) {
			$optimized_fonts = self::get_option( Settings::OMGF_OPTIMIZE_SETTING_OPTIMIZED_FONTS, [] );
		}

		/**
		 * get_option() should take care of this, but sometimes it doesn't.
		 *
		 * @since v4.5.6
		 */
		if ( is_string( $optimized_fonts ) ) {
			// phpcs:ignore
			$optimized_fonts = unserialize( $optimized_fonts );
		}

		/**
		 * If $maybe_add doesn't exist in the cache layer yet, add it.
		 *
		 * @since v4.5.7
		 */
		if ( ! empty( $maybe_add ) && ( ! isset( $optimized_fonts[ key( $maybe_add ) ] ) || $force_add ) ) {
			$optimized_fonts = array_merge( $optimized_fonts, $maybe_add );
		}

		return $optimized_fonts;
	}

	/**
	 * @return array
	 */
	public static function preloaded_fonts() {
		static $preloaded_fonts = [];

		if ( empty( $preloaded_fonts ) ) {
			$preloaded_fonts = self::get_option( Settings::OMGF_OPTIMIZE_SETTING_PRELOAD_FONTS, [] );
		}

		return $preloaded_fonts;
	}

	/**
	 * @return array
	 */
	public static function unloaded_fonts() {
		static $unloaded_fonts = [];

		if ( empty( $unloaded_fonts ) ) {
			$unloaded_fonts = self::get_option( Settings::OMGF_OPTIMIZE_SETTING_UNLOAD_FONTS, [] );
		}

		return $unloaded_fonts;
	}

	/**
	 * @return array
	 */
	public static function unloaded_stylesheets() {
		static $unloaded_stylesheets = [];

		if ( empty( $unloaded_stylesheets ) ) {
			$unloaded_stylesheets = explode( ',', self::get_option( Settings::OMGF_OPTIMIZE_SETTING_UNLOAD_STYLESHEETS, '' ) );
		}

		return array_filter( $unloaded_stylesheets );
	}

	/**
	 * Fetch cache keys from the DB.
	 *
	 * @since v5.6.4 Extract cache keys from Optimized Fonts option if the option itself appears empty.
	 *
	 * @return array
	 */
	public static function cache_keys() {
		static $cache_keys = [];

		if ( empty( $cache_keys ) ) {
			$cache_keys = explode( ',', self::get_option( Settings::OMGF_OPTIMIZE_SETTING_CACHE_KEYS, '' ) );
		}

		// Remove empty elements.
		$cache_keys = array_filter( $cache_keys );

		/**
		 * If the cache keys option is empty, this means that it hasn't been saved before. So, let's fetch
		 * the (default) stylesheet handles from the optimized fonts option.
		 */
		if ( empty( $cache_keys ) ) {
			$optimized_fonts = self::optimized_fonts();

			$cache_keys = array_keys( $optimized_fonts );
		}

		return $cache_keys;
	}

	/**
	 * @param $handle
	 *
	 * @return string
	 */
	public static function get_cache_key( $handle ) {
		$cache_keys = self::cache_keys();

		foreach ( $cache_keys as $index => $key ) {
			/**
			 * @since v4.5.16 Convert $handle to lowercase, because $key is saved lowercase, too.
			 */
			if ( strpos( $key, strtolower( $handle ) ) !== false ) {
				return $key;
			}
		}

		return '';
	}

	/**
	 * @since v5.4.4 Returns the subsets that're available in all requested fonts/stylesheets.
	 *
	 *               Functions as a temporary cache layer to reduce DB reads with get_option().
	 *
	 * @return array
	 */
	public static function available_used_subsets( $maybe_add = [], $intersect = false ) {
		static $subsets = [];

		if ( empty( $subsets ) ) {
			$subsets = self::get_option( Settings::OMGF_AVAILABLE_USED_SUBSETS, [] );
		}

		/**
		 * get_option() should take care of this, but sometimes it doesn't.
		 */
		if ( is_string( $subsets ) ) {
			// phpcs:ignore
			$subsets = unserialize( $subsets );
		}

		/**
		 * If $maybe_add doesn't exist in the cache layer yet, add it.
		 */
		if ( ! empty( $maybe_add ) && ( ! isset( $subsets[ key( $maybe_add ) ] ) ) ) {
			$subsets = array_merge( $subsets, $maybe_add );
		}

		/**
		 * Return only subsets that're available in all font families.
		 *
		 * @see OMGF_Optimize_Run
		 */
		if ( $intersect ) {
			/**
			 * @var array $filtered_subsets Contains an array of Font Families along with the available selected subsets, e.g.
			 *                              { 'Lato' => { 'latin', 'latin-ext' } }
			 */
			$filtered_subsets = array_values( array_filter( $subsets ) );

			self::debug_array( __( 'Filtered Subsets', 'host-webfonts-local' ), $filtered_subsets );

			if ( count( $filtered_subsets ) === 1 ) {
				return reset( $filtered_subsets );
			}

			if ( ! empty( $filtered_subsets ) ) {
				return call_user_func_array( 'array_intersect', $filtered_subsets );
			}

			return $filtered_subsets;
		}

		return $subsets;
	}

	/**
	 * Download $url and save as $filename.$extension to $path.
	 *
	 * @param mixed $url
	 * @param mixed $filename
	 * @param mixed $extension
	 * @param mixed $path
	 *
	 * @return string
	 */
	public static function download( $url, $filename, $extension, $path ) {
		$download = new Download( $url, $filename, $extension, $path );

		return $download->download();
	}

	/**
	 * @param mixed $fonts
	 *
	 * @return string
	 */
	public static function generate_stylesheet( $fonts, $plugin = 'OMGF' ) {
		$generator = new StylesheetGenerator( $fonts, $plugin );

		return $generator->generate();
	}

	/**
	 * Delete file or directory from filesystem.
	 *
	 * @param $entry
	 */
	public static function delete( $entry ) {
		if ( is_dir( $entry ) ) {
			$file = new \FilesystemIterator( $entry );

			// If dir is empty, valid() returns false.
			while ( $file->valid() ) {
				self::delete( $file->getPathName() );
				$file->next();
			}

			rmdir( $entry );
		} else {
			unlink( $entry );
		}
	}

	/**
	 * Global debug logging function. Stops logging if log size exceeds 1MB.
	 *
	 * @param mixed $message
	 * @return void
	 */
	public static function debug( $message ) {
		if (
			! self::get_option( Settings::OMGF_ADV_SETTING_DEBUG_MODE ) ||
			( self::get_option( Settings::OMGF_ADV_SETTING_DEBUG_MODE ) && file_exists( self::log_file() ) && filesize( self::log_file() ) > MB_IN_BYTES )
		) {
			return;
		}

		// phpcs:ignore
		error_log( current_time( 'Y-m-d H:i:s' ) . ' ' . microtime() . ": $message\n", 3, self::log_file() );
	}

	/**
	 * To prevent "Cannot use output buffering  in output buffering display handlers" errors, I introduced a debug array feature,
	 * to easily display, well, arrays in the debug log (duh!)
	 *
	 * @since v5.3.7
	 *
	 * @param $name  A descriptive name to be shown in the debug log
	 * @param $array The array to be displayed in the debug log
	 *
	 * @return void
	 */
	public static function debug_array( $name, $array ) {
		if (
			! self::get_option( Settings::OMGF_ADV_SETTING_DEBUG_MODE ) ||
			( self::get_option( Settings::OMGF_ADV_SETTING_DEBUG_MODE ) && file_exists( self::log_file() ) && filesize( self::log_file() ) > MB_IN_BYTES )
		) {
			return;
		}

		if ( ! is_array( $array ) && ! is_object( $array ) ) {
			return;
		}

		self::debug( __( 'Showing debug information for', 'host-webfonts-local' ) . ': ' . $name );

		foreach ( $array as $key => $elem ) {
			if ( is_array( $elem ) || is_object( $elem ) ) {
				self::debug_array( sprintf( __( 'Subelement %s is array/object', 'host-webfonts-local' ), $key ), $elem );

				continue;
			}

			// phpcs:ignore
			error_log( current_time( 'Y-m-d H:i:s' ) . ' ' . microtime() . ': ' . $key . ' => ' . $elem . "\n", 3, self::log_file() );
		}
	}

	/**
	 * Returns the absolute path to the log file.
	 *
	 * @return string
	 */
	public static function log_file() {
		static $log_file;

		if ( empty( $log_file ) ) {
			$log_file = trailingslashit( WP_CONTENT_DIR ) . 'omgf-debug.log';
		}

		return $log_file;
	}
}
