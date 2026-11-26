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

namespace OMGF;

/**
 * Reads the descriptors of a @font-face statement.
 */
class FontFace {
	/**
	 * A CSS comment.
	 */
	const COMMENT_PATTERN = '\/\*(?:[^*]++|\*(?!\/))*+\*\/';

	/**
	 * A CSS escape, incl. a backslash followed by a CRLF line break.
	 */
	const ESCAPE_PATTERN = '\\\\(?:\r\n|[\s\S])';

	/**
	 * A quoted CSS string.
	 */
	const STRING_PATTERN = '"(?:[^"\\\\\r\n\f]++|' . self::ESCAPE_PATTERN . ')*+"|\'(?:[^\'\\\\\r\n\f]++|' . self::ESCAPE_PATTERN . ')*+\'';

	/**
	 * Parentheses and their contents, e.g. those of url().
	 */
	const PARENTHESES_PATTERN = '\((?:' . self::STRING_PATTERN . '|[^()"\'])*\)';

	/**
	 * A CSS number, e.g. 400, +7e2 or .5.
	 */
	const NUMBER_PATTERN = '[+-]?(?:\d+(?:\.\d+)?|\.\d+)(?:e[+-]?\d+)?';

	/**
	 * Angle units and the number of degrees in one.
	 */
	const ANGLE_UNITS = [
		'deg'  => 1,
		'grad' => 0.9,
		'rad'  => 180 / M_PI,
		'turn' => 360,
	];

	/**
	 * Keywords of font-weight and the weights they stand for.
	 */
	const WEIGHT_KEYWORDS = [
		'normal' => '400',
		'bold'   => '700',
	];

	/**
	 * Replaces comments in $css with a space and, if $strings is true, empties its strings, so their contents are
	 * ignored. The tokens around a comment aren't joined, and a string remains a token, so a value which contains one
	 * stays invalid.
	 *
	 * @param string $css
	 * @param bool   $strings
	 *
	 * @return string
	 */
	public static function mask_comments_and_strings( $css, $strings = true ) {
		return (string) preg_replace_callback(
			'/' . self::COMMENT_PATTERN . '|' . self::STRING_PATTERN . '/i',
			function ( $match ) use ( $strings ) {
				if ( $match[0][0] === '/' ) {
					return ' ';
				}

				return $strings ? ' "" ' : $match[0];
			},
			(string) $css
		);
	}

	/**
	 * The value of $descriptor in $font_face. Like browsers do, the last valid one is used, and comments are ignored.
	 *
	 * @param string        $font_face  A @font-face statement.
	 * @param string        $descriptor E.g. 'font-weight'.
	 * @param callable|null $validator  Receives the trimmed value and returns it (normalized), or null if it's invalid.
	 * @param bool          $strings    Whether to ignore the contents of strings. Their quotes are kept either way.
	 *
	 * @return string|null Null if $font_face has no valid value.
	 */
	public static function descriptor( $font_face, $descriptor, $validator = null, $strings = true ) {
		// Only the start of a declaration is a descriptor's name, and strings and parentheses are skipped, so a name inside
		// them, e.g. in a URL, isn't matched. A value can contain a semicolon inside them, e.g. in a data URL.
		preg_match_all(
			'/(?:' . self::STRING_PATTERN . '|' . self::PARENTHESES_PATTERN . ')(*SKIP)(*FAIL)|(?:^|[{;])\s*\K' . preg_quote( $descriptor, '/' ) . '\s*:\s*((?:' . self::STRING_PATTERN . '|' . self::PARENTHESES_PATTERN . '|[^;}"\'(])+)/i',
			self::mask_comments_and_strings( $font_face, $strings ),
			$matches
		);

		foreach ( array_reverse( $matches[1] ) as $value ) {
			$value = trim( $value );
			$value = $validator ? $validator( $value ) : $value;

			if ( $value !== null ) {
				return $value;
			}
		}

		return null;
	}

	/**
	 * @param string $font_face A @font-face statement.
	 *
	 * @return string Empty if it has none.
	 */
	public static function family( $font_face ) {
		return (string) self::descriptor(
			$font_face,
			'font-family',
			function ( $value ) {
				// One string, e.g. "Open Sans".
				if ( preg_match( '/^(?:' . self::STRING_PATTERN . ')$/', $value ) ) {
					$family = trim( self::unescape( substr( $value, 1, - 1 ) ) );

					return $family !== '' ? $family : null;
				}

				// Or identifiers separated by whitespace, e.g. Open Sans. A list of families isn't valid here.
				if ( ! preg_match( '/^-?(?:[_a-z]|[^\x00-\x7F]|\\\\.)(?:[\w-]|[^\x00-\x7F]|\\\\.)*(?:\s+-?(?:[_a-z]|[^\x00-\x7F]|\\\\.)(?:[\w-]|[^\x00-\x7F]|\\\\.)*)*$/i', $value ) ) {
					return null;
				}

				return preg_replace( '/\s+/', ' ', self::unescape( $value ) );
			},
			false
		);
	}

	/**
	 * Decodes the escapes in a CSS string or identifier, e.g. \\' and \\27 become '.
	 *
	 * @param string $value
	 *
	 * @return string
	 */
	private static function unescape( $value ) {
		return (string) preg_replace_callback(
			'/\\\\(?:([0-9a-f]{1,6})(?:\r\n|[ \t\r\n\f])?|(\r\n|[\r\n\f])|(.))/is',
			function ( $match ) {
				if ( isset( $match[3] ) && $match[3] !== '' ) {
					return $match[3];
				}

				// A line continuation.
				if ( isset( $match[2] ) && $match[2] !== '' ) {
					return '';
				}

				$code_point = hexdec( $match[1] );

				return $code_point > 0 && $code_point <= 0x10FFFF ? html_entity_decode( '&#' . $code_point . ';', ENT_QUOTES | ENT_HTML5, 'UTF-8' ) : "\u{FFFD}";
			},
			$value
		);
	}

	/**
	 * @param string $font_face A @font-face statement.
	 *
	 * @return string One weight from 1 to 1000, or a range of two separated by a space, e.g. '300 800'. '400' if it has none.
	 */
	public static function weight( $font_face ) {
		$weight = self::descriptor(
			$font_face,
			'font-weight',
			function ( $value ) {
				$weights = preg_split( '/\s+/', strtolower( $value ) );

				if ( count( $weights ) > 2 ) {
					return null;
				}

				foreach ( $weights as &$weight ) {
					$weight = self::WEIGHT_KEYWORDS[ $weight ] ?? $weight;

					if ( ! preg_match( '/^' . self::NUMBER_PATTERN . '$/i', $weight ) || $weight < 1 || $weight > 1000 ) {
						return null;
					}

					// E.g. +7e2 and 700.0 are the same weight.
					$weight = (string) ( (float) $weight );
				}

				unset( $weight );

				return implode( ' ', $weights );
			}
		);

		return $weight ?? '400';
	}

	/**
	 * @param string $font_face A @font-face statement.
	 *
	 * @return string E.g. 'italic' or 'oblique 10deg 20deg'. 'normal' if it has none.
	 */
	public static function style( $font_face ) {
		$style = self::descriptor(
			$font_face,
			'font-style',
			function ( $value ) {
				$value = preg_replace( '/\s+/', ' ', strtolower( $value ) );

				if ( ! preg_match( '/^(?:normal|italic|oblique(?: ' . self::NUMBER_PATTERN . '(?:deg|grad|rad|turn)){0,2})$/', $value ) ) {
					return null;
				}

				// An oblique angle is between -90deg and 90deg.
				preg_match_all( '/(' . self::NUMBER_PATTERN . ')(deg|grad|rad|turn)/', $value, $angles, PREG_SET_ORDER );

				foreach ( $angles as list( , $angle, $unit ) ) {
					$degrees = (float) $angle * self::ANGLE_UNITS[ $unit ];

					if ( $degrees < - 90 || $degrees > 90 ) {
						return null;
					}
				}

				return $value;
			}
		);

		return $style ?? 'normal';
	}

	/**
	 * @param string $font_face A @font-face statement.
	 *
	 * @return string The URL of the first url() in its src. Empty if it has none.
	 */
	public static function src( $font_face ) {
		$src = self::descriptor(
			$font_face,
			'src',
			function ( $value ) {
				// Strings are skipped, so e.g. a local() font name isn't read as a URL.
				$pattern = '/(?:' . self::STRING_PATTERN . ')(*SKIP)(*FAIL)|(?<![\w-])url\(\s*(?:(' . self::STRING_PATTERN . ')|([^\'")\s]+))\s*\)/i';

				// A later src without a url(), e.g. local() only, replaces an earlier one, too.
				if ( ! preg_match( $pattern, $value, $url ) ) {
					return '';
				}

				return self::unescape( ( $url[2] ?? '' ) !== '' ? $url[2] : substr( $url[1], 1, - 1 ) );
			},
			false
		);

		return (string) $src;
	}
}
