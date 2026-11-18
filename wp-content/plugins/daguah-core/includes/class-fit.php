<?php
/**
 * Fit: how a piece's measurements compare with a buyer's.
 *
 * The same rule runs in PHP (the "Fits me" filter) and in JS (the fit chip,
 * src/fit.js). Keep them identical; `npm run test:fit` checks both.
 *
 *   Fits you                  every shared measurement within 2 cm
 *   Needs a small alteration  every shared measurement within 5 cm
 *   Not your size             anything further out
 *
 * Only measurements both sides have are compared. A piece with none in
 * common gets no verdict.
 *
 * This compares the garment's measurements with the body's directly, as the
 * brief describes. Real garments are cut with ease (room to move), which
 * differs by style; an ease allowance per category is a refinement for the
 * client to decide on.
 *
 * @package Daguah\Core
 */

declare( strict_types=1 );

namespace Daguah\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Fit comparison.
 */
final class Fit {

	public const FITS  = 'fits';
	public const ALTER = 'alter';
	public const NO    = 'no';

	public const FITS_CM  = 2.0;
	public const ALTER_CM = 5.0;

	/**
	 * Compare a piece with a body.
	 *
	 * @param array $piece Measurements of the piece in cm.
	 * @param array $body  Measurements of the buyer in cm.
	 * @return array{status: string, diffs: array<string, float>}|null Null when nothing can be compared.
	 */
	public static function compare( array $piece, array $body ): ?array {
		$diffs = array();

		foreach ( array_keys( Product_Data::measurement_labels() ) as $key ) {
			if ( isset( $piece[ $key ], $body[ $key ] ) && is_numeric( $piece[ $key ] ) && is_numeric( $body[ $key ] ) ) {
				$diffs[ $key ] = round( (float) $piece[ $key ] - (float) $body[ $key ], 1 );
			}
		}

		if ( ! $diffs ) {
			return null;
		}

		$worst = max( array_map( 'abs', $diffs ) );

		return array(
			'status' => $worst <= self::FITS_CM ? self::FITS : ( $worst <= self::ALTER_CM ? self::ALTER : self::NO ),
			'diffs'  => $diffs,
		);
	}

	/**
	 * Parse measurements from a compact string: "bust:92,waist:76".
	 * Used for the Fits me filter, so guests can filter without an account.
	 *
	 * @param string $value Raw value.
	 * @return array<string, float>
	 */
	public static function parse( string $value ): array {
		$body = array();

		foreach ( explode( ',', $value ) as $pair ) {
			$parts = explode( ':', $pair, 2 );
			$key   = sanitize_key( $parts[0] ?? '' );
			$cm    = (float) ( $parts[1] ?? 0 );

			if ( array_key_exists( $key, Product_Data::measurement_labels() ) && $cm >= 20 && $cm <= 250 ) {
				$body[ $key ] = round( $cm, 1 );
			}
		}

		return $body;
	}
}
