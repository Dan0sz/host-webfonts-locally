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
 * Reads and normalizes widths (font-stretch).
 */
class FontStretch {
	/**
	 * Keywords and the widths they stand for.
	 */
	const KEYWORDS = [
		'ultra-condensed' => '50%',
		'extra-condensed' => '62.5%',
		'condensed'       => '75%',
		'semi-condensed'  => '87.5%',
		'normal'          => '100%',
		'semi-expanded'   => '112.5%',
		'expanded'        => '125%',
		'extra-expanded'  => '150%',
		'ultra-expanded'  => '200%',
	];

	/**
	 * The width of a @font-face statement. Like browsers do, the last valid font-stretch is used, and comments and
	 * strings are ignored.
	 *
	 * @param string $font_face A @font-face statement.
	 *
	 * @return string One or two percentages. Empty for the default width.
	 */
	public static function find( $font_face ) {
		return (string) FontFace::descriptor(
			$font_face,
			'font-stretch',
			function ( $value ) {
				// A string isn't a valid width.
				return str_contains( $value, '"' ) ? null : self::parse( $value );
			}
		);
	}

	/**
	 * @param mixed $font_stretch Value of a font-stretch property, e.g. 'condensed' or '75% 100%'.
	 *
	 * @return string One or two percentages. Empty for the default width or an invalid value.
	 */
	public static function normalize( $font_stretch ) {
		return (string) self::parse( $font_stretch );
	}

	/**
	 * @param mixed $font_stretch Value of a font-stretch property, e.g. 'condensed' or '75% 100%'.
	 *
	 * @return string|null One or two percentages. Empty for the default width, null for an invalid value.
	 */
	private static function parse( $font_stretch ) {
		if ( ! is_string( $font_stretch ) ) {
			return null; // @codeCoverageIgnore
		}

		$values = preg_split( '/\s+/', strtolower( trim( $font_stretch, " \t\n\r\"'" ) ) );

		if ( count( $values ) > 2 ) {
			return null;
		}

		foreach ( $values as &$value ) {
			$value = self::KEYWORDS[ $value ] ?? $value;

			if ( ! preg_match( '/^\+?\d+(\.\d+)?%$/', $value ) ) {
				return null;
			}

			// E.g. +075.0% and 75% are the same width.
			$value = preg_replace( [ '/^\+?0*(?=\d)/', '/(\.\d*?)0+%$/', '/\.?%$/' ], [ '', '$1%', '' ], $value ) . '%';
		}

		unset( $value );

		$values = array_values( array_unique( $values ) );

		return $values === [ '100%' ] ? '' : implode( ' ', $values );
	}
}
