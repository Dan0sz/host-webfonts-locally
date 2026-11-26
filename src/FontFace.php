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
		preg_match_all(
			'/(?<![\w-])' . preg_quote( $descriptor, '/' ) . '\s*:\s*((?:' . self::STRING_PATTERN . '|[^;}"\'])+)/i',
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
				$family = trim( $value, " \t\n\r\f\"'" );

				return $family !== '' ? $family : null;
			},
			false
		);
	}

	/**
	 * @param string $font_face A @font-face statement.
	 *
	 * @return string One weight, or a range of two weights separated by a space, e.g. '300 800'. '400' if it has none.
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

					if ( ! preg_match( '/^\d+(?:\.\d+)?$/', $weight ) ) {
						return null;
					}
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

				return preg_match( '/^(?:normal|italic|oblique(?: -?\d*\.?\d+deg){0,2})$/', $value ) ? $value : null;
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
				return preg_match( '/(?<![\w-])url\(\s*([\'"]?)([^\'")\s]+)\1\s*\)/i', $value, $url ) ? $url[2] : null;
			},
			false
		);

		return (string) $src;
	}
}
