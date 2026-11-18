<?php
/**
 * Currency rates and the landed price.
 *
 * The landed price is what a buyer pays to have a piece at their door:
 * item, delivery on the corridor to their country, and estimated duties,
 * each converted to their currency. Every line is rounded before the total
 * is added up, so the lines on screen always add up to the total.
 *
 * Integration points:
 *   daguah_currency_rates  Replace the rates (for example from the multi
 *                          currency plugin). Keep GBP as the base.
 *   daguah_duties_estimate Replace the duties estimate (for example with a
 *                          call to Zonos). Receives the demo estimate and
 *                          the context, returns the same shape.
 *
 * @package Daguah\Core
 */

declare( strict_types=1 );

namespace Daguah\Core;

use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Rates, conversion and landed price.
 */
final class Pricing {

	/**
	 * Rates from GBP to each supported currency, and where they came from.
	 *
	 * @return array{base: string, source: string, label: string, rates: array<string, float>}
	 */
	public static function rates(): array {
		$demo = (array) Settings::get( 'demo_rates' );

		$data = array(
			'base'   => Countries::BASE_CURRENCY,
			'source' => 'demo',
			'label'  => __( 'Demo rates', 'daguah' ),
			'rates'  => array( 'GBP' => 1.0 ) + array_map( 'floatval', $demo ),
		);

		if ( 'plugin' === Settings::get( 'currency_source' ) ) {
			/**
			 * Rates from the multi currency plugin. Return the same shape with
			 * source "plugin" and an empty label once they are wired up.
			 *
			 * @param array $data Demo rates, as a fallback.
			 */
			$data = (array) apply_filters( 'daguah_currency_rates', $data );
		}

		return $data;
	}

	/**
	 * Convert an amount in pounds.
	 *
	 * @param float  $gbp      Amount in GBP.
	 * @param string $currency Target currency.
	 */
	public static function convert( float $gbp, string $currency ): float {
		$rate = self::rates()['rates'][ strtoupper( $currency ) ] ?? null;

		return null === $rate ? $gbp : round( $gbp * (float) $rate, 2 );
	}

	/**
	 * The landed price of a product delivered to a country.
	 *
	 * @param int    $product_id Product ID.
	 * @param string $country    Buyer's country, ISO alpha 2.
	 * @param string $currency   Currency to show, or empty for the country's usual one.
	 * @param int    $quantity   Number of pieces.
	 * @return array|WP_Error
	 */
	public static function landed( int $product_id, string $country, string $currency = '', int $quantity = 1 ) {
		$product = function_exists( 'wc_get_product' ) ? wc_get_product( $product_id ) : null;

		if ( ! $product || 'publish' !== $product->get_status() || ! $product->is_visible() ) {
			return new WP_Error( 'daguah_no_product', __( 'That piece is not available.', 'daguah' ), array( 'status' => 404 ) );
		}

		$country  = strtoupper( $country );
		$currency = strtoupper( '' !== $currency ? $currency : Countries::currency_for( $country ) );

		if ( ! Countries::is_currency( $currency ) ) {
			return new WP_Error( 'daguah_bad_currency', __( 'Prices are not shown in that currency yet.', 'daguah' ), array( 'status' => 400 ) );
		}

		$quantity = max( 1, min( 20, $quantity ) );
		$zone     = Countries::zone_for( $country );
		$rates    = self::rates();
		$item_gbp = round( (float) $product->get_price() * $quantity, 2 );
		$item     = self::convert( $item_gbp, $currency );

		$result = array(
			'product'  => $product_id,
			'country'  => $country,
			'zone'     => $zone,
			'currency' => $currency,
			'quantity' => $quantity,
			'ships'    => false,
			'item'     => $item,
			'delivery' => null,
			'duties'   => null,
			'total'    => null,
			'rates'    => array(
				'source' => $rates['source'],
				'label'  => $rates['label'],
			),
		);

		$corridor = $zone ? Product_Data::corridor_to( $product_id, $zone ) : null;

		if ( ! $corridor ) {
			return $result;
		}

		$delivery_gbp = (float) $corridor['price'];
		$domestic     = Countries::zone_for( $corridor['from'] ) === $zone;
		$duties       = self::duties_estimate( (string) $zone, $item_gbp + $delivery_gbp, $domestic );

		/**
		 * Replace the duties estimate, for example with a call to Zonos.
		 *
		 * @param array $duties  { amount_gbp: float, rate: float, source: string }.
		 * @param array $context Product, zone, corridor and amounts in GBP.
		 */
		$duties = (array) apply_filters(
			'daguah_duties_estimate',
			$duties,
			array(
				'product_id'   => $product_id,
				'country'      => $country,
				'zone'         => $zone,
				'corridor'     => $corridor,
				'item_gbp'     => $item_gbp,
				'delivery_gbp' => $delivery_gbp,
			)
		);

		$delivery    = self::convert( $delivery_gbp, $currency );
		$duty_amount = self::convert( max( 0.0, (float) ( $duties['amount_gbp'] ?? 0 ) ), $currency );

		$result['ships']    = true;
		$result['delivery'] = array(
			'amount'   => $delivery,
			'from'     => $corridor['from'],
			'to'       => $corridor['to'],
			'min_days' => $corridor['min_days'],
			'max_days' => $corridor['max_days'],
		);
		$result['duties']   = array(
			'amount'    => $duty_amount,
			'rate'      => (float) ( $duties['rate'] ?? 0 ),
			'estimated' => true,
			'source'    => (string) ( $duties['source'] ?? 'demo' ),
		);
		$result['total']    = round( $item + $delivery + $duty_amount, 2 );

		return $result;
	}

	/**
	 * Duties from the settings table. Nothing is charged within a zone or at
	 * or below the zone's threshold.
	 *
	 * @param string $zone     Delivery zone.
	 * @param float  $value    Item and delivery in GBP.
	 * @param bool   $domestic True when it ships within the zone.
	 */
	public static function duties_estimate( string $zone, float $value, bool $domestic ): array {
		$row = ( (array) Settings::get( 'duties' ) )[ $zone ] ?? array( 'rate' => 0.0, 'threshold' => 0.0 );

		if ( $domestic || $value <= (float) $row['threshold'] ) {
			return array( 'amount_gbp' => 0.0, 'rate' => 0.0, 'source' => 'demo' );
		}

		return array(
			'amount_gbp' => round( $value * (float) $row['rate'] / 100, 2 ),
			'rate'       => (float) $row['rate'],
			'source'     => 'demo',
		);
	}
}
