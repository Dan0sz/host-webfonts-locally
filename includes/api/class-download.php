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
	const OMGF_GOOGLE_FONTS_API_URL = 'https://fonts.googleapis.com/css';
	
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
		
		unset( $params['handle'] );
		$query = '?' . http_build_query( $params );
		$url   = self::OMGF_GOOGLE_FONTS_API_URL . $query;
		
		$response = wp_remote_get(
			$url,
			[
				'user-agent' => $request->get_header( 'user_agent' )
			]
		);
		
		/** @var WP_Error $response */
		if ( is_wp_error( $response ) ) {
			wp_send_json_error( $response->get_error_message(), $response->get_error_code() );
		}
		
		$stylesheet = $response['body'];
		
		preg_match_all( '#\bhttps?://[^,\s()<>]+(?:\([\w\d]+\)|([^,[:punct:]\s]|/))#', $stylesheet, $font_urls );
		
		if ( ! isset( $font_urls[0] ) ) {
			wp_send_json_error( 'No valid URLs found.', 406 );
		}
		
		$downloaded_fonts = $this->download( $font_urls[0] );
		
		if ( $downloaded_fonts == 0 ) {
			wp_send_json_success( 'All fonts are downloaded before.', 200 );
		}
		
		$updated_stylesheet = $this->update( $stylesheet, $font_urls[0] );
		
		if ( $stylesheet == $updated_stylesheet ) {
			wp_send_json_success( 'New stylesheet equals updated stylesheet. No reason to write.', 200 );
		}
		
		file_put_contents( $this->path . '/' . $this->handle . '.css', $updated_stylesheet );
	}
	
	/**
	 * @param $urls
	 *
	 * @return bool
	 */
	private function download ( $urls ) {
		if ( ! function_exists( 'download_url' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		
		$downloaded = 0;
		
		wp_mkdir_p( $this->path );
		
		foreach ( $urls as $url ) {
			$file = $this->path . '/' . basename( $url );
			
			if ( file_exists( $file ) ) {
				continue;
			}
			
			$tmp    = download_url( $url );
			$copied = copy( $tmp, $file );
			
			if ( $copied ) {
				$downloaded++;
			}
			
			@unlink( $tmp );
		}
		
		return count( $urls ) == $downloaded;
	}
	
	/**
	 * @param $stylesheet
	 * @param $urls
	 *
	 * @return mixed
	 */
	private function update ( $stylesheet, $urls ) {
		foreach ( $urls as $url ) {
			$local_urls[] = content_url( OMGF_CACHE_PATH . '/' . $this->handle . '/' . basename( $url ) );
		}
		
		$new_stylesheet = str_replace( $urls, $local_urls, $stylesheet );
		
		return $new_stylesheet;
	}
}
