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

class Optimize {
	/**
	 * User Agent set to be used to make requests to the Google Fonts API in Compatibility Mode.
	 *
	 * @see   https://wordpress.org/support/topic/wrong-font-weight-only-in-firefox-2/
	 * @since v5.6.4 Using Win7 User-Agent to prevent rendering issues on older systems.
	 *               This results in 0,2KB larger WOFF2 files, but seems like a fair trade off.
	 * @since v5.9.0 Moved this User Agent to the new Legacy Mode option, because this user agent
	 *               no longer supports variable fonts.
	 */
	const USER_AGENT_COMPATIBILITY = [
		'woff2' => 'Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:109.0) Gecko/20100101 Firefox/115.0',
	];

	/**
	 * User Agent to be used to make requests to the Google Fonts API.
	 */
	const USER_AGENT = [
		'woff2' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:124.0) Gecko/20100101 Firefox/124.0',
	];

	/** @var string $url */
	private $url = '';

	/** @var string */
	private $handle = '';

	/** @var string $original_handle */
	private $original_handle = '';

	/** @var string $return */
	private $return = 'url';

	/** @var bool $return_early */
	private $return_early = false;

	/** @var string */
	private $path = '';

	/**
	 * @var array $variable_fonts An array of font families in the current stylesheets that're Variable Fonts.
	 * @since v5.3.0
	 */
	private $variable_fonts = [];

	/**
	 * @var array $available_used_subsets Contains an array_intersect() of subsets that're set to be used AND are actually available.
	 * @since v5.4.4
	 */
	private $available_used_subsets = [];

	/**
	 * @param string $url             Google Fonts API URL, e.g. "fonts.googleapis.com/css?family="Lato:100,200,300,etc."
	 * @param string $handle          The cache handle, generated using $handle + 5 random chars. Used for storing the fonts and stylesheet.
	 * @param string $original_handle The stylesheet handle, present in the ID attribute.
	 * @param string $return          Valid values: 'url' | 'path' | 'object'.
	 * @param bool   $return_early    If this is set to true, the optimization will skip out early if the object already exists in the database.
	 *
	 * @return void
	 */
	public function __construct(
		string $url,
		string $handle,
		string $original_handle,
		string $return = 'url',
		bool $return_early = false
	) {
		$this->url             = apply_filters( 'omgf_optimize_url', $url );
		$this->handle          = sanitize_title_with_dashes( $handle );
		$this->original_handle = sanitize_title_with_dashes( $original_handle );
		$this->path            = OMGF_UPLOAD_DIR . '/' . $this->handle;
		$this->return          = $return;
		$this->return_early    = $return_early;
	}

	/**
	 * @return string|array
	 */
	public function process() {
		if ( ! $this->handle || ! $this->original_handle ) {
			// @codeCoverageIgnoreStart
			Notice::set_notice(
				sprintf(
					__(
						'OMGF couldn\'t find required stylesheet handle parameter while attempting to talk to API. Values sent were <code>%1$s</code> and <code>%2$s</code>.',
						'host-webfonts-local'
					),
					$this->original_handle,
					$this->handle
				),
				'omgf-api-handle-not-found',
				'error',
				406
			);

			return '';
			// @codeCoverageIgnoreEnd
		}

		/**
		 * Convert protocol relative URLs.
		 */
		if ( str_starts_with( $this->url, '//' ) ) {
			$this->url = 'https:' . $this->url; // @codeCoverageIgnore
		}

		$local_file = $this->path . '/' . $this->handle . '.css';

		/**
		 * @since v3.6.0 Allows us to bail early if a fresh copy of files/stylesheets isn't necessary.
		 */
		if ( file_exists( $local_file ) && $this->return_early ) {
			// @codeCoverageIgnoreStart
			switch ( $this->return ) {
				case 'path':
					return $local_file;
				case 'object':
					$object = OMGF::optimized_fonts()[ $this->original_handle ] ?? (object) [];

					return [ $this->original_handle => $object ];
				default:
					// 'url'
					$timestamp = OMGF::get_option( Settings::OMGF_CACHE_TIMESTAMP );

					return str_replace( OMGF_UPLOAD_DIR, OMGF_UPLOAD_URL, $local_file ) . '?ver=' . $timestamp;
			}
			// @codeCoverageIgnoreEnd
		}

		/**
		 * @since v5.3.8 If any settings were changed, this will make sure the cache is no longer marked as stale.
		 */
		delete_option( Settings::OMGF_CACHE_IS_STALE );

		$stylesheet_bak = $this->fetch_stylesheet( $this->url );
		$fonts_bak      = $this->convert_to_fonts_object( $stylesheet_bak );
		$stylesheet     = $this->remove_unloaded_variants( $stylesheet_bak );
		$fonts          = $this->convert_to_fonts_object( $stylesheet );

		if ( empty( $fonts ) ) {
			return ''; // @codeCoverageIgnore
		}

		foreach ( $fonts as $id => &$font ) {
			/**
			 * Sanitize the font-family because it may contain spaces.
			 *
			 * @since v4.5.6
			 */
			$font->family = rawurlencode( $font->family );

			OMGF::debug( __( 'Processing downloads for', 'host-webfonts-local' ) . ' ' . $font->family . '...' );

			if ( empty( $font->variants ) ) {
				continue; // @codeCoverageIgnore
			}

			$filenames        = array_column( $font->variants, 'woff2' );
			$is_variable_font = false;

			if ( $filenames != array_unique( $filenames ) ) {
				$is_variable_font = true;
			}

			foreach ( $font->variants as $variant_id => &$variant ) {
				/**
				 * @since v5.3.0 Variable fonts use one filename for all font weights/styles. That's why we drop the weight from the filename.
				 */
				if ( $is_variable_font ) {
					$filename = strtolower(
						$id . '-' . $variant->fontStyle . '-' . ( isset( $variant->subset ) ? $variant->subset : '' )
					);
				} else {
					$filename = strtolower(
						$id . '-' . $variant->fontStyle . '-' . ( isset( $variant->subset ) ? $variant->subset . '-' : '' ) . str_replace( ' ', '-', $variant->fontWeight )
					);
				}

				/**
				 * Encode font family, because it may contain spaces.
				 *
				 * @since v4.5.6
				 */
				$variant->fontFamily = rawurlencode( $variant->fontFamily );

				if ( isset( $variant->woff2 ) ) {
					OMGF::debug(
						sprintf( __( 'Downloading %1$s to %2$s from %3$s.' ), $filename, $this->path, $variant->woff2 )
					);

					/**
					 * If file already exists the OMGF_Download class bails early.
					 */
					$variant->woff2 = OMGF::download( $variant->woff2, $filename, 'woff2', $this->path );
				}
			}

			OMGF::debug( __( 'Finished downloading for', 'host-webfonts-local' ) . ' ' . $font->family );
		}

		$stylesheet = OMGF::generate_stylesheet( $fonts );

		if ( ! file_exists( $this->path ) ) {
			wp_mkdir_p( $this->path ); // @codeCoverageIgnore
		}

		file_put_contents( $local_file, $stylesheet );

		/**
		 * @var object $fonts_bak is used to list the fonts in wp-admin (and for loading preloads in the frontend.)
		 * @var object $fonts     Same as $fonts_bak but without any unloaded fonts.
		 */
		$fonts_bak              = $this->rewrite_variants( $fonts_bak, $fonts );
		$current_stylesheet_bak = [ $this->original_handle => $fonts_bak ];
		$current_stylesheet     = [ $this->original_handle => $fonts ];

		/**
		 * $current_stylesheet is added to a temporary cache layer if it isn't present in the database.
		 *
		 * @since v4.5.7
		 */
		$optimized_fonts = OMGF::admin_optimized_fonts( $current_stylesheet_bak, true );

		OMGF::update_option( Settings::OMGF_OPTIMIZE_SETTING_OPTIMIZED_FONTS, $optimized_fonts, false );

		$optimized_fonts_frontend = OMGF::optimized_fonts( $current_stylesheet, true );

		OMGF::update_option( Settings::OMGF_OPTIMIZE_SETTING_OPTIMIZED_FONTS_FRONTEND, $optimized_fonts_frontend );

		/**
		 * @see   OMGF_Optimize_Run
		 * @since v5.4.4 Stores the subsets actually available in this configuration to the database.
		 */
		if ( ! empty( OMGF::get_option( Settings::OMGF_ADV_SETTING_SUBSETS ) ) ) {
			$available_used_subsets = OMGF::available_used_subsets( $this->available_used_subsets );

			OMGF::update_option( Settings::OMGF_AVAILABLE_USED_SUBSETS, $available_used_subsets );
		}

		switch ( $this->return ) {
			case 'path':
				return $local_file;
			case 'object':
				return $current_stylesheet;
			default: // 'url'
				return str_replace( OMGF_UPLOAD_DIR, OMGF_UPLOAD_URL, $local_file );
		}
	}

	/**
	 * Fetch Stylesheet.
	 *
	 * @param string $url Google Fonts API request, e.g. https://fonts.googleapis.com/css?family=Open+Sans:100,200,300,400italic
	 *
	 * @return string
	 */
	private function fetch_stylesheet( $url ) {
		OMGF::debug( __( 'Fetching stylesheet from: ', 'host-webfonts-local' ) . $url );

		/**
		 * @since v6.0.6 Fallback for already locally hosted stylesheets e.g., used by Elementor, etc.
		 *
		 * @codeCoverageIgnoreStart
		 */
		if ( str_contains( $url, get_home_url() ) ) {
			$path = str_replace( get_home_url(), ABSPATH, $url );

			if ( file_exists( $path ) ) {
				return file_get_contents( $path );
			}
		}
		/** @codeCoverageIgnoreEnd */

		$response = wp_remote_get(
			$url,
			[
				/**
				 * Allow WP devs to use a different User-Agent, e.g. for compatibility with older browsers/OSes.
				 *
				 * @filter omgf_optimize_user_agent
				 */ 'user-agent' => apply_filters( 'omgf_optimize_user_agent', self::USER_AGENT[ 'woff2' ] ),
			]
		);

		$code = wp_remote_retrieve_response_code( $response );

		if ( $code !== 200 ) {
			return ''; // @codeCoverageIgnore
		}

		return wp_remote_retrieve_body( $response );
	}

	/**
	 * @since v5.3.0 Parse the stylesheet and build it into a font object which OMGF can understand.
	 *
	 * @param string $stylesheet A valid CSS stylesheet.
	 *
	 * @return array
	 */
	private function convert_to_fonts_object( $stylesheet ) {
		OMGF::debug( __( 'Stylesheet fetched. Parsing for font-families...', 'host-webfonts-local' ) );

		preg_match_all( '/font-family:\s\'(.*?)\';/', $stylesheet, $font_families );

		if ( empty( $font_families[ 1 ] ) ) {
			return []; // @codeCoverageIgnore
		}

		$font_families = array_unique( $font_families[ 1 ] );
		$object        = [];

		OMGF::debug_array( __( 'Font-families found', 'host-webfonts-local' ), $font_families );

		foreach ( $font_families as $font_family ) {
			$id            = strtolower( str_replace( ' ', '-', $font_family ) );
			$object[ $id ] = (object) [
				'id'       => $id,
				'family'   => $font_family,
				'variants' => apply_filters(
					'omgf_optimize_fonts_object_variants',
					$this->parse_variants( $stylesheet, $font_family ),
					$stylesheet,
					$font_family,
					$this->url
				),
				'subsets'  => apply_filters(
					'omgf_optimize_fonts_object_subsets',
					$this->parse_subsets( $stylesheet, $font_family ),
					$stylesheet,
					$font_family,
					$this->url
				),
			];
		}

		OMGF::debug( __( 'Stylesheet successfully converted to object.', 'host-webfonts-local' ) );

		return apply_filters( 'omgf_optimize_fonts_object', $object, $stylesheet );
	}

	/**
	 * Parse a stylesheet from Google Fonts' API into a valid Font Object.
	 *
	 * @param string $stylesheet
	 * @param string $font_family
	 *
	 * @return array
	 */
	private function parse_variants( $stylesheet, $font_family ) {
		OMGF::debug( __( 'Parsing variants.', 'host-webfonts-local' ) );

		/**
		 * This also captures the commented Subset name.
		 */
		preg_match_all(
			apply_filters( 'omgf_optimize_parse_variants_regex', '/\/\*\s.*?}/s', $this->url ),
			$stylesheet,
			$font_faces
		);

		if ( empty( $font_faces[ 0 ] ) ) {
			return []; // @codeCoverageIgnore
		}

		OMGF::debug(
			sprintf( __( 'Found %s @font-face statements.', 'host-webfonts-local' ), count( $font_faces[ 0 ] ) )
		);

		$font_object = [];

		foreach ( $font_faces[ 0 ] as $font_face ) {
			/**
			 * @since v5.3.3 Exact match for font-family attribute, to prevent similar font names from falling through, e.g., Roboto and Roboto Slab.
			 */
			if ( ! preg_match( '/font-family:[\s\'"]*?' . $font_family . '[\'"]?;/', $font_face ) ) {
				continue; // @codeCoverageIgnore
			}

			preg_match( '/font-style:\s(normal|italic);/', $font_face, $font_style );
			preg_match( '/font-weight:\s([0-9\s]+);/', $font_face, $font_weight );
			preg_match( '/src\s*:\s*[^;]*?url\(\s*[\'"]?([^\'")]+\.woff2)[\'"]?\s*\)/', $font_face, $font_src );
			preg_match( '/\/\*\s([a-z\-0-9\[\]]+?)\s\*\//', $font_face, $subset );
			preg_match( '/unicode-range:\s(.*?);/', $font_face, $range );

			// If no woff2 file is found in the src attribute, check if it defines a woff file.
			if ( empty( $font_src[ 1 ] ) ) {
				preg_match( '/src\s*:\s*[^;]*?url\(\s*[\'"]?([^\'")]+\.woff)[\'"]?\s*\)/', $font_face, $font_src ); // @codeCoverageIgnore
			}

			$font_style  = ! empty( $font_style[ 1 ] ) ? $font_style[ 1 ] : 'normal';
			$font_weight = ! empty( $font_weight[ 1 ] ) ? $font_weight [ 1 ] : '400';
			$font_src    = ! empty( $font_src[ 1 ] ) ? $font_src[ 1 ] : '';
			$subset      = ! empty( $subset[ 1 ] ) ? trim( $subset[ 1 ], '[]' ) : '';

			/**
			 * @since v5.3.0 No need to keep this if this variant belongs to a subset we don't need.
			 */
			if ( ! empty( $subset ) && ! in_array( $subset, OMGF::get_option( settings::OMGF_ADV_SETTING_SUBSETS ) ) && ! is_numeric( $subset ) ) {
				continue;
			}

			/**
			 * If $subset is empty, assume it's a logographic (Chinese, Japanese, etc.) character set.
			 */
			if ( is_numeric( $subset ) ) {
				$subset = 'logogram-' . $subset;
			}

			$font_weight_id                  = str_replace( ' ', '-', $font_weight );
			$key                             = $subset . '-' . $font_weight_id . ( $font_style === 'normal' ? '' : '-' . $font_style );
			$font_object[ $key ]             = new \stdClass();
			$font_object[ $key ]->id         = $font_weight_id . ( $font_style === 'normal' ? '' : $font_style );
			$font_object[ $key ]->fontFamily = $font_family;
			$font_object[ $key ]->fontStyle  = $font_style;
			$font_object[ $key ]->fontWeight = $font_weight;
			$font_object[ $key ]->woff2      = $font_src;

			if ( ! empty( $subset ) ) {
				$font_object[ $key ]->subset = $subset;
			}

			if ( ! empty( $range ) && isset( $range[ 1 ] ) ) {
				$font_object[ $key ]->range = $range[ 1 ];
			}

			$id = strtolower( str_replace( ' ', '-', $font_family ) );

			/**
			 * @since v5.3.0 Is this a variable font i.e., one font file for multiple font weights/styles?
			 */
			if ( $font_src && substr_count( $stylesheet, $font_src ) > 1 && ! in_array( $id, $this->variable_fonts ) ) {
				$this->variable_fonts[ $id ] = $id;

				OMGF::debug(
					__(
						'Same file used for multiple @font-face statements. This is a variable font: ',
						'host-webfonts-local'
					) . $font_family
				);
			}
		}

		OMGF::debug_array( __( 'Generated @font-face objects', 'host-webfonts-local' ), $font_object );

		OMGF::debug( __( 'All @font-face statements processed.', 'host-webfonts-local' ) );

		return $font_object;
	}

	/**
	 * Parse stylesheets for subsets, which in Google Fonts stylesheets are always
	 * included, commented above each @font-face statements, e.g. /* latin-ext
	 */
	private function parse_subsets( $stylesheet, $font_family ) {
		OMGF::debug( __( 'Parsing subsets.', 'host-webfonts-local' ) );

		preg_match_all( '/\/\*\s([a-z\-]+?)\s\*\//', $stylesheet, $subsets );

		if ( empty( $subsets[ 1 ] ) ) {
			return []; // @codeCoverageIgnore
		}

		$subsets = array_unique( $subsets[ 1 ] );

		/**
		 * @since v5.4.4 Stores all subsets that are selected to be used AND are actually available in this font-family.
		 */
		$this->available_used_subsets[ $font_family ] = array_intersect(
			$subsets,
			OMGF::get_option( Settings::OMGF_ADV_SETTING_SUBSETS )
		);

		OMGF::debug_array( __( 'Subset @font-face statements', 'host-webfonts-local' ), $subsets );

		return $subsets;
	}

	/**
	 * Removes @font-face statements from $stylesheet if they are set to be unloaded.
	 *
	 * @param string $stylesheet
	 *
	 * @return string
	 */
	private function remove_unloaded_variants( $stylesheet ) {
		if ( ! isset( OMGF::unloaded_fonts()[ $this->original_handle ] ) ) {
			return $stylesheet;
		}

		// Extract all @font-face blocks with their comments
		preg_match_all( '#/\*[^*]*\*+(?:[^/*][^*]*\*+)*/\s*@font-face\s*\{[^}]*}#', $stylesheet, $font_face_matches );

		if ( empty( $font_face_matches[ 0 ] ) ) {
			return $stylesheet;
		}

		$font_faces_to_keep = [];
		$unloaded_fonts     = OMGF::unloaded_fonts()[ $this->original_handle ];

		foreach ( $font_face_matches[ 0 ] as $font_face_block ) {
			// Extract font-family from the @font-face block
			if ( preg_match( '/font-family:\s*[\'"]?([^\'";]+)[\'"]?\s*;/', $font_face_block, $font_family ) ) {
				if ( empty( $font_family[ 1 ] ) ) {
					continue;
				}

				$font_family = trim( $font_family[ 1 ] );
				$font_id     = strtolower( str_replace( ' ', '-', $font_family ) );

				// Are any unloaded variants set for this $font_id?
				if ( ! isset( $unloaded_fonts[ $font_id ] ) || empty( array_filter( $unloaded_fonts[ $font_id ] ) ) ) {
					$font_faces_to_keep[] = $font_face_block;

					continue;
				}

				// Extract font-weight and font-style from the @font-face block
				preg_match( '/font-weight:\s*([^;]+);/', $font_face_block, $font_weight );
				preg_match( '/font-style:\s*([^;]+);/', $font_face_block, $font_style );

				if ( empty( $font_weight[ 1 ] ) ) {
					continue;
				}

				$font_weight = trim( $font_weight[ 1 ] );
				$font_style  = isset( $font_style[ 1 ] ) ? trim( $font_style[ 1 ] ) : '';

				if ( $font_style === 'normal' ) {
					$font_style = '';
				}

				$variant_key   = $font_weight . $font_style;
				$should_remove = in_array( $variant_key, $unloaded_fonts[ $font_id ] );

				if ( ! $should_remove ) {
					$font_faces_to_keep[] = $font_face_block;
				}
			}
		}

		// Reconstruct the stylesheet
		$result = '';

		if ( ! empty( $font_faces_to_keep ) ) {
			$result .= implode( "\n", $font_faces_to_keep );
		}

		return trim( $result );
	}

	/**
	 * When unload is used, insert the cache key in the font URLs for the variants still in use.
	 *
	 * @param object $current     Contains all font styles, loaded and unloaded.
	 * @param object $replacement Contains just the loaded font styles of current stylesheet.
	 *
	 * Both parameters follow this structure:
	 * @formatter:off
	 * (string) Font Family {
	 *      (string) id, (string) family, (array) variants {
	 *          (string) id => (object) {
	 *              (string) id, (string) fontFamily, (string) fontStyle, (string) fontWeight, (string) woff2, (string) subset = null, (string) range
	 *          }
	 *      }
	 * }
	 * @formatter:on
	 *
	 * @return object
	 */
	private function rewrite_variants( $current, $replacement ) {
		OMGF::debug( __( 'Rewriting URLs for each font variant...', 'host-webfonts-local' ) );

		OMGF::debug_array( 'Current Fonts Set', $current );
		OMGF::debug_array( 'Replacement Fonts Set', $replacement );

		foreach ( $current as $font_family => &$properties ) {
			if ( empty( $properties->variants ) ) {
				continue; // @codeCoverageIgnore
			}

			foreach ( $properties->variants as $id => &$variant ) {
				$replacement_variant = $replacement[ $font_family ]->variants[ $id ] ?? '';

				if ( $replacement_variant && $replacement_variant != $variant ) {
					$variant = $replacement_variant;
				}
			}
		}

		return $current;
	}
}
