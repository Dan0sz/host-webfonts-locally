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
 * @copyright: © 2017 - 2024 Daan van den Bergh
 * @url      : https://daan.dev
 * * * * * * * * * * * * * * * * * * * */

namespace OMGF;

use OMGF\Admin\Settings;

class Helper {
	/**
	 * Property to hold all settings.
	 * @var mixed
	 */
	private static $settings;

	/**
	 * This is basically a wrapper around update_option() to offer a centralized interface for
	 * storing OMGF's settings in the wp_options table.
	 * @since v5.6.0
	 *
	 * @param mixed  $value
	 * @param string $setting
	 *
	 * @return bool
	 */
	public static function update_option( $setting, $value, $autoload = true ) {
		// If $setting starts with 'omgf_' it should be saved in a separate row.
		if ( str_starts_with( $setting, 'omgf_' ) ) {
			return update_option( $setting, $value, $autoload );
		}

		if ( self::$settings === null ) {
			self::$settings = self::get_settings(); // @codeCoverageIgnore
		}

		self::$settings[ $setting ] = $value;

		return update_option( 'omgf_settings', self::$settings );
	}

	/**
	 * Gets all settings for OMGF.
	 * @filter omgf_settings
	 * @since  5.5.7
	 * @return array
	 */
	public static function get_settings() {
		$defaults = apply_filters(
			'omgf_settings_defaults',
			[
				Settings::OMGF_OPTIMIZE_SETTING_DISPLAY_OPTION    => 'swap',
				Settings::OMGF_OPTIMIZE_SETTING_TEST_MODE         => '',
				Settings::OMGF_ADV_SETTING_LEGACY_MODE            => '',
				Settings::OMGF_ADV_SETTING_COMPATIBILITY          => '',
				Settings::OMGF_ADV_SETTING_AUTO_SUBSETS           => 'on',
				Settings::OMGF_ADV_SETTING_SUBSETS                => [ 'latin', 'latin-ext' ],
				Settings::OMGF_ADV_SETTING_DISABLE_ADMIN_BAR_MENU => '',
				Settings::OMGF_ADV_SETTING_DEBUG_MODE             => '',
				Settings::OMGF_ADV_SETTING_UNINSTALL              => '',
			]
		);

		if ( empty( self::$settings ) ) {
			self::$settings = get_option( 'omgf_settings', [] ); // @codeCoverageIgnore
		}

		return apply_filters( 'omgf_settings', wp_parse_args( self::$settings, $defaults ) );
	}

	/**
	 * This is basically a wrapper around delete_option() to offer a centralized interface for
	 * removing OMGF's settings in the wp_options table.
	 * @since v5.6.0
	 *
	 * @param string $setting
	 *
	 * @return bool
	 */
	public static function delete_option( $setting ) {
		if ( str_starts_with( $setting, 'omgf_' ) || apply_filters( 'omgf_delete_option', false, $setting ) ) {
			return delete_option( $setting );
		}

		// This prevents settings from 'mysteriously' returning after being unset.
		if ( empty( self::$settings ) ) {
			self::$settings = self::get_settings(); // @codeCoverageIgnore
		}

		unset( self::$settings[ $setting ] );

		return update_option( 'omgf_settings', self::$settings );
	}

	/**
	 * @return array
	 *
	 * @codeCoverageIgnore
	 */
	public static function preloaded_fonts() {
		static $preloaded_fonts = [];

		if ( empty( $preloaded_fonts ) ) {
			$preloaded_fonts = self::get_option( Settings::OMGF_OPTIMIZE_SETTING_PRELOAD_FONTS, [] );
		}

		return $preloaded_fonts;
	}

	/**
	 * Method to retrieve OMGF's settings from database.
	 * WARNING: DO NOT ATTEMPT TO RETRIEVE WP CORE SETTINGS USING THIS METHOD. IT WILL FAIL.
	 * @filter omgf_setting_{$name}
	 * @since  v5.6.0
	 *
	 * @param mixed  $default (optional)
	 * @param string $name
	 */
	public static function get_option( $name, $default = null ) {
		// If $name starts with 'omgf_' it means it is saved in a separate row.
		if ( str_starts_with( $name, 'omgf_' ) ) {
			$value = get_option( $name, $default );

			return apply_filters( 'omgf_setting_' . str_replace( 'omgf_', '', $name ), $value );
		}

		$value = self::get_settings()[ $name ] ?? $default;

		if ( empty( $value ) && ! $default && $name === Settings::OMGF_ADV_SETTING_SUBSETS ) {
			$default = [ 'latin', 'latin-ext' ]; // @codeCoverageIgnore
		}

		if ( empty( $value ) && $value !== '0' && $default !== null ) {
			$value = $default;
		}

		return apply_filters( "omgf_setting_$name", $value );
	}

	/**
	 * @return array
	 *
	 * @codeCoverageIgnore
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
	 *
	 * @codeCoverageIgnore
	 */
	public static function unloaded_stylesheets() {
		static $unloaded_stylesheets = [];

		if ( empty( $unloaded_stylesheets ) ) {
			$unloaded_stylesheets = explode( ',', self::get_option( Settings::OMGF_OPTIMIZE_SETTING_UNLOAD_STYLESHEETS, '' ) );
		}

		return array_filter( $unloaded_stylesheets );
	}

	/**
	 * @param $handle
	 *
	 * @return string
	 */
	public static function get_cache_key( $handle ) {
		$cache_keys = self::cache_keys();

		foreach ( $cache_keys as $key ) {
			/**
			 * @since v4.5.16 Convert $handle to lowercase, because $key is saved lowercase, too.
			 */
			if ( str_contains( $key, strtolower( $handle ) ) ) {
				return $key;
			}
		}

		return '';
	}

	/**
	 * Fetch cache keys from the DB.
	 * @since v5.6.4 Extract cache keys from Optimized Fonts option if the option itself appears empty.
	 * @return array
	 *
	 * @codeCoverageIgnore
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
			$optimized_fonts = self::admin_optimized_fonts(); // @codeCoverageIgnore

			$cache_keys = array_keys( $optimized_fonts ); //@codeCoverageIgnore
		}

		return $cache_keys;
	}

	/**
	 * Optimized Local Fonts to be displayed in the Optimize Local Fonts table.
	 * Use a static variable to reduce database reads/writes.
	 * @since v4.5.7
	 *
	 * @param bool  $force_add
	 * @param array $maybe_add If it doesn't exist, it's added to the cache layer.
	 *
	 * @return array
	 *
	 * @codeCoverageIgnore
	 */
	public static function admin_optimized_fonts( $maybe_add = [], $force_add = false ) {
		static $optimized_fonts = [];

		/**
		 * Get a fresh copy from the database if $optimized_fonts is empty|null|false (on 1st run)
		 */
		if ( empty( $optimized_fonts ) ) {
			$optimized_fonts = self::get_option( Settings::OMGF_OPTIMIZE_SETTING_OPTIMIZED_FONTS, [] );
		}

		/**
		 * get_option() should take care of this, but sometimes it doesn't.
		 * @since v4.5.6
		 */
		if ( is_string( $optimized_fonts ) && $optimized_fonts !== '' ) {
			$optimized_fonts = unserialize( $optimized_fonts ); // @codeCoverageIgnore
		}

		/**
		 * If $maybe_add doesn't exist in the cache layer yet, add it.
		 * @since v4.5.7
		 */
		if ( ! empty( $maybe_add ) && ( ! isset( $optimized_fonts[ key( $maybe_add ) ] ) || $force_add ) ) {
			$optimized_fonts = array_merge( $optimized_fonts, $maybe_add );
		}

		return $optimized_fonts ?: [];
	}

	/**
	 * Optimized Local Fonts to be used in the frontend. Doesn\'t contain unloaded fonts.
	 * Use a static variable to reduce database reads/writes.
	 * @since v5.8.1
	 *
	 * @param bool  $force_add
	 * @param array $maybe_add If it doesn't exist, it's added to the cache layer.
	 *
	 * @return array
	 *
	 * @codeCoverageIgnore
	 */
	public static function optimized_fonts( $maybe_add = [], $force_add = false ) {
		static $optimized_fonts = [];

		/**
		 * Get a fresh copy from the database if $optimized_fonts is empty|null|false (on 1st run)
		 */
		if ( empty( $optimized_fonts ) ) {
			$optimized_fonts = self::get_option( Settings::OMGF_OPTIMIZE_SETTING_OPTIMIZED_FONTS_FRONTEND, [] );
		}

		/**
		 * Fallback to original Optimized Fonts table.
		 */
		if ( empty( $optimized_fonts ) ) {
			$optimized_fonts = self::admin_optimized_fonts();
		}

		/**
		 * get_option() should take care of this, but sometimes it doesn't.
		 * @since v4.5.6
		 */
		if ( is_string( $optimized_fonts ) && $optimized_fonts !== '' ) {
			$optimized_fonts = unserialize( $optimized_fonts ); // @codeCoverageIgnore
		}

		/**
		 * If $maybe_add doesn't exist in the cache layer yet, add it.
		 * @since v4.5.7
		 */
		if ( ! empty( $maybe_add ) && ( ! isset( $optimized_fonts[ key( $maybe_add ) ] ) || $force_add ) ) {
			$optimized_fonts = array_merge( $optimized_fonts, $maybe_add );
		}

		return $optimized_fonts ?: [];
	}

	/**
	 * @since v5.4.4 Returns the available subsets in all requested fonts/stylesheets.
	 *               Functions as a temporary cache layer to reduce DB reads with get_option().
	 * @return array
	 *
	 * @codeCoverageIgnore
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
			$subsets = unserialize( $subsets ); // @codeCoverageIgnore
		}

		/**
		 * If $maybe_add doesn't exist in the cache layer yet, add it.
		 */
		if ( ! empty( $maybe_add ) && ( ! isset( $subsets[ key( $maybe_add ) ] ) ) ) {
			$subsets = array_merge( $subsets, $maybe_add );
		}

		/**
		 * Return only subsets that are available in all font families.
		 * @see OMGF_Optimize_Run
		 */
		if ( $intersect ) {
			/**
			 * @var array $filtered_subsets Contains an array of Font Families along with the available selected subsets, e.g.
			 *                              { 'Lato' => { 'latin', 'latin-ext' } }
			 */
			$filtered_subsets = apply_filters( 'omgf_available_filtered_subsets', array_values( array_filter( $subsets ) ) );

			self::debug_array( __( 'Filtered Subsets', 'host-webfonts-local' ), $filtered_subsets );

			if ( count( $filtered_subsets ) === 1 ) {
				return reset( $filtered_subsets ); // @codeCoverageIgnore
			}

			if ( ! empty( $filtered_subsets ) ) {
				return call_user_func_array( 'array_intersect', $filtered_subsets );
			}

			return $filtered_subsets;
		}

		return apply_filters( 'omgf_available_subsets', $subsets );
	}

	/**
	 * To prevent "Cannot use output buffering  in output buffering display handlers" errors, I introduced a debug
	 * array feature, to easily display, well, arrays in the debug log (duh!)
	 * @since v5.3.7
	 *
	 * @param array|object $array The array to be displayed in the debug log
	 * @param string       $name  A descriptive name to be shown in the debug log
	 *
	 * @return void
	 *
	 * @codeCoverageIgnore
	 */
	public static function debug_array( $name, $array ) {
		if ( ! self::get_option( Settings::OMGF_ADV_SETTING_DEBUG_MODE ) ||
			( self::get_option( Settings::OMGF_ADV_SETTING_DEBUG_MODE ) && file_exists( self::log_file() ) && filesize( self::log_file() ) > MB_IN_BYTES ) ) {
			return;
		}

		if ( ! is_array( $array ) && ! is_object( $array ) ) {
			return;
		}

		self::debug( __( 'Showing debug information for', 'host-webfonts-local' ) . ': ' . $name );

		foreach ( $array as $key => $elem ) {
			if ( is_array( $elem ) || is_object( $elem ) ) {
				self::debug_array(
					sprintf( __( 'Subelement %s is array/object', 'host-webfonts-local' ), $key ),
					$elem
				);

				continue;
			}

			error_log(
				current_time( 'Y-m-d H:i:s' ) . ' ' . microtime() . ': ' . $key . ' => ' . $elem . "\n",
				3,
				self::log_file()
			);
		}
	}

	/**
	 * Returns the absolute path to the log file.
	 * @return string
	 *
	 * @codeCoverageIgnore
	 */
	public static function log_file() {
		static $log_file;

		if ( empty( $log_file ) ) {
			$log_file = trailingslashit( WP_CONTENT_DIR ) . 'omgf-debug.log';
		}

		return $log_file;
	}

	/**
	 * Global debug logging function. Stops logging if log size exceeds 1MB.
	 *
	 * @param mixed $message
	 *
	 * @return void
	 *
	 * @codeCoverageIgnore
	 */
	public static function debug( $message ) {
		if ( ! self::get_option( Settings::OMGF_ADV_SETTING_DEBUG_MODE ) ||
			( self::get_option( Settings::OMGF_ADV_SETTING_DEBUG_MODE ) && file_exists( self::log_file() ) && filesize( self::log_file() ) > MB_IN_BYTES ) ) {
			return;
		}

		error_log(
			current_time( 'Y-m-d H:i:s' ) . ' ' . microtime() . ": $message\n",
			3,
			self::log_file()
		); // @codeCoverageIgnore
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
	 * Generate a request to $uri including the required parameters for OMGF to run in the frontend.
	 *
	 * @since v5.4.4 Added omgf_optimize_run_args filter so other plugins can add query parameters to the Save & Optimize routine.
	 *
	 * @param $url A (relative or absolute) URL, defaults to home URL.
	 *
	 * @return string
	 */
	public static function no_cache_optimize_url( $url = '' ) {
		if ( ! $url ) {
			$url = get_home_url();
		}

		if ( wp_make_link_relative( $url ) === $url ) {
			$url = home_url( $url ); // @codeCoverageIgnore
		}

		$args = apply_filters(
			'omgf_optimize_run_args',
			[
				'omgf_optimize' => 1,
				'nocache'       => substr(
					md5( microtime() ),
					wp_rand( 0, 26 ),
					5
				),
			]
		);

		return add_query_arg( $args, $url );
	}

	/**
	 * @param array $post
	 *
	 * @return bool
	 *
	 * @codeCoverageIgnore
	 */
	public static function is_running_optimize( $post = [] ) {
		return apply_filters( 'omgf_is_running_optimize', ( array_key_exists( 'omgf_optimize', $_GET ) || array_key_exists( 'omgf_optimize', $post ) ) );
	}
}
