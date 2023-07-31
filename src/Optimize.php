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

defined( 'ABSPATH' ) || exit;

class Optimize {
	/**
	 * User Agents set to be used to make requests to the Google Fonts API.
	 *
	 * @since v5.6.4 Using Win7 User-Agent to prevent rendering issues on older systems.
	 *               This results in 0,2KB larger WOFF2 files, but seems like a fair trade off.
	 *               @see https://wordpress.org/support/topic/wrong-font-weight-only-in-firefox-2/
	 */
	const USER_AGENT = [
		'woff2' => 'Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:109.0) Gecko/20100101 Firefox/115.0',
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
	 *
	 * @since v5.3.0
	 */
	private $variable_fonts = [];

	/**
	 * @var array $available_used_subsets Contains an array_intersect() of subsets that're set to be used AND are actually available.
	 *
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
			Notice::set_notice( sprintf( __( 'OMGF couldn\'t find required stylesheet handle parameter while attempting to talk to API. Values sent were <code>%1$s</code> and <code>%2$s</code>.', 'host-webfonts-local' ), $this->original_handle, $this->handle ), 'omgf-api-handle-not-found', 'error', 406 );

			return '';
		}

		/**
		 * Convert protocol relative URLs.
		 */
		if ( strpos( $this->url, '//' ) === 0 ) {
			$this->url = 'https:' . $this->url;
		}

		$local_file = $this->path . '/' . $this->handle . '.css';

		/**
		 * @since v3.6.0 Allows us to bail early, if a fresh copy of files/stylesheets isn't necessary.
		 */
		if ( file_exists( $local_file ) && $this->return_early ) {
			switch ( $this->return ) {
				case 'path':
					return $local_file;
				case 'object':
					return [ $this->original_handle => OMGF::optimized_fonts()[ $this->original_handle ] ];
				default:
					return str_replace( OMGF_UPLOAD_DIR, OMGF_UPLOAD_URL, $local_file );
			}
		}

		/**
		 * @since v5.3.8 If any settings were changed, this will make sure the cache is no longer marked as stale.
		 */
		delete_option( Settings::OMGF_CACHE_IS_STALE );

		$fonts_bak = $this->grab_fonts_object( $this->url );
		$url       = $this->unload_variants( $this->url );
		$fonts     = $this->grab_fonts_object( $url );

		if ( empty( $fonts ) ) {
			return '';
		}

		foreach ( $fonts as $id => &$font ) {
			/**
			 * Sanitize font family, because it may contain spaces.
			 *
			 * @since v4.5.6
			 */
			$font->family = rawurlencode( $font->family );

			OMGF::debug( __( 'Processing downloads for', 'host-webfonts-local' ) . ' ' . $font->family . '...' );

			if ( ! isset( $font->variants ) || empty( $font->variants ) ) {
				continue;
			}

			foreach ( $font->variants as $variant_id => &$variant ) {
				/**
				 * @since v5.3.0 Variable fonts use one filename for all font weights/styles. That's why we drop the weight from the filename.
				 */
				if ( isset( $this->variable_fonts[ $id ] ) ) {
					$filename = strtolower( $id . '-' . $variant->fontStyle . '-' . ( isset( $variant->subset ) ? $variant->subset : '' ) );
				} else {
					$filename = strtolower( $id . '-' . $variant->fontStyle . '-' . ( isset( $variant->subset ) ? $variant->subset . '-' : '' ) . $variant->fontWeight );
				}

				/**
				 * Encode font family, because it may contain spaces.
				 *
				 * @since v4.5.6
				 */
				$variant->fontFamily = rawurlencode( $variant->fontFamily );

				if ( isset( $variant->woff2 ) ) {
					OMGF::debug( sprintf( __( 'Downloading %1$s to %2$s from %3$s.' ), $filename, $this->path, $variant->woff2 ) );

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
			wp_mkdir_p( $this->path );
		}

		file_put_contents( $local_file, $stylesheet );

		$fonts_bak          = $this->rewrite_variants( $fonts_bak, $fonts );
		$current_stylesheet = [ $this->original_handle => $fonts_bak ];

		/**
		 * $current_stylesheet is added to temporary cache layer, if it isn't present in database.
		 *
		 * @since v4.5.7
		 */
		$optimized_fonts = OMGF::optimized_fonts( $current_stylesheet, true );

		OMGF::update_option( Settings::OMGF_OPTIMIZE_SETTING_OPTIMIZED_FONTS, $optimized_fonts );

		/**
		 * @since v5.4.4 Stores the subsets actually available in this configuration to the database.
		 *
		 * @see OMGF_Optimize_Run
		 */
		if ( ! empty( OMGF::get_option( Settings::OMGF_ADV_SETTING_SUBSETS ) ) ) {
			$available_used_subsets = OMGF::available_used_subsets( $this->available_used_subsets );

			OMGF::update_option( Settings::OMGF_AVAILABLE_USED_SUBSETS, $available_used_subsets );
		}

		switch ( $this->return ) {
			case 'path':
				return $local_file;
				break;
			case 'object':
				return $current_stylesheet;
				break;
			default: // 'url'
				return str_replace( OMGF_UPLOAD_DIR, OMGF_UPLOAD_URL, $local_file );
		}
	}

	/**
	 * @since v5.3.0 Parse the stylesheet and build it into a font object which OMGF can understand.
	 *
	 * @param $url Google Fonts API request, e.g. https://fonts.googleapis.com/css?family=Open+Sans:100,200,300,400italic
	 *
	 * @return array
	 */
	private function grab_fonts_object( $url ) {
		OMGF::debug( __( 'Fetching stylesheet from: ', 'host-webfonts-local' ) . $url );

		$response = wp_remote_get(
			$url,
			[
				/**
				 * Allow WP devs to use a different User-Agent, e.g. for compatibility with older browsers/OSes.
				 *
				 * @filter omgf_optimize_user_agent
				 */
				'user-agent' => apply_filters( 'omgf_optimize_user_agent', self::USER_AGENT['woff2'] ),
			]
		);

		$code = wp_remote_retrieve_response_code( $response );

		if ( $code !== 200 ) {
			return [];
		}

		$stylesheet = wp_remote_retrieve_body( $response );

		OMGF::debug( __( 'Stylesheet fetched. Parsing for font-families...', 'host-webfonts-local' ) );

		preg_match_all( '/font-family:\s\'(.*?)\';/', $stylesheet, $font_families );

		if ( ! isset( $font_families[1] ) || empty( $font_families[1] ) ) {
			return [];
		}

		$font_families = array_unique( $font_families[1] );
		$object        = [];

		OMGF::debug_array( __( 'Font-families found', 'host-webfonts-local' ), $font_families );

		foreach ( $font_families as $font_family ) {
			$id            = strtolower( str_replace( ' ', '-', $font_family ) );
			$object[ $id ] = (object) [
				'id'       => $id,
				'family'   => $font_family,
				'variants' => apply_filters( 'omgf_optimize_fonts_object_variants', $this->parse_variants( $stylesheet, $font_family ), $stylesheet, $font_family, $this->url ),
				'subsets'  => apply_filters( 'omgf_optimize_fonts_object_subsets', $this->parse_subsets( $stylesheet, $font_family ), $stylesheet, $font_family, $this->url ),
			];
		}

		OMGF::debug( __( 'Stylesheet successfully converted to object.', 'host-webfonts-local' ) );

		return apply_filters( 'omgf_optimize_fonts_object', $object, $url );
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
		preg_match_all( apply_filters( 'omgf_optimize_parse_variants_regex', '/\/\*\s.*?}/s', $this->url ), $stylesheet, $font_faces );

		if ( ! isset( $font_faces[0] ) || empty( $font_faces[0] ) ) {
			return [];
		}

		OMGF::debug( sprintf( __( 'Found %s @font-face statements.', 'host-webfonts-local' ), count( $font_faces[0] ) ) );

		$font_object = [];

		foreach ( $font_faces[0] as $key => $font_face ) {
			/**
			 * @since v5.3.3 Exact match for font-family attribute, to prevent similar font names from falling thru, e.g. Roboto and Roboto Slab.
			 */
			if ( ! preg_match( '/font-family:[\s\'"]*?' . $font_family . '[\'"]?;/', $font_face ) ) {
				continue;
			}

			preg_match( '/font-style:\s(normal|italic);/', $font_face, $font_style );
			preg_match( '/font-weight:\s([0-9]+);/', $font_face, $font_weight );
			preg_match( '/src:\surl\((.*?woff2)\)/', $font_face, $font_src );
			preg_match( '/\/\*\s([a-z\-0-9\[\]]+?)\s\*\//', $font_face, $subset );
			preg_match( '/unicode-range:\s(.*?);/', $font_face, $range );

			$subset[1] = trim( $subset[1], '[]' );

			/**
			 * @since v5.3.0 No need to keep this if this variant belongs to a subset we don't need.
			 */
			if ( ! empty( $subset ) && isset( $subset[1] ) && ! in_array( $subset[1], OMGF::get_option( settings::OMGF_ADV_SETTING_SUBSETS ) ) && ! is_numeric( $subset[1] ) ) {
				continue;
			}

			/**
			 * If $subset is empty, assume it's a logographic (Chinese, Japanese, etc.) character set.
			 *
			 * TODO: [OMGF-87] the Used Subsets option doesn't work here. Can we make it work?
			 */
			if ( is_numeric( $subset[1] ) ) {
				$subset[1] = 'logogram-' . $subset[1];
			}

			$key                             = $subset[1] . '-' . $font_weight[1] . ( $font_style[1] === 'normal' ? '' : '-' . $font_style[1] );
			$font_object[ $key ]             = new \stdClass();
			$font_object[ $key ]->id         = $font_weight[1] . ( $font_style[1] === 'normal' ? '' : $font_style[1] );
			$font_object[ $key ]->fontFamily = $font_family;
			$font_object[ $key ]->fontStyle  = $font_style[1];
			$font_object[ $key ]->fontWeight = $font_weight[1];
			$font_object[ $key ]->woff2      = $font_src[1];

			if ( ! empty( $subset ) && isset( $subset[1] ) ) {
				$font_object[ $key ]->subset = $subset[1];
			}

			if ( ! empty( $range ) && isset( $range[1] ) ) {
				$font_object[ $key ]->range = $range[1];
			}

			$id = strtolower( str_replace( ' ', '-', $font_family ) );

			/**
			 * @since v5.3.0 Is this a variable font i.e. one font file for multiple font weights/styles?
			 */
			if ( substr_count( $stylesheet, $font_src[1] ) > 1 && ! in_array( $id, $this->variable_fonts ) ) {
				$this->variable_fonts[ $id ] = $id;

				OMGF::debug( __( 'Same file used for multiple @font-face statements. This is a variable font: ', 'host-webfonts-local' ) . $font_family );
			}
		}

		OMGF::debug_array( __( 'Generated @font-face objects', 'host-webfonts-local' ), $font_object );

		OMGF::debug( __( 'All @font-face statements processed.', 'host-webfonts-local' ) );

		return $font_object;
	}

	/**
	 * Parse stylesheets for subsets, which in Google Fonts stylesheets are always
	 * included, commented above each @font-face statements, e.g. /* latin-ext */ /*
	 */
	private function parse_subsets( $stylesheet, $font_family ) {
		OMGF::debug( __( 'Parsing subsets.', 'host-webfonts-local' ) );

		preg_match_all( '/\/\*\s([a-z\-]+?)\s\*\//', $stylesheet, $subsets );

		if ( ! isset( $subsets[1] ) || empty( $subsets[1] ) ) {
			return [];
		}

		$subsets = array_unique( $subsets[1] );

		/**
		 * @since v5.4.4 Stores all subsets that are selected to be used AND are actually available in this font-family.
		 */
		$this->available_used_subsets[ $font_family ] = array_intersect( $subsets, OMGF::get_option( Settings::OMGF_ADV_SETTING_SUBSETS ) );

		OMGF::debug_array( __( 'Subset @font-face statements', 'host-webfonts-local' ), $subsets );

		return $subsets;
	}

	/**
	 * Modifies the URL to not include unloaded variants.
	 *
	 * @param mixed $url
	 * @return void
	 */
	private function unload_variants( $url ) {
		if ( ! isset( OMGF::unloaded_fonts()[ $this->original_handle ] ) ) {
			return $url;
		}

		$url = urldecode( $url );

		OMGF::debug( __( 'Looking for unloads for: ', 'host-webfonts-local' ) . $url );

		if ( strpos( $url, '/css2' ) !== false ) {
			$url = $this->unload_css2( $url );
		} else {
			$url = $this->unload_css( $url );
		}

		return apply_filters( 'omgf_optimize_unload_variants_url', $url );
	}

	/**
	 * Process unload for Variable Fonts API requests.
	 *
	 * @param string $url full request to Variable Fonts API.
	 *
	 * @return string full requests (excluding unloaded variants)
	 */
	private function unload_css2( $url ) {
		$query = wp_parse_url( $url, PHP_URL_QUERY );

		foreach ( $font_families = explode( '&', $query ) as $key => $family ) {
			preg_match( '/family=(?<name>[A-Za-z\s]+)[\:]?/', $family, $name );
			preg_match( '/:(?P<axes>[a-z,]+)@/', $family, $axes );
			preg_match( '/@(?P<tuples>[0-9,;]+)[&]?/', $family, $tuples );

			if ( ! isset( $name['name'] ) || empty( $name['name'] ) ) {
				continue;
			}

			$name = $name['name'];
			$id   = str_replace( ' ', '-', strtolower( $name ) );

			if ( ! isset( OMGF::unloaded_fonts()[ $this->original_handle ][ $id ] ) ) {
				continue;
			}

			if ( ! isset( $axes['axes'] ) || empty( $axes['axes'] ) ) {
				$axes = 'wght';
			} else {
				$axes = $axes['axes'];
			}

			if ( ! isset( $tuples['tuples'] ) || empty( $tuples['tuples'] ) ) {
				/**
				 * Variable Fonts API returns only regular (normal, 400) if no variations are defined.
				 */
				$tuples = [ '400' ];
			} else {
				$tuples = explode( ';', $tuples['tuples'] );
			}

			$unloaded_fonts = OMGF::unloaded_fonts()[ $this->original_handle ][ $id ];
			$tuples         = array_filter(
				$tuples,
				function ( $tuple ) use ( $unloaded_fonts ) {
					return ! in_array( preg_replace( '/[0-9]+,/', '', $tuple ), $unloaded_fonts );
				}
			);

			/**
			 * The entire font-family appears to be unloaded, let's remove it.
			 */
			if ( empty( $tuples ) ) {
				unset( $font_families[ $key ] );

				continue;
			}

			$font_families[ $key ] = 'family=' . $name . ':' . $axes . '@' . implode( ';', $tuples );
		}

		return 'https://fonts.googleapis.com/css2?' . implode( '&', $font_families );
	}

	/**
	 * Process unload for Google Fonts API.
	 *
	 * @param string $url Full request to Google Fonts API.
	 *
	 * @return string     Full request (excluding unloaded variants)
	 */
	private function unload_css( $url ) {
		$query = wp_parse_url( $url, PHP_URL_QUERY );

		parse_str( $query, $font_families );

		foreach ( $font_families = explode( '|', $font_families['family'] ) as $key => $font_family ) {
			list($name, $tuples) = array_pad( explode( ':', $font_family ), 2, [] );

			$id = str_replace( ' ', '-', strtolower( $name ) );

			if ( ! isset( OMGF::unloaded_fonts()[ $this->original_handle ][ $id ] ) ) {
				continue;
			}

			/**
			 * Google Fonts API returns 400 if no tuples are defined.
			 */
			if ( empty( $tuples ) ) {
				$tuples = [ '400' ];
			} else {
				$tuples = explode( ',', $tuples );
			}

			$unloaded_fonts = OMGF::unloaded_fonts()[ $this->original_handle ][ $id ];
			$tuples         = array_filter(
				$tuples,
				function ( $tuple ) use ( $unloaded_fonts ) {
					return ! in_array( $tuple, $unloaded_fonts );
				}
			);

			/**
			 * The entire font-family appears to be unloaded, let's remove it.
			 */
			if ( empty( $tuples ) ) {
				unset( $font_families[ $key ] );

				continue;
			}

			$font_families[ $key ] = urlencode( $name ) . ':' . implode( ',', $tuples );
		}

		return 'https://fonts.googleapis.com/css?family=' . implode( '|', $font_families );
	}

	/**
	 * When unload is used, insert the cache key in the font URLs for the variants still in use.
	 *
	 * @param array $current     Contains all font styles, loaded and unloaded.
	 * @param array $replacement Contains just the loaded font styles of current stylesheet.
	 *
	 *                           Both parameters follow this structure:
	 *
	 *                           (string) Font Family {
	 *                               (string) id, (string) family, (array) variants {
	 *                                   (string) id => (object) {
	 *                                       (string) id, (string) fontFamily, (string) fontStyle, (string) fontWeight, (string) woff2, (string) subset = null, (string) range
	 *                                   }
	 *                               }
	 *                           }
	 *
	 * @return array
	 */
	private function rewrite_variants( $current, $replacement ) {
		OMGF::debug( __( 'Rewriting URLs for each font variant...', 'host-webfonts-local' ) );

		OMGF::debug_array( 'Current Fonts Set', $current );
		OMGF::debug_array( 'Replacement Fonts Set', $replacement );

		foreach ( $current as $font_family => &$properties ) {
			if ( ! isset( $properties->variants ) || empty( $properties->variants ) ) {
				continue;
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
