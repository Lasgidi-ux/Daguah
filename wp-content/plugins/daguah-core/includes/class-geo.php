<?php
/**
 * Places for the Thread: a dot grid map and the towns pieces come from and go to.
 *
 * The land outlines are deliberately coarse, hand drawn polygons in
 * longitude and latitude: enough for a minimal dot map of North America,
 * Europe and Africa, small enough to ship as data. The dots are projected
 * once (plain equirectangular) and cached.
 *
 * @package Daguah\Core
 */

declare( strict_types=1 );

namespace Daguah\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Map projection, land mask, towns.
 */
final class Geo {

	public const WIDTH  = 800;
	public const HEIGHT = 424;

	private const WEST  = -130.0;
	private const EAST  = 55.0;
	private const NORTH = 62.0;
	private const SOUTH = -36.0;
	private const STEP  = 3.0;

	/**
	 * Coarse land outlines as [lon, lat] points.
	 *
	 * @return array<string, array<int, array{0: float, 1: float}>>
	 */
	private static function land(): array {
		return array(
			'north_america' => array( array( -130, 55 ), array( -130, 62 ), array( -64, 62 ), array( -62, 55 ), array( -56, 52 ), array( -60, 47 ), array( -66, 44 ), array( -70, 42 ), array( -74, 40 ), array( -76, 35 ), array( -81, 31 ), array( -80, 25 ), array( -82, 27 ), array( -84, 30 ), array( -89, 30 ), array( -94, 29 ), array( -97, 26 ), array( -97, 22 ), array( -95, 18 ), array( -90, 21 ), array( -87, 21 ), array( -88, 16 ), array( -84, 11 ), array( -79, 9 ), array( -83, 8 ), array( -86, 12 ), array( -92, 14 ), array( -96, 16 ), array( -105, 20 ), array( -106, 23 ), array( -110, 24 ), array( -112, 29 ), array( -115, 30 ), array( -117, 33 ), array( -121, 36 ), array( -124, 40 ), array( -124, 48 ), array( -128, 51 ) ),
			'cuba'          => array( array( -85, 22 ), array( -80, 23 ), array( -75, 20 ), array( -78, 20 ) ),
			'south_america' => array( array( -77, 8 ), array( -72, 12 ), array( -62, 11 ), array( -52, 5 ), array( -50, 0 ), array( -35, -6 ), array( -39, -14 ), array( -41, -22 ), array( -48, -26 ), array( -53, -34 ), array( -58, -36 ), array( -71, -36 ), array( -72, -30 ), array( -71, -18 ), array( -76, -14 ), array( -81, -5 ), array( -80, 0 ) ),
			'europe'        => array( array( -9, 43 ), array( -9, 38 ), array( -6, 36 ), array( -2, 37 ), array( 3, 42 ), array( 8, 44 ), array( 12, 44 ), array( 16, 38 ), array( 18, 40 ), array( 13, 45 ), array( 19, 42 ), array( 22, 37 ), array( 26, 40 ), array( 29, 41 ), array( 30, 45 ), array( 38, 47 ), array( 45, 45 ), array( 50, 47 ), array( 55, 50 ), array( 55, 62 ), array( 30, 62 ), array( 28, 60 ), array( 22, 58 ), array( 20, 55 ), array( 14, 54 ), array( 10, 54 ), array( 8, 57 ), array( 5, 58 ), array( 5, 62 ), array( 4, 62 ), array( 4, 52 ), array( 2, 51 ), array( -2, 48 ), array( -5, 48 ), array( -1, 46 ), array( -2, 43 ) ),
			'britain'       => array( array( -5, 50 ), array( 1, 51 ), array( 2, 53 ), array( -2, 56 ), array( -2, 58 ), array( -6, 58 ), array( -5, 54 ), array( -3, 54 ), array( -5, 52 ) ),
			'ireland'       => array( array( -10, 52 ), array( -6, 52 ), array( -6, 55 ), array( -8, 55 ), array( -10, 54 ) ),
			'africa'        => array( array( -17, 21 ), array( -16, 28 ), array( -10, 30 ), array( -6, 36 ), array( 10, 37 ), array( 11, 33 ), array( 20, 32 ), array( 25, 32 ), array( 32, 31 ), array( 34, 28 ), array( 37, 22 ), array( 39, 15 ), array( 43, 12 ), array( 51, 12 ), array( 51, 10 ), array( 44, 1 ), array( 41, -2 ), array( 40, -10 ), array( 40, -15 ), array( 35, -24 ), array( 33, -29 ), array( 28, -33 ), array( 20, -35 ), array( 18, -32 ), array( 14, -23 ), array( 12, -17 ), array( 13, -11 ), array( 9, -1 ), array( 9, 4 ), array( 5, 5 ), array( -4, 5 ), array( -8, 4 ), array( -13, 8 ), array( -17, 14 ) ),
			'madagascar'    => array( array( 44, -25 ), array( 47, -25 ), array( 50, -15 ), array( 49, -12 ), array( 44, -16 ) ),
			'arabia'        => array( array( 34, 28 ), array( 36, 33 ), array( 42, 37 ), array( 55, 37 ), array( 55, 25 ), array( 57, 23 ), array( 52, 16 ), array( 43, 12 ), array( 39, 15 ), array( 37, 22 ), array( 35, 26 ) ),
		);
	}

	/**
	 * Map position for a longitude and latitude.
	 *
	 * @param float $lon Longitude.
	 * @param float $lat Latitude.
	 * @return array{x: int, y: int}
	 */
	public static function project( float $lon, float $lat ): array {
		return array(
			'x' => (int) round( ( $lon - self::WEST ) / ( self::EAST - self::WEST ) * self::WIDTH ),
			'y' => (int) round( ( self::NORTH - $lat ) / ( self::NORTH - self::SOUTH ) * self::HEIGHT ),
		);
	}

	/**
	 * Map positions of every land dot, cached.
	 *
	 * @return array<int, array{0: int, 1: int}>
	 */
	public static function dots(): array {
		$cached = get_transient( 'daguah_map_dots_v1' );

		if ( is_array( $cached ) ) {
			return $cached;
		}

		$dots = array();

		for ( $lat = self::NORTH - self::STEP / 2; $lat > self::SOUTH; $lat -= self::STEP ) {
			for ( $lon = self::WEST + self::STEP / 2; $lon < self::EAST; $lon += self::STEP ) {
				foreach ( self::land() as $shape ) {
					if ( self::inside( $lon, $lat, $shape ) ) {
						$point  = self::project( $lon, $lat );
						$dots[] = array( $point['x'], $point['y'] );
						break;
					}
				}
			}
		}

		set_transient( 'daguah_map_dots_v1', $dots, MONTH_IN_SECONDS );

		return $dots;
	}

	/**
	 * Ray casting point in polygon.
	 *
	 * @param float $x     Longitude.
	 * @param float $y     Latitude.
	 * @param array $shape Polygon.
	 */
	private static function inside( float $x, float $y, array $shape ): bool {
		$in    = false;
		$count = count( $shape );

		for ( $i = 0, $j = $count - 1; $i < $count; $j = $i++ ) {
			list( $xi, $yi ) = $shape[ $i ];
			list( $xj, $yj ) = $shape[ $j ];

			if ( ( $yi > $y ) !== ( $yj > $y ) && $x < ( $xj - $xi ) * ( $y - $yi ) / ( ( $yj - $yi ) ?: 1e-9 ) + $xi ) {
				$in = ! $in;
			}
		}

		return $in;
	}

	/**
	 * Towns pieces are made in: name to [lon, lat].
	 *
	 * @return array<string, array{0: float, 1: float}>
	 */
	public static function towns(): array {
		return array(
			'Abeokuta'    => array( 3.35, 7.15 ),
			'Lagos'       => array( 3.38, 6.52 ),
			'Ibadan'      => array( 3.9, 7.38 ),
			'Zaria'       => array( 7.7, 11.07 ),
			'Kano'        => array( 8.52, 12.0 ),
			'Abuja'       => array( 7.49, 9.06 ),
			'Bonwire'     => array( -1.43, 6.78 ),
			'Kumasi'      => array( -1.62, 6.69 ),
			'Accra'       => array( -0.19, 5.6 ),
			'Nairobi'     => array( 36.82, -1.29 ),
			'Mombasa'     => array( 39.67, -4.04 ),
			'Addis Ababa' => array( 38.75, 9.03 ),
			'Kinshasa'    => array( 15.3, -4.32 ),
			'Dakar'       => array( -17.45, 14.69 ),
			'Bamako'      => array( -8.0, 12.64 ),
			'Brussels'    => array( 4.35, 50.85 ),
			'London'      => array( -0.13, 51.5 ),
			'Washington'  => array( -77.04, 38.9 ),
		);
	}

	/**
	 * The main town for a country, when a piece's own town is not on the list.
	 *
	 * @param string $country ISO code.
	 */
	public static function capital( string $country ): string {
		$map = array(
			'NG' => 'Lagos',
			'GH' => 'Accra',
			'KE' => 'Nairobi',
			'ET' => 'Addis Ababa',
			'CD' => 'Kinshasa',
			'SN' => 'Dakar',
			'ML' => 'Bamako',
			'GB' => 'London',
			'BE' => 'Brussels',
			'US' => 'Washington',
		);

		return $map[ strtoupper( $country ) ] ?? 'Lagos';
	}

	/**
	 * Where buyers are: country to cities, each [name, lon, lat]. The first
	 * city is the default.
	 *
	 * @return array<string, array<int, array{0: string, 1: float, 2: float}>>
	 */
	public static function buyer_cities(): array {
		return array(
			'GB' => array( array( 'London', -0.13, 51.5 ), array( 'Croydon', -0.1, 51.37 ), array( 'Manchester', -2.24, 53.48 ), array( 'Birmingham', -1.9, 52.48 ), array( 'Leeds', -1.55, 53.8 ) ),
			'US' => array( array( 'Houston', -95.37, 29.76 ), array( 'New York', -74.0, 40.71 ), array( 'Washington', -77.04, 38.9 ), array( 'Atlanta', -84.39, 33.75 ), array( 'Minneapolis', -93.27, 44.98 ) ),
			'CA' => array( array( 'Toronto', -79.38, 43.65 ), array( 'Montreal', -73.57, 45.5 ), array( 'Calgary', -114.07, 51.05 ), array( 'Ottawa', -75.7, 45.42 ) ),
			'IE' => array( array( 'Dublin', -6.26, 53.35 ) ),
			'BE' => array( array( 'Brussels', 4.35, 50.85 ), array( 'Antwerp', 4.4, 51.22 ) ),
			'FR' => array( array( 'Paris', 2.35, 48.86 ), array( 'Lyon', 4.84, 45.76 ) ),
			'DE' => array( array( 'Berlin', 13.4, 52.52 ), array( 'Hamburg', 9.99, 53.55 ) ),
			'NL' => array( array( 'Amsterdam', 4.9, 52.37 ), array( 'Rotterdam', 4.48, 51.92 ) ),
			'IT' => array( array( 'Rome', 12.5, 41.9 ), array( 'Milan', 9.19, 45.46 ) ),
			'ES' => array( array( 'Madrid', -3.7, 40.42 ), array( 'Barcelona', 2.17, 41.39 ) ),
			'NG' => array( array( 'Lagos', 3.38, 6.52 ), array( 'Abuja', 7.49, 9.06 ), array( 'Port Harcourt', 7.0, 4.82 ) ),
			'GH' => array( array( 'Accra', -0.19, 5.6 ), array( 'Kumasi', -1.62, 6.69 ) ),
			'KE' => array( array( 'Nairobi', 36.82, -1.29 ), array( 'Mombasa', 39.67, -4.04 ) ),
		);
	}

	/**
	 * Countries a buyer can choose, with their currency and zone.
	 *
	 * @return array<string, array{name: string, currency: string, zone: string}>
	 */
	public static function buyer_countries(): array {
		$list = array();

		foreach ( array_keys( self::buyer_cities() ) as $code ) {
			$list[ $code ] = array(
				'name'     => Countries::name( $code ),
				'currency' => Countries::currency_for( $code ),
				'zone'     => (string) Countries::zone_for( $code ),
			);
		}

		$list['IT']['name'] = __( 'Italy', 'daguah' );
		$list['ES']['name'] = __( 'Spain', 'daguah' );

		return $list;
	}
}
