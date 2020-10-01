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
	
	/** @var array $endpoints */
	private $endpoints = [ 'css' ];
	
	/** @var string $namespace */
	protected $namespace = 'omgf/v1';
	
	/** @var string $rest_base */
	protected $rest_base = '/download/';
	
	/** @var string $handle */
	private $handle = '';
	
	/** @var string $path */
	private $path = '';
	
	/**
	 * OMGF_API_Download constructor.
	 */
	public function __construct () {
		add_filter( 'content_url', [ $this, 'rewrite_url' ], 10, 2 );
	}
	
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
	 * @param $request
	 */
	public function process ( $request ) {
		$params       = $request->get_params();
		$this->handle = $params['handle'] ?? '';
		
		if ( ! $this->handle ) {
			wp_send_json_error( 'Handle not provided.', 406 );
		}
		
		$this->path = WP_CONTENT_DIR . OMGF_CACHE_PATH . '/' . $this->handle;
		
		$url           = self::OMGF_GOOGLE_FONTS_API_URL . '/api/fonts/%s';
		$font_families = explode( '|', $params['family'] );
		
		foreach ( $font_families as $font_family ) {
			list( $family, $variants ) = explode( ':', $font_family );
			$family = strtolower(str_replace( ' ', '-', $family ) );
			
			if ( defined( 'OMGF_PRO_FORCE_SUBSETS' ) && ! empty( OMGF_PRO_FORCE_SUBSETS ) ) {
				$query['subsets'] = implode( ',', OMGF_PRO_FORCE_SUBSETS );
			} else {
				$query['subsets'] = $params['subset'] ?? 'latin,latin-ext';
			}
			
			$response = wp_remote_get(
				sprintf( $url, $family ) . '?' . http_build_query( $query )
			);
			
			if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) == 200 ) {
				$fonts[] = json_decode( wp_remote_retrieve_body( $response ) );
			}
		}
		
		foreach ( $fonts as &$font ) {
			$font_request   = array_filter(
				$font_families,
				function ( $value ) use ( $font ) {
					return strpos( $value, $font->family ) !== false;
				}
			);
			$font->variants = $this->filter_variants( $font->variants, reset( $font_request ) );
		}
		
		foreach ( $fonts as &$font ) {
			foreach ( $font->variants as &$variant ) {
				$font_family    = trim($variant->fontFamily, '\'"');
				$filename       = strtolower( $font_family . '-' . $variant->fontStyle . '-' . $variant->fontWeight );
				$variant->woff  = $this->download( $variant->woff, $filename );
				$variant->woff2 = $this->download( $variant->woff2, $filename );
				$variant->eot   = $this->download( $variant->eot, $filename );
				$variant->ttf   = $this->download( $variant->ttf, $filename );
			}
		}
		
		$stylesheet = $this->generate_stylesheet( $fonts );
		
		$local_file = $this->path . '/' . $this->handle . '.css';
		
		file_put_contents( $local_file, $stylesheet );
		
		// After downloading it, serve it.
		header( 'Content-Type: text/css' );
		header( "Content-Transfer-Encoding: Binary" );
		header( 'Content-Length: ' . filesize( $local_file ) );
		flush();
		readfile( $local_file );
		die();
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
				
				if ( $id == 'regular' || $id == 'italic' ) {
					$id = '400';
				}
				
				return in_array( $id, $variants );
			}
		);
	}
	
	/**
	 * @param $urls
	 *
	 * @return bool
	 */
	private function download ( $url, $filename ) {
		if ( ! function_exists( 'download_url' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		
		wp_mkdir_p( $this->path );
		
		$file     = $this->path . '/' . $filename . '.' . pathinfo($url, PATHINFO_EXTENSION);
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
	 * @param $url
	 * @param $path
	 *
	 * @return mixed
	 */
	public function rewrite_url ( $url, $path ) {
		/**
		 * Exit early if this isn't requested by OMGF.
		 */
		if ( strpos( $url, OMGF_CACHE_PATH ) === false ) {
			return $url;
		}
		
		/**
		 * If Relative URLs is enabled, overwrite URL with Path and continue execution.
		 */
		if ( OMGF_RELATIVE_URL ) {
			$content_dir = str_replace( site_url(), '', content_url() );
			
			$url = $content_dir . $path;
		}
		
		if ( OMGF_CDN_URL ) {
			$url = str_replace( site_url(), OMGF_CDN_URL, $url );
		}
		
		if ( OMGF_CACHE_URI ) {
			$url = str_replace( OMGF_CACHE_PATH, OMGF_CACHE_URI, $url );
		}
		
		return $url;
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
				
				if (is_array($variant->local)) {
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
	 * @param bool   $endSemiColon
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
