<?php
/**
 * Tests
 *
 * @package OMGF
 * @author  Daan van den Bergh
 */

namespace OMGF\Tests;

use Yoast\WPTestUtils\BrainMonkey\TestCase as YoastTestCase;

class TestCase extends YoastTestCase {
	/** @var string $current_handle */
	protected $current_handle;

	/**
	 * Build class.
	 */
	public function __construct() {
		/**
		 * During local unit testing this constant is required.
		 */
		if ( ! defined( 'ABSPATH' ) ) {
			define( 'ABSPATH', true );
		}

		/**
		 * Required for loading assets.
		 */
		if ( ! defined( 'OMGF_TESTS_ROOT' ) ) {
			define( 'OMGF_TESTS_ROOT', __DIR__ . '/' );
		}

		parent::__construct();
	}

	/**
	 * Add manage_options cap.
	 *
	 * @param $allcaps
	 *
	 * @return true[]
	 */
	public function addManageOptionsCap( $allcaps ) {
		$allcaps['manage_options'] = true;

		return $allcaps;
	}

	public function addPreloadFonts() {
		return unserialize(
			'a:1:{s:14:"test_cache_key";a:1:{s:15:"source-sans-pro";a:7:{s:9:"300italic";s:1:"0";s:9:"400italic";s:1:"0";i:300;s:1:"0";i:400;s:3:"400";i:600;s:3:"600";i:700;s:3:"700";i:900;s:1:"0";}}}'
		);
	}

	/**
	 * @return string
	 */
	public function addTestCacheKey() {
		return 'cache_key,test_cache_key';
	}

	/**
	 * Mock Google Fonts requests.
	 *
	 * @param $pre
	 * @param $args
	 * @param $url
	 *
	 * @return array|\WP_Error
	 */
	public function mockGoogleFontsRequests( $pre, $args, $url ) {
		/**
		 * The only test that should receive an actual response is the testDownload method in the DownloadTest class.
		 */
		if ( $this->getName() === 'testDownload' ) {
			return $pre;
		}

		if ( strpos( $url, 'fonts.gstatic.com' ) !== false ) {
			$extension = pathinfo( $url, PATHINFO_EXTENSION );
			$content   = 'mock-font-content';

			if ( ! empty( $args['stream'] ) && ! empty( $args['filename'] ) ) {
				file_put_contents( $args['filename'], $content );
			}

			return [
				'headers'  => [
					'content-type' => $this->getMimeType( $extension ),
				],
				'body'     => $content,
				'response' => [
					'code'    => 200,
					'message' => 'OK',
				],
				'cookies'  => [],
				'filename' => $args['filename'] ?? '',
			];
		}

		if ( strpos( $url, 'fonts.googleapis.com' ) !== false ) {
			// Capture the handle if possible to help with mocking decisions
			preg_match( '/handle=([^&]+)/', $url, $handle_match );

			// In some tests, the handle might not be in the URL but we can guess from context
			// In testLegacyModeAgainstVariableFontsSupport, it's passed to Optimize constructor.
			// We can't easily get it here, but we can try to look at the URL for hints.
			if ( strpos( $url, 'variable-fonts' ) !== false ) {
				$this->current_handle = 'variable-fonts';
			} elseif ( strpos( $url, 'traditional-fonts' ) !== false ) {
				$this->current_handle = 'traditional-fonts';
			}

			return [
				'headers'  => [
					'content-type' => 'text/css',
				],
				'body'     => $this->getMockStylesheet( $url ),
				'response' => [
					'code'    => 200,
					'message' => 'OK',
				],
				'cookies'  => [],
			];
		}

		return $pre;
	}

	/**
	 * Get mime type based on extension.
	 *
	 * @param $extension
	 *
	 * @return string
	 */
	private function getMimeType( $extension ) {
		$mime_types = [
			'woff2' => 'font/woff2',
			'woff'  => 'font/woff',
			'ttf'   => 'font/ttf',
			'otf'   => 'font/otf',
			'eot'   => 'application/vnd.ms-fontobject',
		];

		return $mime_types[ $extension ] ?? 'application/octet-stream';
	}

	/**
	 * Returns a mock stylesheet based on the Google Fonts API URL.
	 *
	 * @param string $url
	 *
	 * @return string
	 */
	private function getMockStylesheet( $url ) {
		// Extract families from URL
		preg_match_all( '/family=([^&]+)/', $url, $matches );
		$families = $matches[1] ?? [];

		$css = '';
		foreach ( $families as $family_string ) {
			// e.g. Open+Sans:300,400,700 OR Open+Sans:ital,wght@0,300;0,400
			$parts  = explode( ':', $family_string );
			$family = str_replace( '+', ' ', $parts[0] );
			$args   = $parts[1] ?? '400';

			// In testLegacyModeAgainstVariableFontsSupport, it's passed to Optimize constructor.
			// The URL doesn't contain it. But we can check for specific families/weights.
			// If it's Open Sans with multiple weights, we check for a custom query param we might add in the test or just guess.
			// Let's look at the full URL.
			if ( strpos( $url, 'variable-fonts' ) !== false ) {
				$this->current_handle = 'variable-fonts';
			}

			if ( strpos( $args, '@' ) !== false ) {
				// CSS2 format: ital,wght@0,300;0,400;1,300
				$parts   = explode( '@', $args );
				$weights = explode( ';', $parts[1] );

				foreach ( $weights as $weight_pair ) {
					$pair   = explode( ',', $weight_pair );
					$style  = isset( $pair[0] ) && $pair[0] == '1' ? 'italic' : 'normal';
					$weight = $pair[1] ?? '400';

					$css .= $this->generateFontFace( $family, $style, $weight );
				}
			} else {
				// CSS1 format: 300,400italic,700
				$weights = explode( ',', $args );

				foreach ( $weights as $weight_info ) {
					$style  = strpos( $weight_info, 'italic' ) !== false ? 'italic' : 'normal';
					$weight = str_replace( 'italic', '', $weight_info );

					if ( empty( $weight ) ) {
						$weight = '400';
					}

					$css .= $this->generateFontFace( $family, $style, $weight );
				}
			}
		}

		return $css;
	}

	/**
	 * @param $family
	 * @param $style
	 * @param $weight
	 *
	 * @return string
	 */
	private function generateFontFace( $family, $style, $weight ) {
		$id = strtolower( str_replace( ' ', '', $family ) );

		// FiltersTest::testLegacyModeAgainstVariableFontsSupport expects specific filenames.
		// If it's Open Sans and not variable, it expects weight in filename.
		// Optimize::process() decides the filename based on whether it thinks it's a variable font.
		// If we use the same URL for different weights, it thinks it's variable.
		// So we use unique URLs to simulate traditional fonts.
		// BUT if weights are like 300, 400, 700 it should be traditional.
		// If the weights were something like 100 900, it might be variable.
		// The test uses: https://fonts.googleapis.com/css?family=Open+Sans:300,400,700

		$url = "https://fonts.gstatic.com/s/$id/v1/$id-$style-latin-$weight.woff2";

		// Force variable font for the second part of testLegacyModeAgainstVariableFontsSupport
		// by using the same URL if we are in the 'variable-fonts' test case handle.
		if ( isset( $this->current_handle ) && $this->current_handle === 'variable-fonts' ) {
			$url = "https://fonts.gstatic.com/s/$id/v1/$id-$style-latin.woff2";
		}

		return "
/* latin */
@font-face {
  font-family: '$family';
  font-style: $style;
  font-weight: $weight;
  font-display: swap;
  src: url($url) format('woff2');
}
";
	}

	public function returnOn() {
		return 'on';
	}

	/**
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		add_filter( 'pre_http_request', [ $this, 'mockGoogleFontsRequests' ], 10, 3 );
	}

	/**
	 * @return void
	 */
	public function tear_down() {
		parent::tear_down();

		remove_filter( 'pre_http_request', [ $this, 'mockGoogleFontsRequests' ], 10 );
	}
}
