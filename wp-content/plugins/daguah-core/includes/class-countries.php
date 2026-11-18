<?php
/**
 * Countries, delivery zones and currencies.
 *
 * A delivery corridor runs from the country a piece ships from to a delivery
 * zone. Zones are the launch markets: six countries plus the European Union,
 * which is treated as one zone for delivery and duties.
 *
 * @package Daguah\Core
 */

declare( strict_types=1 );

namespace Daguah\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Lookup tables. Everything here is static data.
 */
final class Countries {

	/**
	 * Currencies a buyer can see prices in. GBP is the store's base currency.
	 */
	public const CURRENCIES = array( 'GBP', 'USD', 'CAD', 'EUR', 'NGN', 'GHS', 'KES' );

	public const BASE_CURRENCY = 'GBP';

	/**
	 * European Union member states (ISO 3166 alpha 2).
	 */
	public const EU = array( 'AT', 'BE', 'BG', 'HR', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR', 'DE', 'GR', 'HU', 'IE', 'IT', 'LV', 'LT', 'LU', 'MT', 'NL', 'PL', 'PT', 'RO', 'SK', 'SI', 'ES', 'SE' );

	/**
	 * Delivery zones at launch.
	 *
	 * @return array<string, string> Zone code to name.
	 */
	public static function zones(): array {
		return array(
			'GB' => __( 'United Kingdom', 'daguah' ),
			'US' => __( 'United States', 'daguah' ),
			'CA' => __( 'Canada', 'daguah' ),
			'EU' => __( 'European Union', 'daguah' ),
			'NG' => __( 'Nigeria', 'daguah' ),
			'GH' => __( 'Ghana', 'daguah' ),
			'KE' => __( 'Kenya', 'daguah' ),
		);
	}

	/**
	 * Countries a piece can ship from or be made in.
	 *
	 * @return array<string, string> ISO code to name.
	 */
	public static function origins(): array {
		return array(
			'NG' => __( 'Nigeria', 'daguah' ),
			'GH' => __( 'Ghana', 'daguah' ),
			'KE' => __( 'Kenya', 'daguah' ),
			'ET' => __( 'Ethiopia', 'daguah' ),
			'ER' => __( 'Eritrea', 'daguah' ),
			'CD' => __( 'DR Congo', 'daguah' ),
			'CI' => __( 'Côte d\'Ivoire', 'daguah' ),
			'SN' => __( 'Senegal', 'daguah' ),
			'ML' => __( 'Mali', 'daguah' ),
			'ZA' => __( 'South Africa', 'daguah' ),
			'RW' => __( 'Rwanda', 'daguah' ),
			'GB' => __( 'United Kingdom', 'daguah' ),
			'US' => __( 'United States', 'daguah' ),
			'CA' => __( 'Canada', 'daguah' ),
			'BE' => __( 'Belgium', 'daguah' ),
			'FR' => __( 'France', 'daguah' ),
			'IE' => __( 'Ireland', 'daguah' ),
			'NL' => __( 'Netherlands', 'daguah' ),
			'DE' => __( 'Germany', 'daguah' ),
		);
	}

	/**
	 * The delivery zone a country belongs to, or null if DAGUAH does not ship there yet.
	 *
	 * @param string $country ISO 3166 alpha 2 code.
	 */
	public static function zone_for( string $country ): ?string {
		$country = strtoupper( $country );

		if ( in_array( $country, self::EU, true ) ) {
			return 'EU';
		}

		return array_key_exists( $country, self::zones() ) && 'EU' !== $country ? $country : null;
	}

	/**
	 * The currency a buyer in this country most likely pays in.
	 *
	 * @param string $country ISO 3166 alpha 2 code.
	 */
	public static function currency_for( string $country ): string {
		$map = array(
			'GB' => 'GBP',
			'US' => 'USD',
			'CA' => 'CAD',
			'EU' => 'EUR',
			'NG' => 'NGN',
			'GH' => 'GHS',
			'KE' => 'KES',
		);

		return $map[ self::zone_for( $country ) ?? '' ] ?? 'USD';
	}

	/**
	 * Is this a currency DAGUAH shows prices in?
	 *
	 * @param string $currency ISO 4217 code.
	 */
	public static function is_currency( string $currency ): bool {
		return in_array( strtoupper( $currency ), self::CURRENCIES, true );
	}

	/**
	 * Country name from its code, for display.
	 *
	 * @param string $code ISO code or EU.
	 */
	public static function name( string $code ): string {
		$code = strtoupper( $code );

		return self::origins()[ $code ] ?? self::zones()[ $code ] ?? $code;
	}
}
