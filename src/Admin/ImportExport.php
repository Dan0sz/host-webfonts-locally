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
* @copyright: © 2026 Daan van den Bergh
* @url      : https://daan.dev
* * * * * * * * * * * * * * * * * * * */

namespace OMGF\Admin;

use OMGF\Helper as OMGF;

/**
 * Exports OMGF's settings to, and imports them from, a JSON file.
 *
 * @since v6.4.0
 */
class ImportExport {
	const ACTION_EXPORT = 'omgf_export_settings';

	const ACTION_IMPORT = 'omgf_import_settings';

	const FIELD_FILE = 'omgf_import_file';

	const FIELD_INCLUDE_FONTS = 'omgf_export_include_fonts';

	const FORMAT_VERSION = 1;

	const MAX_FILE_SIZE = 2097152; // 2 MB

	const MAX_JSON_DEPTH = 16;

	/**
	 * Characters which never belong in a key or in a font property, e.g. because they'd allow breaking out of
	 * a CSS declaration or an HTML attribute.
	 */
	const UNSAFE_CHARS_REGEX = '/[<>{};"\'`\\\\\x00-\x1F\x7F]/';

	/**
	 * Build class.
	 */
	public function __construct() {
		add_action( 'admin_post_' . self::ACTION_EXPORT, [ $this, 'handle_export' ] );
		add_action( 'admin_post_' . self::ACTION_IMPORT, [ $this, 'handle_import' ] );
	}

	/**
	 * Options (rows in wp_options) which can be exported/imported. Options flagged with 'fonts' contain the
	 * Optimize Local Fonts data and are only exported when requested.
	 *
	 * @filter omgf_import_export_options
	 *
	 * @param bool $include_addons Include the options added by add-ons, e.g. OMGF Pro.
	 *
	 * @return array [ option_name => [ 'type' => settings|map|fonts, 'depth' => int, 'fonts' => bool, 'autoload' => bool ] ]
	 */
	public static function get_options( $include_addons = true ) {
		$options = [
			'omgf_settings'                                          => [ 'type' => 'settings' ],
			Settings::OMGF_OPTIMIZE_SETTING_PRELOAD_FONTS            => [ 'type' => 'map', 'depth' => 3, 'fonts' => true ],
			Settings::OMGF_OPTIMIZE_SETTING_UNLOAD_FONTS             => [ 'type' => 'map', 'depth' => 3, 'fonts' => true ],
			Settings::OMGF_OPTIMIZE_SETTING_OPTIMIZED_FONTS          => [ 'type' => 'fonts', 'fonts' => true, 'autoload' => false ],
			Settings::OMGF_OPTIMIZE_SETTING_OPTIMIZED_FONTS_FRONTEND => [ 'type' => 'fonts', 'fonts' => true ],
		];

		return $include_addons ? apply_filters( 'omgf_import_export_options', $options ) : $options;
	}

	/**
	 * Slugs of the add-ons active on this site, as listed in the export's metadata.
	 *
	 * @return array
	 */
	public static function get_active_addons() {
		$versions = self::get_meta()['versions'] ?? [];

		return array_values( array_diff( array_keys( is_array( $versions ) ? $versions : [] ), [ 'omgf' ] ) );
	}

	/**
	 * @param bool $include_fonts
	 *
	 * @return array
	 */
	private static function get_meta( $include_fonts = false ) {
		return apply_filters(
			'omgf_import_export_meta',
			[
				'exported_at'    => gmdate( 'c' ),
				'site_url'       => get_site_url(),
				'includes_fonts' => (bool) $include_fonts,
				'versions'       => [
					'omgf' => get_file_data( OMGF_PLUGIN_FILE, [ 'Version' => 'Version' ] )['Version'],
				],
			]
		);
	}

	/**
	 * Keys in the omgf_settings row which can be exported/imported. Only known settings (i.e. those with a
	 * default value) are allowed.
	 *
	 * @param bool $include_fonts Whether to include settings belonging to the Optimize Local Fonts data.
	 *
	 * @return array
	 */
	public static function get_setting_keys( $include_fonts = false ) {
		$keys = array_keys( OMGF::get_default_settings() );

		if ( $include_fonts ) {
			return $keys;
		}

		/**
		 * @filter omgf_import_export_font_settings Settings which belong to the Optimize Local Fonts data.
		 */
		$font_settings = apply_filters(
			'omgf_import_export_font_settings',
			[
				Settings::OMGF_OPTIMIZE_SETTING_CACHE_KEYS,
				Settings::OMGF_OPTIMIZE_SETTING_UNLOAD_STYLESHEETS,
			]
		);

		return array_values( array_diff( $keys, $font_settings ) );
	}

	/**
	 * Build the export data.
	 *
	 * @param bool $include_fonts Include the Optimize Local Fonts data.
	 *
	 * @return array
	 */
	public static function export( $include_fonts = false ) {
		$options = [];

		foreach ( self::get_options() as $option_name => $schema ) {
			if ( ! empty( $schema['fonts'] ) && ! $include_fonts ) {
				continue;
			}

			if ( $schema['type'] === 'settings' ) {
				$value = array_intersect_key( OMGF::get_settings(), array_flip( self::get_setting_keys( $include_fonts ) ) );
			} else {
				$value = OMGF::get_option( $option_name, [] );
			}

			$value = self::sanitize_option( $option_name, $value ?: [], $schema, $include_fonts, false );

			// Never export data an import would reject.
			if ( is_wp_error( $value ) ) {
				continue; // @codeCoverageIgnore
			}

			$options[ $option_name ] = $value;
		}

		$meta = self::get_meta( $include_fonts );

		/**
		 * Keys added by add-ons, so an import on a site without those add-ons can skip them.
		 *
		 * @since v6.4.0
		 */
		$meta['addon_keys'] = [
			'options'  => array_values( array_diff( array_keys( $options ), array_keys( self::get_options( false ) ) ) ),
			'settings' => array_values(
				array_diff( array_keys( $options['omgf_settings'] ?? [] ), array_keys( OMGF::get_default_settings( false ) ) )
			),
		];

		return [
			'plugin'  => 'omgf',
			'format'  => self::FORMAT_VERSION,
			'meta'    => $meta,
			'options' => $options,
		];
	}

	/**
	 * Encode the export data as JSON.
	 *
	 * @param bool $include_fonts
	 *
	 * @return string
	 */
	public static function export_json( $include_fonts = false ) {
		return wp_json_encode( self::export( $include_fonts ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
	}

	/**
	 * Decode and validate a JSON string. Never unserializes anything; objects are decoded as arrays.
	 *
	 * @param string $json
	 * @param array  $skipped Populated with the keys skipped, because they belong to inactive add-ons.
	 *
	 * @return array|\WP_Error Validated options, ready to be stored.
	 */
	public static function parse( $json, &$skipped = [] ) {
		if ( ! is_string( $json ) || $json === '' ) {
			return new \WP_Error( 'omgf_import_empty', __( 'The uploaded file is empty.', 'host-webfonts-local' ) );
		}

		if ( strlen( $json ) > self::MAX_FILE_SIZE ) {
			return new \WP_Error( 'omgf_import_too_large', __( 'The uploaded file is too large.', 'host-webfonts-local' ) );
		}

		$data = json_decode( $json, true, self::MAX_JSON_DEPTH );

		if ( ! is_array( $data ) || json_last_error() !== JSON_ERROR_NONE ) {
			return new \WP_Error( 'omgf_import_invalid_json', __( 'The uploaded file isn\'t a valid JSON file.', 'host-webfonts-local' ) );
		}

		return self::validate( $data, $skipped );
	}

	/**
	 * Validate decoded import data.
	 *
	 * @param array $data
	 * @param array $skipped Populated with the keys skipped, because they belong to inactive add-ons.
	 *
	 * @return array|\WP_Error
	 */
	public static function validate( $data, &$skipped = [] ) {
		$skipped = [ 'addons' => [], 'options' => [], 'settings' => [] ];

		if ( ! is_array( $data ) ||
			( $data['plugin'] ?? '' ) !== 'omgf' ||
			! is_int( $data['format'] ?? null ) ||
			$data['format'] < 1 ||
			$data['format'] > self::FORMAT_VERSION ||
			! isset( $data['options'] ) ||
			! is_array( $data['options'] ) ) {
			return new \WP_Error( 'omgf_import_invalid_format', __( 'The uploaded file isn\'t an OMGF settings export.', 'host-webfonts-local' ) );
		}

		$schemas   = self::get_options();
		$skippable = self::get_skippable_keys( $data['meta'] ?? [] );
		$unknown   = array_diff( array_keys( $data['options'] ), array_keys( $schemas ) );

		foreach ( $unknown as $i => $option_name ) {
			if ( in_array( $option_name, $skippable['options'], true ) ) {
				$skipped['options'][] = $option_name;

				unset( $unknown[ $i ], $data['options'][ $option_name ] );
			}
		}

		if ( ! empty( $unknown ) ) {
			return self::unknown_keys_error( $unknown );
		}

		if ( isset( $data['options']['omgf_settings'] ) && is_array( $data['options']['omgf_settings'] ) ) {
			$known = self::get_setting_keys( true );

			foreach ( array_keys( $data['options']['omgf_settings'] ) as $key ) {
				if ( ! in_array( $key, $known, true ) && in_array( $key, $skippable['settings'], true ) ) {
					$skipped['settings'][] = $key;

					unset( $data['options']['omgf_settings'][ $key ] );
				}
			}
		}

		if ( ! empty( $skipped['options'] ) || ! empty( $skipped['settings'] ) ) {
			$skipped['addons'] = $skippable['addons'];
		}

		$options = [];

		foreach ( $data['options'] as $option_name => $value ) {
			$value = self::sanitize_option( $option_name, $value, $schemas[ $option_name ], true );

			if ( is_wp_error( $value ) ) {
				return $value;
			}

			$options[ $option_name ] = $value;
		}

		return $options;
	}

	/**
	 * Keys an import may skip: those the export attributes to add-ons, if any of the add-ons listed in the export
	 * isn't active on this site. Everything else is validated as usual.
	 *
	 * @param mixed $meta The export's metadata.
	 *
	 * @return array [ 'addons' => string[], 'options' => string[], 'settings' => string[] ]
	 */
	private static function get_skippable_keys( $meta ) {
		$skippable = [ 'addons' => [], 'options' => [], 'settings' => [] ];

		if ( ! is_array( $meta ) || ! is_array( $meta['versions'] ?? null ) || ! is_array( $meta['addon_keys'] ?? null ) ) {
			return $skippable;
		}

		$addons = array_diff( array_keys( $meta['versions'] ), [ 'omgf' ], self::get_active_addons() );
		$addons = array_values( array_filter( $addons, [ __CLASS__, 'is_safe_key' ] ) );

		if ( empty( $addons ) ) {
			return $skippable;
		}

		$skippable['addons'] = $addons;

		foreach ( [ 'options', 'settings' ] as $type ) {
			$keys = $meta['addon_keys'][ $type ] ?? [];

			if ( ! is_array( $keys ) ) {
				continue; // @codeCoverageIgnore
			}

			$skippable[ $type ] = array_values( array_filter( $keys, function ( $key ) use ( $type ) {
				// Add-ons store their options with OMGF's prefix.
				return self::is_safe_key( $key ) && is_string( $key ) && ( $type === 'settings' || str_starts_with( $key, 'omgf_' ) );
			} ) );
		}

		return $skippable;
	}

	/**
	 * A warning listing the skipped keys, or an empty string if nothing was skipped.
	 *
	 * @param array $skipped As populated by self::validate().
	 *
	 * @return string
	 */
	public static function get_skipped_message( $skipped ) {
		$keys = array_merge( $skipped['options'] ?? [], $skipped['settings'] ?? [] );

		if ( empty( $keys ) ) {
			return '';
		}

		return sprintf(
			/* translators: 1: comma separated list of plugin slugs, 2: comma separated list of setting names. */
			__( 'These settings were skipped, because they belong to add-ons which aren\'t active on this site (%1$s): %2$s.', 'host-webfonts-local' ),
			esc_html( implode( ', ', $skipped['addons'] ?? [] ) ),
			implode( ', ', array_map( function ( $key ) {
				return '<code>' . esc_html( $key ) . '</code>';
			}, $keys ) )
		);
	}

	/**
	 * Store validated options. The omgf_settings row is merged with the current settings, other rows are replaced.
	 *
	 * @param array $options Output of self::validate()
	 *
	 * @return void
	 */
	public static function import( $options ) {
		$schemas = self::get_options();

		foreach ( $options as $option_name => $value ) {
			$schema = $schemas[ $option_name ] ?? null;

			if ( ! $schema ) {
				continue; // @codeCoverageIgnore
			}

			if ( $schema['type'] === 'settings' ) {
				$current = get_option( 'omgf_settings', [] );
				$value   = array_replace( is_array( $current ) ? $current : [], $value );
			}

			OMGF::update_option( $option_name, $value, $schema['autoload'] ?? true );
		}

		// Cached stylesheets don't reflect the imported settings, until Save & Optimize runs.
		OMGF::update_option( Settings::OMGF_FLAG_CACHE_IS_STALE, true );

		do_action( 'omgf_settings_imported', $options );
	}

	/**
	 * Validate and sanitize a single option's value.
	 *
	 * @param string $option_name
	 * @param mixed  $value
	 * @param array  $schema
	 * @param bool   $include_fonts Allow settings belonging to the Optimize Local Fonts data.
	 * @param bool   $strict        Reject unknown font properties instead of dropping them.
	 *
	 * @return mixed|\WP_Error
	 */
	private static function sanitize_option( $option_name, $value, $schema, $include_fonts, $strict = true ) {
		switch ( $schema['type'] ?? '' ) {
			case 'settings':
				$value = self::sanitize_settings( $value, $include_fonts, $strict );
				break;
			case 'map':
				$value = self::sanitize_map( $value, (int) ( $schema['depth'] ?? 1 ) );
				break;
			case 'fonts':
				$value = self::sanitize_fonts( $value, $strict );
				break;
			default:
				$value = false; // @codeCoverageIgnore
		}

		if ( $value === false ) {
			return new \WP_Error(
				'omgf_import_invalid_value',
				sprintf(
					/* translators: %s: option name. */
					__( 'The value for %s in the uploaded file is invalid.', 'host-webfonts-local' ),
					'<code>' . esc_html( $option_name ) . '</code>'
				)
			);
		}

		return $value;
	}

	/**
	 * Settings stored in the omgf_settings row: known keys only, with scalar values or lists of scalars.
	 *
	 * @param mixed $settings
	 * @param bool  $include_fonts
	 * @param bool  $strict Reject invalid values, instead of dropping them.
	 *
	 * @return array|false|\WP_Error
	 */
	private static function sanitize_settings( $settings, $include_fonts, $strict = true ) {
		if ( ! is_array( $settings ) ) {
			return false;
		}

		$allowed = self::get_setting_keys( $include_fonts );
		$unknown = array_diff( array_keys( $settings ), $allowed );

		if ( ! empty( $unknown ) ) {
			return self::unknown_keys_error( $unknown );
		}

		$sanitized = [];

		foreach ( $settings as $key => $value ) {
			$value = self::sanitize_setting( $key, $value );

			if ( $value === false ) {
				if ( $strict ) {
					return false;
				}

				continue;
			}

			$sanitized[ $key ] = $value;
		}

		return $sanitized;
	}

	/**
	 * @param string $key
	 * @param mixed  $value A scalar, or a list of scalars.
	 *
	 * @return string|array|false
	 */
	private static function sanitize_setting( $key, $value ) {
		$defaults = OMGF::get_default_settings();

		// A setting's shape (list or scalar) must match its default value.
		if ( is_array( $value ) !== is_array( $defaults[ $key ] ?? null ) ) {
			return false;
		}

		if ( is_array( $value ) ) {
			$list = [];

			foreach ( $value as $item ) {
				$item = self::sanitize_scalar( $item );

				if ( $item === false ) {
					return false;
				}

				$list[] = $item;
			}

			$value = $list;
		} else {
			$value = self::sanitize_scalar( $value );
		}

		return $value !== false && self::is_valid_setting( $key, $value ) ? $value : false;
	}

	/**
	 * Settings with a fixed set of options must use one of those options.
	 *
	 * @param string       $key
	 * @param string|array $value
	 *
	 * @return bool
	 */
	private static function is_valid_setting( $key, $value ) {
		if ( $key === Settings::OMGF_OPTIMIZE_SETTING_DISPLAY_OPTION ) {
			return is_string( $value ) && ( $value === '' || isset( Settings::OMGF_FONT_DISPLAY_OPTIONS[ $value ] ) );
		}

		if ( $key === Settings::OMGF_ADV_SETTING_SUBSETS ) {
			return is_array( $value ) && empty( array_diff( $value, array_keys( Settings::OMGF_SUBSETS ) ) );
		}

		return apply_filters( 'omgf_import_export_is_valid_setting', true, $key, $value );
	}

	/**
	 * Nested arrays (e.g. [ handle ][ font id ][ variant id ] => value) exactly $depth levels deep.
	 *
	 * @param mixed $map
	 * @param int   $depth
	 *
	 * @return array|false
	 */
	private static function sanitize_map( $map, $depth ) {
		if ( ! is_array( $map ) || $depth < 1 ) {
			return false;
		}

		$sanitized = [];

		foreach ( $map as $key => $value ) {
			if ( ! self::is_safe_key( $key ) ) {
				return false;
			}

			$value = $depth > 1 ? self::sanitize_map( $value, $depth - 1 ) : self::sanitize_scalar( $value );

			if ( $value === false ) {
				return false;
			}

			$sanitized[ $key ] = $value;
		}

		return $sanitized;
	}

	/**
	 * Optimized fonts: [ handle ][ font id ] => font object, each with a list of variant objects. Objects are
	 * rebuilt as stdClass from the (decoded) arrays, so nothing else is ever instantiated.
	 *
	 * @param mixed $fonts
	 * @param bool  $strict Reject unknown properties, instead of dropping them.
	 *
	 * @return array|false
	 */
	private static function sanitize_fonts( $fonts, $strict ) {
		if ( ! is_array( $fonts ) ) {
			return false;
		}

		$font_props    = [ 'id', 'family', 'variants', 'subsets' ];
		$variant_props = apply_filters(
			'omgf_import_export_font_variant_properties',
			[ 'id', 'fontFamily', 'fontStyle', 'fontWeight', 'woff2', 'woff', 'subset', 'range' ]
		);
		$sanitized     = [];

		foreach ( $fonts as $handle => $stylesheet ) {
			if ( ! self::is_safe_key( $handle ) || ! is_array( $stylesheet ) ) {
				return false;
			}

			$sanitized[ $handle ] = [];

			foreach ( $stylesheet as $font_id => $font ) {
				$font = self::to_array( $font );

				if ( ! self::is_safe_key( $font_id ) || $font === false ) {
					return false;
				}

				$font_object = new \stdClass();

				foreach ( $font as $prop => $value ) {
					if ( ! in_array( $prop, $font_props, true ) ) {
						if ( $strict ) {
							return false;
						}

						continue;
					}

					if ( $prop === 'variants' ) {
						$value = self::sanitize_variants( $value, $variant_props, $strict );
					} elseif ( $prop === 'subsets' ) {
						$value = self::sanitize_list( $value );
					} else {
						$value = self::sanitize_font_string( $value );
					}

					if ( $value === false ) {
						return false;
					}

					$font_object->$prop = $value;
				}

				$sanitized[ $handle ][ $font_id ] = $font_object;
			}
		}

		return $sanitized;
	}

	/**
	 * @param mixed $variants
	 * @param array $allowed_props
	 * @param bool  $strict
	 *
	 * @return array|false
	 */
	private static function sanitize_variants( $variants, $allowed_props, $strict ) {
		if ( ! is_array( $variants ) ) {
			return false;
		}

		$sanitized = [];

		foreach ( $variants as $key => $variant ) {
			$variant = self::to_array( $variant );

			if ( ! self::is_safe_key( $key ) || $variant === false ) {
				return false; // @codeCoverageIgnore
			}

			$variant_object = new \stdClass();

			foreach ( $variant as $prop => $value ) {
				if ( ! in_array( $prop, $allowed_props, true ) ) {
					if ( $strict ) {
						return false;
					}

					continue;
				}

				$value = self::sanitize_font_string( $value );

				// Font files must be referenced by a (protocol relative) URL or an absolute path.
				if ( $value !== false && in_array( $prop, [ 'woff2', 'woff' ], true ) && $value !== '' &&
					! preg_match( '#^(https?:)?//|^/(?!/)#i', $value ) ) {
					$value = false;
				}

				if ( $value === false ) {
					return false;
				}

				$variant_object->$prop = $value;
			}

			$sanitized[ $key ] = $variant_object;
		}

		return $sanitized;
	}

	/**
	 * @param mixed $list
	 *
	 * @return array|false
	 */
	private static function sanitize_list( $list ) {
		if ( ! is_array( $list ) ) {
			return false; // @codeCoverageIgnore
		}

		$sanitized = [];

		foreach ( $list as $item ) {
			$item = self::sanitize_font_string( $item );

			if ( $item === false ) {
				return false;
			}

			$sanitized[] = $item;
		}

		return $sanitized;
	}

	/**
	 * Font properties end up in generated stylesheets, so reject anything that could break out of a declaration.
	 *
	 * @param mixed $value
	 *
	 * @return string|false
	 */
	private static function sanitize_font_string( $value ) {
		if ( ! is_string( $value ) && ! is_int( $value ) && ! is_float( $value ) ) {
			return false;
		}

		$value = (string) $value;

		// Not sanitize_text_field(), because it strips the URL encoding font families are stored with.
		if ( preg_match( self::UNSAFE_CHARS_REGEX, $value ) || wp_check_invalid_utf8( $value ) !== $value ) {
			return false;
		}

		return trim( $value );
	}

	/**
	 * Same sanitization as the settings form. Booleans and null are stored by some (older) settings.
	 *
	 * @param mixed $value
	 *
	 * @return string|false
	 */
	private static function sanitize_scalar( $value ) {
		if ( $value === null || $value === false ) {
			return '';
		}

		if ( $value === true ) {
			return 'on';
		}

		if ( ! is_string( $value ) && ! is_int( $value ) && ! is_float( $value ) ) {
			return false;
		}

		return sanitize_text_field( (string) $value );
	}

	/**
	 * @param string|int $key
	 *
	 * @return bool
	 */
	private static function is_safe_key( $key ) {
		if ( is_int( $key ) ) {
			return true;
		}

		return is_string( $key ) && $key !== '' && strlen( $key ) <= 191 && ! preg_match( self::UNSAFE_CHARS_REGEX, $key );
	}

	/**
	 * Stored fonts are stdClass objects, decoded fonts are arrays.
	 *
	 * @param mixed $value
	 *
	 * @return array|false
	 */
	private static function to_array( $value ) {
		if ( $value instanceof \stdClass ) {
			return get_object_vars( $value );
		}

		return is_array( $value ) ? $value : false;
	}

	/**
	 * @param array $keys
	 *
	 * @return \WP_Error
	 */
	private static function unknown_keys_error( $keys ) {
		$keys = array_map(
			function ( $key ) {
				return '<code>' . esc_html( (string) $key ) . '</code>';
			},
			array_slice( $keys, 0, 10 )
		);

		return new \WP_Error(
			'omgf_import_unknown_keys',
			sprintf(
				/* translators: %s: comma separated list of setting names. */
				__( 'The uploaded file contains unknown settings: %s. Is the same version of OMGF (and its add-ons) active on this site?', 'host-webfonts-local' ),
				implode( ', ', $keys )
			)
		);
	}

	/**
	 * Send the export file to the browser.
	 *
	 * @action admin_post_omgf_export_settings
	 *
	 * @return void
	 */
	public function handle_export() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( "Hmmm, you're not supposed to be here.", 'host-webfonts-local' ), 403 );
		}

		check_admin_referer( self::ACTION_EXPORT );

		$include_fonts = ! empty( $_POST[ self::FIELD_INCLUDE_FONTS ] );
		$json          = self::export_json( $include_fonts );
		$filename      = sprintf( 'omgf-settings-%s-%s.json', sanitize_file_name( (string) wp_parse_url( get_site_url(), PHP_URL_HOST ) ), gmdate( 'Y-m-d' ) );

		if ( defined( 'DAAN_DOING_TESTS' ) ) {
			echo $json; // phpcs:ignore WordPress.Security.EscapeOutput

			return;
		}

		// @codeCoverageIgnoreStart
		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'X-Content-Type-Options: nosniff' );

		echo $json; // phpcs:ignore WordPress.Security.EscapeOutput

		exit;
		// @codeCoverageIgnoreEnd
	}

	/**
	 * Process the uploaded export file.
	 *
	 * @action admin_post_omgf_import_settings
	 *
	 * @return void
	 */
	public function handle_import() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( "Hmmm, you're not supposed to be here.", 'host-webfonts-local' ), 403 );
		}

		check_admin_referer( self::ACTION_IMPORT );

		// Tests can't create actual uploads.
		$result  = self::read_upload( $_FILES[ self::FIELD_FILE ] ?? null, ! defined( 'DAAN_DOING_TESTS' ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$skipped = [];

		if ( ! is_wp_error( $result ) ) {
			$result = self::parse( $result, $skipped );
		}

		if ( is_wp_error( $result ) ) {
			Notice::set_notice(
				__( 'Import failed.', 'host-webfonts-local' ) . ' ' . $result->get_error_message(),
				'omgf-import-export',
				'error'
			);
		} else {
			self::import( $result );

			Notice::set_notice(
				sprintf(
					/* translators: %s: URL to the Local Fonts tab. */
					__( 'Settings imported successfully. Go to the <a href="%s">Local Fonts</a> tab and click <strong>Save & Optimize</strong> to apply them.', 'host-webfonts-local' ),
					esc_url( admin_url( Settings::OMGF_OPTIONS_GENERAL_PAGE_OPTIMIZE_WEBFONTS ) )
				),
				'omgf-import-export'
			);

			$warning = self::get_skipped_message( $skipped );

			if ( $warning ) {
				Notice::set_notice( $warning, 'omgf-import-export-skipped', 'warning' );
			}
		}

		if ( defined( 'DAAN_DOING_TESTS' ) ) {
			return;
		}

		// @codeCoverageIgnoreStart
		wp_safe_redirect( admin_url( Settings::OMGF_OPTIONS_GENERAL_PAGE_OPTIMIZE_WEBFONTS . '&tab=' . Settings::OMGF_SETTINGS_FIELD_HELP ) );

		exit;
		// @codeCoverageIgnoreEnd
	}

	/**
	 * Read an uploaded file (an entry of $_FILES).
	 *
	 * @param mixed $file
	 * @param bool  $check_uploaded Only disabled in tests, which can't create actual uploads.
	 *
	 * @return string|\WP_Error
	 */
	public static function read_upload( $file, $check_uploaded = true ) {
		if ( ! is_array( $file ) || ! isset( $file['tmp_name'], $file['name'], $file['size'], $file['error'] ) || is_array( $file['tmp_name'] ) ) {
			return new \WP_Error( 'omgf_import_no_file', __( 'No file was uploaded.', 'host-webfonts-local' ) );
		}

		if ( (int) $file['error'] !== UPLOAD_ERR_OK ) {
			return new \WP_Error( 'omgf_import_upload_error', __( 'The file couldn\'t be uploaded.', 'host-webfonts-local' ) );
		}

		if ( (int) $file['size'] > self::MAX_FILE_SIZE ) {
			return new \WP_Error( 'omgf_import_too_large', __( 'The uploaded file is too large.', 'host-webfonts-local' ) );
		}

		if ( strtolower( pathinfo( (string) $file['name'], PATHINFO_EXTENSION ) ) !== 'json' ) {
			return new \WP_Error( 'omgf_import_invalid_json', __( 'The uploaded file isn\'t a valid JSON file.', 'host-webfonts-local' ) );
		}

		if ( $check_uploaded && ! is_uploaded_file( $file['tmp_name'] ) ) {
			return new \WP_Error( 'omgf_import_no_file', __( 'No file was uploaded.', 'host-webfonts-local' ) ); // @codeCoverageIgnore
		}

		$contents = file_get_contents( $file['tmp_name'], false, null, 0, self::MAX_FILE_SIZE + 1 ); // phpcs:ignore

		if ( $contents === false ) {
			return new \WP_Error( 'omgf_import_upload_error', __( 'The file couldn\'t be uploaded.', 'host-webfonts-local' ) ); // @codeCoverageIgnore
		}

		return $contents;
	}
}
