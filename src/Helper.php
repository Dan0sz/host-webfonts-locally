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

namespace OMGF;

use OMGF\Admin\Settings;

class Helper {
	/**
	 * Property to hold all settings.
	 * @var array
	 */
	private static $settings = [];

	/**
	 * @var array $preloaded_fonts
	 */
	private static $preloaded_fonts = [];

	/**
	 * @var array $unloaded_fonts
	 */
	private static $unloaded_fonts = [];

	/**
	 * @var array $unloaded_stylesheets
	 */
	private static $unloaded_stylesheets = [];

	/**
	 * @var array $cache_keys
	 */
	private static $cache_keys = [];

	/**
	 * @var array $optimized_fonts
	 */
	private static $optimized_fonts = [];

	/**
	 * @var array $admin_optimized_fonts
	 */
	private static $admin_optimized_fonts = [];

	/**
	 * To prevent "Cannot use output buffering  in output buffering display handlers" errors, I introduced a debug
	 * array feature, to easily display, well, arrays in the debug log (duh!)
	 *
	 * @since v5.3.7
	 *
	 * @param string       $name  A descriptive name to be shown in the debug log
	 *
	 * @param array|object $array The array to be displayed in the debug log
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
	 * Method to retrieve OMGF's settings from database.
	 * WARNING: DO NOT ATTEMPT TO RETRIEVE WP CORE SETTINGS USING THIS METHOD. IT WILL FAIL.
	 *
	 * @filter omgf_setting_{$name}
	 *
	 * @since  v5.6.0
	 *
	 * @param string $name
	 *
	 * @param mixed  $default (optional)
	 */
	public static function get_option( $name, $default = null ) {
		// If $name starts with 'omgf_' it means it is saved in a separate row.
		if ( str_starts_with( $name, 'omgf_' ) ) {
			$value = get_option( $name, $default );
			$name  = str_replace( 'omgf_', '', $name );

			// get_option() should take care of this, but sometimes it doesn't.
			if ( is_string( $value ) ) {
				$value = maybe_unserialize( $value );
			}

			return apply_filters( "omgf_setting_$name", $value );
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
	 * Gets all settings for OMGF.
	 * @filter omgf_settings
	 * @since  5.5.7
	 * @return array
	 */
	public static function get_settings() {
		$defaults = apply_filters(
			'omgf_settings_defaults',
			[
				Settings::OMGF_OPTIMIZE_SETTING_DISPLAY_OPTION     => 'swap',
				Settings::OMGF_OPTIMIZE_SETTING_TEST_MODE          => '',
				Settings::OMGF_OPTIMIZE_SETTING_UNLOAD_STYLESHEETS => '',
				Settings::OMGF_OPTIMIZE_SETTING_CACHE_KEYS         => '',
				Settings::OMGF_ADV_SETTING_LEGACY_MODE             => '',
				Settings::OMGF_ADV_SETTING_COMPATIBILITY           => '',
				Settings::OMGF_ADV_SETTING_SUBSETS                 => [ 'latin', 'latin-ext' ],
				Settings::OMGF_ADV_SETTING_DISABLE_ADMIN_BAR_MENU  => '',
				Settings::OMGF_ADV_SETTING_DEBUG_MODE              => '',
				Settings::OMGF_ADV_SETTING_UNINSTALL               => '',
			]
		);

		if ( empty( self::$settings ) ) {
			self::$settings = get_option( 'omgf_settings', [] ); // @codeCoverageIgnore
		}

		return apply_filters( 'omgf_settings', wp_parse_args( self::$settings, $defaults ) );
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
	 * Delete a file or directory from the filesystem.
	 *
	 * @param $entry
	 *
	 * @return bool|void
	 */
	public static function delete( $entry ) {
		if ( is_link( $entry ) ) {
			return unlink( $entry );
		}

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
	 * This is basically a wrapper around delete_option() to offer a centralized interface for
	 * removing OMGF's settings in the wp_options table.
	 *
	 * @since v5.6.0
	 *
	 * @param string $setting
	 *
	 * @return bool
	 */
	public static function delete_option( $setting ) {
		if ( str_starts_with( $setting, 'omgf_' ) || apply_filters( 'omgf_delete_option', false, $setting ) ) {
			$deleted = delete_option( $setting );

			if ( $deleted ) {
				self::reset_cache();
			}

			return $deleted;
		}

		// This prevents settings from 'mysteriously' returning after being unset.
		if ( empty( self::$settings ) ) {
			self::$settings = self::get_settings(); // @codeCoverageIgnore
		}

		unset( self::$settings[ $setting ] );

		$deleted = update_option( 'omgf_settings', self::$settings );

		if ( $deleted ) {
			self::reset_cache();
		}

		return $deleted;
	}

	/**
	 * Resets all static caches.
	 *
	 * @return void
	 */
	public static function reset_cache() {
		self::$settings              = [];
		self::$preloaded_fonts       = [];
		self::$unloaded_fonts        = [];
		self::$unloaded_stylesheets  = [];
		self::$cache_keys            = [];
		self::$admin_optimized_fonts = [];
		self::$optimized_fonts       = [];
	}

	/**
	 * Download $url and save as $filename.$extension to $path.
	 *
	 * @param mixed $url
	 * @param mixed $filename
	 * @param mixed $path
	 *
	 * @return string
	 */
	public static function download( $url, $filename, $path ) {
		$download = new Download( $url, $filename, $path );

		return apply_filters( 'omgf_download', $download->download() );
	}

	/**
	 * Flush the entire OMGF cache.
	 *
	 * @return void
	 * @throws \ReflectionException
	 */
	public static function flush_cache( $initiator = 'optimize-webfonts' ) {
		$cache = new Cache();

		$cache->flush( $initiator );
	}

	/**
	 * Flush only 3rd party stylesheet cache directories.
	 *
	 * @return void
	 */
	public static function flush_third_party_cache() {
		$cache = new Cache();

		$cache->flush_third_party();
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
	 * @param $handle
	 *
	 * @return string
	 */
	public static function get_cache_key( $handle ) {
		$cache_keys = self::cache_keys();
		$cache_key  = '';

		foreach ( $cache_keys as $key ) {
			/**
			 * @since v4.5.16 Convert $handle to lowercase, because $key is saved lowercase, too.
			 */
			if ( str_contains( $key, strtolower( $handle ) ) ) {
				$cache_key = $key;

				break;
			}
		}

		return apply_filters( 'omgf_cache_key', $cache_key, $handle );
	}

	/**
	 * Fetch cache keys from the DB.
	 * @since v5.6.4 Extract cache keys from Optimized Fonts option if the option itself appears empty.
	 * @return array
	 *
	 * @codeCoverageIgnore
	 */
	public static function cache_keys() {
		if ( empty( self::$cache_keys ) ) {
			// Returns a string with one empty element if the option is empty, that's why we array_filter it.
			self::$cache_keys = array_filter( explode( ',', self::get_option( Settings::OMGF_OPTIMIZE_SETTING_CACHE_KEYS, '' ) ) );
		}

		/**
		 * If the cache keys option is empty, this means that it hasn't been saved before. So, let's fetch
		 * the (default) stylesheet handles from the optimized fonts option.
		 */
		if ( empty( self::$cache_keys ) ) {
			$optimized_fonts = self::admin_optimized_fonts(); // @codeCoverageIgnore

			self::$cache_keys = array_keys( $optimized_fonts ); //@codeCoverageIgnore
		}

		return self::$cache_keys;
	}

	/**
	 * Optimized Local Fonts to be displayed in the Optimize Local Fonts table.
	 *
	 * Use a static variable to reduce database reads/writes.
	 *
	 * @since v4.5.7
	 *
	 * @param array $maybe_add If it doesn't exist, it's added to the cache layer.
	 *
	 * @param bool  $force_add
	 *
	 * @return array
	 *
	 * @codeCoverageIgnore
	 */
	public static function admin_optimized_fonts( $maybe_add = [], $force_add = false ) {
		/**
		 * Get a fresh copy from the database if self::$optimized_fonts is empty|null|false (on 1st run)
		 */
		if ( empty( self::$admin_optimized_fonts ) ) {
			self::$admin_optimized_fonts = self::get_option( Settings::OMGF_OPTIMIZE_SETTING_OPTIMIZED_FONTS, [] );
		}

		/**
		 * get_option() should take care of this, but sometimes it doesn't.
		 * @since v4.5.6
		 */
		if ( is_string( self::$admin_optimized_fonts ) && self::$admin_optimized_fonts !== '' ) {
			self::$admin_optimized_fonts = maybe_unserialize( self::$admin_optimized_fonts ); // @codeCoverageIgnore
		}

		/**
		 * If $maybe_add doesn't exist in the cache layer yet, add it.
		 * @since v4.5.7
		 */
		if ( ! empty( $maybe_add ) && ( ! isset( self::$admin_optimized_fonts[ key( $maybe_add ) ] ) || $force_add ) ) {
			self::$admin_optimized_fonts = array_merge( self::$admin_optimized_fonts, $maybe_add );
		}

		return apply_filters( 'omgf_filter_admin_optimized_fonts', self::$admin_optimized_fonts ?: [] );
	}

	/**
	 * Returns an array of settings rows, filtered by $needles, derived directly from the Settings class.
	 *
	 * Used in:
	 * - @see Uninstall::remove_db_entries()
	 * - @see Ajax::empty_cache()
	 *
	 * @param array $needles
	 * @param array $ignore
	 *
	 * @return array
	 * @throws \ReflectionException
	 */
	public static function get_db_rows_by( $needles = [], $ignore = [] ) {
		$settings = ( new \ReflectionClass( Settings::class ) )->getConstants();

		return array_filter( $settings, function ( $row, $constant ) use ( $needles, $ignore ) {
			foreach ( $needles as $needle ) {
				if ( str_starts_with( $constant, $needle ) && ! in_array( $row, $ignore, true ) ) {
					return true;
				}
			}

			return false;
		}, ARRAY_FILTER_USE_BOTH );
	}

	/**
	 * @param array $post
	 *
	 * @return bool
	 *
	 * @codeCoverageIgnore
	 */
	public static function is_running_optimize( $post = [] ) {
		$is_running = false;

		if ( isset( $_GET ) ) {
			$is_running = array_key_exists( 'omgf_optimize', $_GET );
		}

		return apply_filters( 'omgf_is_running_optimize', ( array_key_exists( 'omgf_optimize', $post ) || $is_running ) );
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
	 * If admin_optimized_fonts() is empty, but optimize has run, we can assume optimize has failed.
	 *
	 * @since v6.2.0
	 *
	 * @return bool
	 */
	public static function optimize_failed() {
		return empty( self::admin_optimized_fonts() ) && self::get_option( Settings::OMGF_FLAG_OPTIMIZE_HAS_RUN );
	}

	/**
	 * If admin_optimized_fonts() is not empty and optimize has run, we can assume optimize has succeeded.
	 *
	 * @since v6.2.0
	 *
	 * @return bool
	 */
	public static function optimize_succeeded() {
		return ! empty( self::admin_optimized_fonts() ) && self::get_option( Settings::OMGF_FLAG_OPTIMIZE_HAS_RUN );
	}

	/**
	 * Optimized Local Fonts to be used in the frontend. Doesn't contain unloaded fonts.
	 * Use a static variable to reduce database reads/writes.
	 *
	 * @since v5.8.1
	 *
	 * @param array $maybe_add If it doesn't exist, it's added to the cache layer.
	 *
	 * @param bool  $force_add
	 *
	 * @return array
	 *
	 * @codeCoverageIgnore
	 */
	public static function optimized_fonts( $maybe_add = [], $force_add = false ) {
		/**
		 * Get a fresh copy from the database if self::$optimized_fonts is empty|null|false (on 1st run)
		 */
		if ( empty( self::$optimized_fonts ) ) {
			self::$optimized_fonts = self::get_option( Settings::OMGF_OPTIMIZE_SETTING_OPTIMIZED_FONTS_FRONTEND, [] );
		}

		/**
		 * Fallback to the original Optimized Fonts table.
		 */
		if ( empty( self::$optimized_fonts ) ) {
			self::$optimized_fonts = self::admin_optimized_fonts();
		}

		/**
		 * get_option() should take care of this, but sometimes it doesn't.
		 * @since v4.5.6
		 */
		if ( is_string( self::$optimized_fonts ) && self::$optimized_fonts !== '' ) {
			self::$optimized_fonts = maybe_unserialize( self::$optimized_fonts ); // @codeCoverageIgnore
		}

		/**
		 * If $maybe_add doesn't exist in the cache layer yet, add it.
		 * @since v4.5.7
		 */
		if ( ! empty( $maybe_add ) && ( ! isset( self::$optimized_fonts[ key( $maybe_add ) ] ) || $force_add ) ) {
			self::$optimized_fonts = array_merge( self::$optimized_fonts, $maybe_add );
		}

		self::$optimized_fonts = apply_filters( 'omgf_filter_optimized_fonts', self::$optimized_fonts ?: [] );

		/**
		 * Just to make sure that everything keeps working.
		$preloaded_fonts = apply_filters( 'omgf_filter_preloaded_fonts', self::$preloaded_fonts );

		/**
		 * Just to make sure that everything keeps working.
		 */
		if ( has_filter( 'omgf_frontend_preloaded_fonts' ) ) {
			_deprecated_hook( 'omgf_frontend_preloaded_fonts', '6.1.4', 'omgf_filter_preloaded_fonts' );

			$preloaded_fonts = apply_filters( 'omgf_frontend_preloaded_fonts', $preloaded_fonts );
		}

		return $preloaded_fonts;
	}

	/**
	 * @return array
	 *
	 * @codeCoverageIgnore
	 */
	public static function unloaded_fonts() {
		if ( empty( self::$unloaded_fonts ) ) {
			self::$unloaded_fonts = self::get_option( Settings::OMGF_OPTIMIZE_SETTING_UNLOAD_FONTS, [] );
		}

		return apply_filters( 'omgf_filter_unloaded_fonts', self::$unloaded_fonts );
	}

	/**
	 * @return array
	 *
	 * @codeCoverageIgnore
	 */
	public static function unloaded_stylesheets() {
		if ( empty( self::$unloaded_stylesheets ) ) {
			// Returns a string with one empty element if the option is empty, that's why we array_filter it.
			self::$unloaded_stylesheets = array_filter( explode( ',', self::get_option( Settings::OMGF_OPTIMIZE_SETTING_UNLOAD_STYLESHEETS, '' ) ) );
		}

		return apply_filters( 'omgf_filter_unloaded_stylesheets', self::$unloaded_stylesheets );
	}

	/**
	 * This is basically a wrapper around update_option() to offer a centralized interface for
	 * storing OMGF's settings in the wp_options table.
	 *
	 * @since v5.6.0
	 *
	 * @param string $setting
	 *
	 * @param mixed  $value
	 *
	 * @return bool
	 */
	public static function update_option( $setting, $value, $autoload = true ) {
		// If $setting starts with 'omgf_' it should be saved in a separate row.
		if ( str_starts_with( $setting, 'omgf_' ) ) {
			$updated = update_option( $setting, $value, $autoload );

			if ( $updated ) {
				self::reset_cache();
			}

			return $updated;
		}

		if ( empty( self::$settings ) ) {
			self::$settings = self::get_settings(); // @codeCoverageIgnore
		}

		self::$settings[ $setting ] = $value;

		$updated = update_option( 'omgf_settings', self::$settings );

		if ( $updated ) {
			self::reset_cache();
		}

		return $updated;
	}
}
