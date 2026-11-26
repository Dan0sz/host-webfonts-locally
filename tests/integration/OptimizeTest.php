<?php
/**
 * Optimize Test
 * @package OMGF
 * @author  Daan van den Bergh
 */

namespace OMGF\Tests\Integration;

use OMGF\Admin\Notice;
use OMGF\Optimize;
use OMGF\Tests\TestCase;
use OMGF\Tests\Mocks\HttpClientMock;

class OptimizeTest extends TestCase {
	/**
	 * Test @see \OMGF\Optimize::process() with a CSS2 url with multiple font-families.
	 */
	public function testProcessWithCSS2() {
		$url    = 'https://fonts.googleapis.com/css2?family=Afacad:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500;1,600;1,700&display=swap&family=IBM+Plex+Sans:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;1,100;1,200;1,300;1,400;1,500;1,600;1,700&display=swap&family=Nunito+Sans:ital,wght@0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap&family=Open+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,300;1,400;1,500;1,600;1,700;1,800&display=swap&family=Readex+Pro:wght@200;300;400;500;600;700&display=swap';
		$handle = 'test-css2';
		$return = 'object';

		$class     = new Optimize( $url, $handle, $handle, $return );
		$processed = $class->process();

		$this->assertArrayHasKey( 'test-css2', $processed );
		$this->assertArrayHasKey( 'afacad', $processed[ 'test-css2' ] );
		$this->assertArrayHasKey( 'ibm-plex-sans', $processed[ 'test-css2' ] );
		$this->assertArrayHasKey( 'nunito-sans', $processed[ 'test-css2' ] );
		$this->assertArrayHasKey( 'open-sans', $processed[ 'test-css2' ] );
		$this->assertArrayHasKey( 'readex-pro', $processed[ 'test-css2' ] );
	}

	/**
	 * Test @see \OMGF\Optimize::process() with a CSS stylesheet containing several different src notations.
	 * @return void
	 */
	public function testProcessWithCrazySyntaxes() {
		$css = file_get_contents( OMGF_TESTS_ROOT . 'assets/crazy-syntaxes.css' );

		HttpClientMock::activate();
		HttpClientMock::mockCssContent( $css );

		$url       = 'https://daan.dev/tests/crazy-syntaxes.css';
		$handle    = 'test-crazy-syntaxes';
		$class     = new Optimize( $url, $handle, $handle, 'object' );
		$processed = $class->process();

		HttpClientMock::deactivate();

		$this->assertArrayHasKey( 'test-crazy-syntaxes', $processed );
		$this->assertArrayHasKey( 'roboto', $processed[ 'test-crazy-syntaxes' ] );
		$this->assertArrayHasKey( 'open-sans', $processed[ 'test-crazy-syntaxes' ] );
		$this->assertArrayHasKey( 'lato', $processed[ 'test-crazy-syntaxes' ] );
		$this->assertArrayHasKey( 'montserrat', $processed[ 'test-crazy-syntaxes' ] );
		$this->assertArrayHasKey( 'source-sans-pro', $processed[ 'test-crazy-syntaxes' ] );
		$this->assertArrayHasKey( 'poppins', $processed[ 'test-crazy-syntaxes' ] );
		$this->assertArrayHasKey( 'merriweather', $processed[ 'test-crazy-syntaxes' ] );
		$this->assertArrayHasKey( 'nunito', $processed[ 'test-crazy-syntaxes' ] );
		$this->assertArrayHasKey( 'ubuntu', $processed[ 'test-crazy-syntaxes' ] );
		$this->assertArrayHasKey( 'quicksand', $processed[ 'test-crazy-syntaxes' ] );

		foreach ( $processed[ 'test-crazy-syntaxes' ] as $font_object ) {
			$this->assertTrue( count( $font_object->variants ) > 0 );
		}
	}

	/**
	 * Test @see \OMGF\Optimize::process() with a stylesheet which isn't a Google Fonts API
	 * response. Only the font families defined in its @font-face statements may be stored;
	 * the values of its regular font-family declarations may not.
	 *
	 * Those values used to be stored as font families, which is what filled up the Optimized
	 * Fonts table with entries like 'Arial,Helvetica,sans-serif !important' and 'inherit'.
	 *
	 * @return void
	 */
	public function testProcessSkipsInvalidFontFamilies() {
		$css = file_get_contents( OMGF_TESTS_ROOT . 'assets/non-google-fonts.css' );

		HttpClientMock::activate();
		HttpClientMock::mockCssContent( $css );

		$url       = 'https://daan.dev/tests/non-google-fonts.css';
		$handle    = 'test-non-google-fonts';
		$class     = new Optimize( $url, $handle, $handle, 'object' );
		$processed = $class->process();

		HttpClientMock::deactivate();

		$this->assertArrayHasKey( $handle, $processed );

		// Both @font-face statements are processed, incl. the locally installed (non-Google) font.
		$this->assertArrayHasKey( 'mulish', $processed[ $handle ] );
		$this->assertArrayHasKey( 'nunito-sans-12pt-extralight-12pt', $processed[ $handle ] );

		// Nothing else is.
		$this->assertCount( 2, $processed[ $handle ] );
		$this->assertArrayNotHasKey( 'inherit', $processed[ $handle ] );
		$this->assertArrayNotHasKey( 'sans-serif', $processed[ $handle ] );
		$this->assertArrayNotHasKey( 'mulish-!important', $processed[ $handle ] );
	}

	/**
	 * Test @see \OMGF\Optimize::process() with a font family whose name has a special meaning in
	 * a regular expression. It's interpolated into the pattern which matches a font family to its
	 * @font-face statements, so it must be escaped: '.*' used to match every @font-face statement
	 * in the stylesheet, and claimed the variants (and downloads) of every other font in it.
	 *
	 * @return void
	 */
	public function testProcessMatchesFontFamiliesLiterally() {
		$css = file_get_contents( OMGF_TESTS_ROOT . 'assets/regex-injection.css' );

		HttpClientMock::activate();
		HttpClientMock::mockCssContent( $css );

		$url       = 'https://daan.dev/tests/regex-injection.css';
		$handle    = 'test-regex-injection';
		$class     = new Optimize( $url, $handle, $handle, 'object' );
		$processed = $class->process();

		HttpClientMock::deactivate();

		$this->assertArrayHasKey( $handle, $processed );

		// Each @font-face statement belongs to exactly one font family.
		$this->assertCount( 1, $processed[ $handle ][ 'roboto' ]->variants );
		$this->assertCount( 1, $processed[ $handle ][ 'lato' ]->variants );

		// The wildcard matches neither of them, so there's nothing to download for it.
		$this->assertArrayHasKey( '.*', $processed[ $handle ] );
		$this->assertCount( 0, $processed[ $handle ][ '.*' ]->variants );
	}

	/**
	 * A variable font uses the same file for each font weight, so it should only be downloaded once.
	 *
	 * @see \OMGF\Optimize::process()
	 * @return void
	 */
	public function testProcessDownloadsVariableFontOnce() {
		$url = 'https://daan.dev/tests/variable-font.css';
		$css = '';

		foreach ( [ 300, 400, 700 ] as $weight ) {
			$css .= "/* latin */\n@font-face {\n  font-family: 'Nunito Sans';\n  font-style: italic;\n  font-weight: $weight;\n  src: url(https://fonts.gstatic.com/s/nunitosans/v19/variable-italic.woff2) format('woff2');\n}\n";
		}

		$requests = 0;
		// Font files are mocked by TestCase::mockGoogleFontsRequests(), this runs after it.
		$mock = function ( $pre, $args, $request_url ) use ( $url, $css, &$requests ) {
			if ( $request_url === $url ) {
				return [ 'body' => $css, 'response' => [ 'code' => 200 ] ];
			}

			if ( str_contains( $request_url, 'fonts.gstatic.com' ) ) {
				$requests++;
			}

			return $pre;
		};

		add_filter( 'pre_http_request', $mock, 11, 3 );

		try {
			$class     = new Optimize( $url, 'test-variable-font', 'test-variable-font', 'object' );
			$processed = $class->process();
		} finally {
			remove_filter( 'pre_http_request', $mock, 11 );
		}

		$this->assertSame( 1, $requests );

		$variants = $processed['test-variable-font']['nunito-sans']->variants;

		$this->assertCount( 3, $variants );

		foreach ( $variants as $variant ) {
			$this->assertStringEndsWith( '/nunito-sans-italic-latin.woff2', $variant->woff2 );
		}
	}

	/**
	 * The widths of fonts should be kept, and variants which only differ in width shouldn't overwrite each other.
	 *
	 * @see \OMGF\Optimize::process()
	 * @return void
	 */
	public function testProcessKeepsFontStretch() {
		$url   = 'https://daan.dev/tests/font-stretch.css';
		$css   = '';
		$faces = [
			[ 'Open Sans', '300 800', '75% 100%', 'range' ],
			[ 'Roboto', '400', '75%', 'shared' ],
			[ 'Roboto', '400', '100%', 'shared' ],
			[ 'Anybody', '400', '87.5%', 'condensed' ],
			[ 'Anybody', '400', '100%', 'regular' ],
			[ 'Lato', '400', 'NoRmAl', 'lato' ],
			[ 'Inter', '400', '75%', 'inter' ],
			[ 'Inter', '400', '100%', 'inter' ],
			[ 'Inter', '700', '100%', 'inter-bold' ],
			[ 'Inter', '900', '100%', 'inter-black' ],
			[ 'Nunito', '300', '100%', 'nunito-shared' ],
			[ 'Nunito', '400', '100%', 'nunito-shared' ],
			[ 'Nunito', '700', '100%', 'nunito-bold' ],
			[ 'Nunito', '900', '100%', 'nunito-black' ],
			[ 'Mulish', '400', '75% 100%', 'mulish-a' ],
			[ 'Mulish', '400', '75%', 'mulish-b' ],
			[ 'Mulish', '100', '75%', 'mulish-c' ],
			[ 'Mulish', '500', '75%', 'mulish-b' ],
			[ 'Oswald', '400', '112.125%', 'oswald-a' ],
			[ 'Oswald', '400', '112% 125%', 'oswald-b' ],
			[ 'Oswald', '100 150', '100%', 'oswald-c' ],
			[ 'Oswald', '100', '150%', 'oswald-d' ],
		];

		foreach ( $faces as $face ) {
			$css .= "/* latin */\n@font-face {\n  font-family: '$face[0]';\n  font-style: normal;\n  font-weight: $face[1];\n  font-stretch: $face[2];\n  src: url(https://fonts.gstatic.com/s/$face[3].woff2) format('woff2');\n}\n";
		}

		$mock = function ( $pre, $args, $request_url ) use ( $url, $css ) {
			return $request_url === $url ? [ 'body' => $css, 'response' => [ 'code' => 200 ] ] : $pre;
		};

		add_filter( 'pre_http_request', $mock, 11, 3 );

		try {
			$class = new Optimize( $url, 'test-font-stretch', 'test-font-stretch', 'object' );
			$fonts = $class->process()['test-font-stretch'];
		} finally {
			remove_filter( 'pre_http_request', $mock, 11 );
		}

		$this->assertSame( [ 'latin-300-800-stretch-75-100' ], array_keys( $fonts['open-sans']->variants ) );
		$this->assertSame( [ 'latin-400-stretch-75', 'latin-400' ], array_keys( $fonts['roboto']->variants ) );
		$this->assertSame( [ '400', '400' ], array_column( $fonts['roboto']->variants, 'id' ) );
		$this->assertSame( [ 'latin-400' ], array_keys( $fonts['lato']->variants ) );
		$this->assertFalse( property_exists( $fonts['lato']->variants['latin-400'], 'fontStretch' ) );
		$this->assertFalse( property_exists( $fonts['roboto']->variants['latin-400'], 'fontStretch' ) );
		$this->assertSame( '75%', $fonts['roboto']->variants['latin-400-stretch-75']->fontStretch );
		$this->assertSame( '75% 100%', $fonts['open-sans']->variants['latin-300-800-stretch-75-100']->fontStretch );

		// Each width has its own file.
		$this->assertStringEndsWith( '/anybody-normal-latin-400-stretch-87_5.woff2', $fonts['anybody']->variants['latin-400-stretch-87_5']->woff2 );
		$this->assertStringEndsWith( '/anybody-normal-latin-400.woff2', $fonts['anybody']->variants['latin-400']->woff2 );

		// Different files never share a filename, also if other variants of the family share a file.
		foreach ( [ 'inter', 'nunito', 'mulish' ] as $id ) {
			$this->assertCount( 3, array_unique( array_column( $fonts[ $id ]->variants, 'woff2' ) ) );
		}

		$this->assertCount( 4, array_unique( array_column( $fonts['oswald']->variants, 'woff2' ) ) );

		$stylesheet = file_get_contents( OMGF_UPLOAD_DIR . '/test-font-stretch/test-font-stretch.css' );

		$this->assertStringContainsString( 'font-stretch: 75% 100%;', $stylesheet );
		$this->assertStringContainsString( 'font-stretch: 87.5%;', $stylesheet );
		// The default width isn't declared.
		$this->assertSame( 11, substr_count( $stylesheet, 'font-stretch:' ) );
		$this->assertSame( 22, substr_count( $stylesheet, '@font-face' ) );
	}

	/**
	 * Variable fonts with a weight range shouldn't be loaded if Don't Load is checked.
	 *
	 * @see \OMGF\Optimize::process()
	 * @return void
	 */
	public function testProcessUnloadsWeightRanges() {
		$url    = 'https://daan.dev/tests/unload-range.css';
		$css    = '';
		$unload = function () {
			return [ 'test-unload-range' => [ 'open-sans' => [ '300-800italic' => '300-800italic' ] ] ];
		};
		$mock   = function ( $pre, $args, $request_url ) use ( $url, &$css ) {
			return $request_url === $url ? [ 'body' => $css, 'response' => [ 'code' => 200 ] ] : $pre;
		};

		$faces = [
			[ 'normal', '100%', 'normal' ],
			[ 'italic', '100%', 'italic' ],
			[ 'italic', '75%', 'italic-condensed' ],
		];

		foreach ( $faces as [ $style, $width, $source ] ) {
			$css .= "/* latin */\n@font-face {\n  font-family: 'Open Sans';\n  font-style: $style;\n  font-weight: 300\t800;\n  font-stretch: $width;\n  src: url(https://fonts.gstatic.com/s/range-$source.woff2) format('woff2');\n}\n";
		}

		add_filter( 'omgf_setting_unload_fonts', $unload );
		add_filter( 'pre_http_request', $mock, 11, 3 );

		try {
			$class = new Optimize( $url, 'test-unload-range', 'test-unload-range', 'object' );
			$fonts = $class->process()['test-unload-range'];
		} finally {
			remove_filter( 'omgf_setting_unload_fonts', $unload );
			remove_filter( 'pre_http_request', $mock, 11 );
		}

		$this->assertSame( [ '300-800' ], array_column( $fonts['open-sans']->variants, 'id' ) );
		$this->assertSame( [ '300 800' ], array_column( $fonts['open-sans']->variants, 'fontWeight' ) );

		$stylesheet = file_get_contents( OMGF_UPLOAD_DIR . '/test-unload-range/test-unload-range.css' );
		$this->assertSame( 1, substr_count( $stylesheet, '@font-face' ) );
		$this->assertStringContainsString( 'font-weight: 300 800;', $stylesheet );
		$this->assertStringNotContainsString( 'font-style: italic;', $stylesheet );
	}

	/**
	 * Test @see \OMGF\Optimize::process() when the stylesheet can't be fetched, e.g. because the server can't reach the
	 * Google Fonts API. The admin is told why.
	 *
	 * @return void
	 */
	public function testProcessNotifiesWhenStylesheetCantBeFetched() {
		$url       = 'https://fonts.googleapis.com/css?family=Fetch+Failed';
		$responses = [
			'cURL error 28: Connection timed out'  => new \WP_Error( 'http_request_failed', 'cURL error 28: Connection timed out' ),
			'HTTP 403'                             => [ 'body' => 'Forbidden', 'response' => [ 'code' => 403 ] ],
			'the response isn&#039;t a stylesheet' => [ 'body' => '<html></html>', 'headers' => [ 'content-type' => 'text/html' ], 'response' => [ 'code' => 200 ] ],
		];
		$response  = null;
		$mock      = function ( $pre, $args, $request_url ) use ( $url, &$response ) {
			return $request_url === $url ? $response : $pre;
		};

		add_filter( 'pre_http_request', $mock, 11, 3 );

		try {
			foreach ( $responses as $error => $response ) {
				delete_transient( Notice::OMGF_ADMIN_NOTICE_TRANSIENT );

				$class = new Optimize( $url, 'test-fetch-failed', 'test-fetch-failed', 'object' );

				$this->assertSame( '', $class->process(), $error );

				$notices = get_transient( Notice::OMGF_ADMIN_NOTICE_TRANSIENT );

				$this->assertSame( "OMGF couldn't fetch the stylesheet $url: $error", $notices['all']['error']['omgf-fetch-stylesheet-failed'] ?? '' );
			}
		} finally {
			remove_filter( 'pre_http_request', $mock, 11 );
			delete_transient( Notice::OMGF_ADMIN_NOTICE_TRANSIENT );
		}
	}
}
