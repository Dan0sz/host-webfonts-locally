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

class OMGF_API_Download extends WP_REST_Controller
{
	const OMGF_GOOGLE_FONTS_API_URL = 'https://google-webfonts-helper.herokuapp.com';
	
	private $plugin_text_domain = 'host-webfonts-local';
	
	/** @var array $endpoints */
	private $endpoints = [ 'css', 'css2' ];
	
	/** @var string $namespace */
	protected $namespace = 'omgf/v1';
	
	/** @var string $rest_base */
	protected $rest_base = '/download/';
	
	/** @var string $handle */
	private $handle = '';
	
	/** @var string $path */
	private $path = '';
	
	/**
	 *
	 */
	public function register_routes () {
		foreach ( $this->endpoints as $endpoint ) {
			register_rest_route(
				$this->namespace,
				'/' . $this->rest_base . $endpoint,
				[
					[
						'methods'             => 'GET',
						'callback'            => [ $this, 'process' ],
						'permission_callback' => [ $this, 'permissions_check' ]
					],
					'schema' => null,
				]
			);
		}
	}
	
	/**
	 * @return bool
	 */
	public function permissions_check () {
		return true;
	}
	
	/**
	 * @param $request WP_Rest_Request
	 */
	public function process ( $request ) {
		if ( strpos( $request->get_route(), 'css2' ) !== false ) {
			$this->convert_css2( $request );
		}
		
		$params          = $request->get_params();
		$this->handle    = $params['handle'] ?? '';
		$original_handle = $request->get_param( 'original_handle' );
		
		if ( ! $this->handle || ! $original_handle ) {
			wp_send_json_error( 'Handle not provided.', 406 );
		}
		
		$this->path    = WP_CONTENT_DIR . OMGF_CACHE_PATH . '/' . $this->handle;
		$url           = self::OMGF_GOOGLE_FONTS_API_URL . '/api/fonts/%s';
		$font_families = explode( '|', $params['family'] );
		
		if ( defined( 'OMGF_PRO_FORCE_SUBSETS' ) && ! empty( OMGF_PRO_FORCE_SUBSETS ) ) {
			$query['subsets'] = implode( ',', OMGF_PRO_FORCE_SUBSETS );
		} else {
			$query['subsets'] = $params['subset'] ?? 'latin,latin-ext';
		}
		
		$fonts = [];
		
		foreach ( $font_families as $font_family ) {
			$fonts[] = $this->grab_font_family( $font_family, $url, $query );
		}
		
		// Filter out empty element, i.e. failed requests.
		$fonts = array_filter( $fonts );
		
		foreach ( $fonts as $font_key => &$font ) {
			$font_request = $this->filter_font_families( $font_families, $font );
			
			list ( $family, $variants ) = explode( ':', $font_request );
			
			$variants = $this->process_variants( $variants, $font );
			
			if ( $unloaded_fonts = omgf_init()::unloaded_fonts() ) {
				$font_id = $font->id;
				
				// Now we're sure we got 'em all. We can safely unload those we don't want.
				if ( isset( $unloaded_fonts[ $original_handle ][ $font_id ] ) ) {
					$variants     = $this->dequeue_unloaded_fonts( $variants, $unloaded_fonts[ $original_handle ], $font->id );
					$font_request = $family . ':' . implode( ',', $variants );
				}
			}
			
			// TODO: Filtered variants are no longer displayed in settings. How to resolve?
			$font->variants = $this->filter_variants( $font->variants, $font_request );
		}
		
		foreach ( $fonts as &$font ) {
			foreach ( $font->variants as &$variant ) {
				$font_family    = trim( $variant->fontFamily, '\'"' );
				$filename       = strtolower( str_replace( ' ', '-', $font_family ) . '-' . $variant->fontStyle . '-' . $variant->fontWeight );
				$variant->woff  = $this->download( $variant->woff, $filename );
				$variant->woff2 = $this->download( $variant->woff2, $filename );
				$variant->eot   = $this->download( $variant->eot, $filename );
				$variant->ttf   = $this->download( $variant->ttf, $filename );
			}
		}
		
		$stylesheet = $this->generate_stylesheet( $fonts );
		$local_file = $this->path . '/' . $this->handle . '.css';
		
		file_put_contents( $local_file, $stylesheet );
		
		$optimized_fonts = omgf_init()::optimized_fonts();
		$current_font    = [ $original_handle => $fonts ];
		
		update_option( OMGF_Admin_Settings::OMGF_OPTIMIZE_SETTING_OPTIMIZED_FONTS, array_replace_recursive( $optimized_fonts, $current_font ) );
		
		// After downloading it, serve it.
		header( 'Content-Type: text/css' );
		header( "Content-Transfer-Encoding: Binary" );
		header( 'Content-Length: ' . filesize( $local_file ) );
		flush();
		readfile( $local_file );
		die();
	}
	
	/**
	 * @param $variants
	 * @param $unloaded_fonts
	 * @param $font_id
	 *
	 * @return array
	 */
	private function dequeue_unloaded_fonts ( $variants, $unloaded_fonts, $font_id ) {
		return array_filter(
			$variants,
			function ( $value ) use ( $unloaded_fonts, $font_id ) {
				if ( $value == '400' ) {
					// Sometimes the font is defined as 'regular', so we need to check both.
					return ! in_array( 'regular', $unloaded_fonts [ $font_id ] ) && ! in_array( $value, $unloaded_fonts [ $font_id ] );
				}
				
				return ! in_array( $value, $unloaded_fonts[ $font_id ] );
			}
		);
	}
	
	/**
	 * Converts requests to OMGF's Download/CSS2 API to a format readable by the regular API.
	 *
	 * @param $request WP_Rest_Request
	 */
	private function convert_css2 ( &$request ) {
		$query         = $this->get_query_from_request();
		$params        = explode( '&', $query );
		$font_families = [];
		$fonts         = [];
		
		foreach ( $params as $param ) {
			if ( strpos( $param, 'family' ) === false ) {
				continue;
			}
			
			parse_str( $param, $parts );
			
			$font_families[] = $parts;
		}
		
		foreach ( $font_families as $font_family ) {
			list( $family, $weights ) = explode( ':', reset( $font_family ) );
			
			/**
			 * @return array [ '300', '400', '500', etc. ]
			 */
			$weights = explode( ';', substr( $weights, strpos( $weights, '@' ) + 1 ) );
			
			$fonts[] = $family . ':' . implode( ',', $weights );
		}
		
		$request->set_param( 'family', implode( '|', $fonts ) );
	}
	
	/**
	 * Since Google Fonts' variable fonts API uses the same name for each parameter ('family') we need to parse the url manually.
	 *
	 * @return mixed
	 */
	private function get_query_from_request () {
		return parse_url( $_SERVER['REQUEST_URI'] )['query'];
	}
	
	/**
	 * @param $font_family
	 * @param $url
	 * @param $query
	 *
	 * @return mixed|void
	 */
	private function grab_font_family ( $font_family, $url, $query ) {
		list( $family, $variants ) = explode( ':', $font_family );
		$family = strtolower( str_replace( ' ', '-', $family ) );
		
		$response = wp_remote_get(
			sprintf( $url, $family ) . '?' . http_build_query( $query )
		);
		
		$response_code = $response['response']['code'] ?? '';
		
		if ( $response_code !== 200 ) {
			$message = sprintf( __( '<strong>%s</strong> could not be found using the current configuration. The API returned the following error: %s', $this->plugin_text_domain ), ucfirst( $family ), wp_remote_retrieve_body( $response ) );
			
			OMGF_Admin_Notice::set_notice(
				$message,
				'omgf_api_error',
				false,
				'error'
			);
			
			return [];
		}
		
		if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) == 200 ) {
			return json_decode( wp_remote_retrieve_body( $response ) );
		}
	}
	
	/**
	 * @param $font_families
	 * @param $font
	 *
	 * @return mixed
	 */
	private function filter_font_families ( $font_families, $font ) {
		$font_request = array_filter(
			$font_families,
			function ( $value ) use ( $font ) {
				return strpos( $value, $font->family ) !== false;
			}
		);
		
		return reset( $font_request );
	}
	
	/**
	 * @param $variants
	 * @param $font
	 *
	 * @return array
	 */
	private function process_variants ( $variants, $font ) {
		$variants = array_filter( explode( ',', $variants ) );
		
		// This means by default all fonts are requested, so we need to fill up the queue, before unloading the unwanted variants.
		if ( count( $variants ) == 0 ) {
			foreach ( $font->variants as $variant ) {
				$variants[] = $variant->id;
			}
		}
		
		return $variants;
	}
	
	/**
	 * @param $available_variants
	 * @param $wanted
	 *
	 * @return array
	 */
	private function filter_variants ( $available_variants, $wanted ) {
		list ( $family, $variants ) = explode( ':', $wanted );
		
		if ( ! $variants ) {
			return $available_variants;
		}
		
		$variants = explode( ',', $variants );
		
		return array_filter(
			$available_variants,
			function ( $font ) use ( $variants ) {
				$id = $font->id;
				
				if ( $id == 'regular' || $id == '400' ) {
					return in_array( '400', $variants ) || in_array( 'regular', $variants );
				}
				
				if ( $id == 'italic' ) {
					return in_array( '400italic', $variants ) || in_array( 'italic', $variants );
				}
				
				return in_array( $id, $variants );
			}
		);
	}
	
	/**
	 * @param $url
	 * @param $filename
	 *
	 * @return string
	 */
	private function download ( $url, $filename ) {
		if ( ! function_exists( 'download_url' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		
		wp_mkdir_p( $this->path );
		
		$file     = $this->path . '/' . $filename . '.' . pathinfo( $url, PATHINFO_EXTENSION );
		$file_uri = str_replace( WP_CONTENT_DIR, '', $file );
		
		if ( file_exists( $file ) ) {
			return content_url( $file_uri );
		}
		
		$tmp = download_url( $url );
		copy( $tmp, $file );
		@unlink( $tmp );
		
		return content_url( $file_uri );
	}
	
	/**
	 * @param $fonts
	 *
	 * @return string
	 */
	private function generate_stylesheet ( $fonts ) {
		$stylesheet   = "/**\n * Auto Generated by OMGF\n * @author: Daan van den Bergh\n * @url: https://ffwp.dev\n */\n\n";
		$font_display = OMGF_DISPLAY_OPTION;
		
		foreach ( $fonts as $font ) {
			foreach ( $font->variants as $variant ) {
				$font_family = $variant->fontFamily;
				$font_style  = $variant->fontStyle;
				$font_weight = $variant->fontWeight;
				$stylesheet  .= "@font-face {\n";
				$stylesheet  .= "    font-family: $font_family;\n";
				$stylesheet  .= "    font-style: $font_style;\n";
				$stylesheet  .= "    font-weight: $font_weight;\n";
				$stylesheet  .= "    font-display: $font_display;\n";
				$stylesheet  .= "    src: url('" . $variant->eot . "');\n";
				
				$local_src = '';
				
				if ( is_array( $variant->local ) ) {
					foreach ( $variant->local as $local ) {
						$local_src .= "local('$local'), ";
					}
				}
				
				$stylesheet .= "    src: $local_src\n";
				
				$font_src_url = isset( $variant->woff2 ) ? [ 'woff2' => $variant->woff2 ] : [];
				$font_src_url = $font_src_url + ( isset( $variant->woff ) ? [ 'woff' => $variant->woff ] : [] );
				$font_src_url = $font_src_url + ( isset( $variant->ttf ) ? [ 'ttf' => $variant->ttf ] : [] );
				
				$stylesheet .= $this->build_source_string( $font_src_url );
				$stylesheet .= "}\n";
			}
		}
		
		return $stylesheet;
	}
	
	/**
	 * @param        $sources
	 * @param string $type
	 * @param bool   $end_semi_colon
	 *
	 * @return string
	 */
	private function build_source_string ( $sources, $type = 'url', $end_semi_colon = true ) {
		$lastSrc = end( $sources );
		$source  = '';
		
		foreach ( $sources as $format => $url ) {
			$source .= "    $type('$url')" . ( ! is_numeric( $format ) ? " format('$format')" : '' );
			
			if ( $url === $lastSrc && $end_semi_colon ) {
				$source .= ";\n";
			} else {
				$source .= ",\n";
			}
		}
		
		return $source;
	}
}
