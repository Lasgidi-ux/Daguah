<?php
/**
 * The shared shop script and its settings.
 *
 * Registered as `daguah-shop`. Blocks that need it list it as their view
 * script, and the product card enqueues it, so it loads only on pages with
 * pieces, prices or the Fit Mirror on them.
 *
 * @package Daguah\Core
 */

declare( strict_types=1 );

namespace Daguah\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Script registration and front end settings.
 */
final class Shop_Assets {

	public const HANDLE = 'daguah-shop';

	/**
	 * Hooks. Registered early so blocks can name the handle.
	 */
	public static function init(): void {
		add_action( 'init', array( self::class, 'register' ), 5 );
	}

	/**
	 * Register the script with its settings.
	 */
	public static function register(): void {
		wp_register_style( self::HANDLE, DAGUAH_CORE_URL . 'assets/shop.css', array(), DAGUAH_CORE_VERSION );

		$asset_path = DAGUAH_CORE_DIR . 'build/shop.asset.php';
		$asset      = is_readable( $asset_path ) ? require $asset_path : array();

		wp_register_script(
			self::HANDLE,
			DAGUAH_CORE_URL . 'build/shop.js',
			(array) ( $asset['dependencies'] ?? array() ),
			(string) ( $asset['version'] ?? DAGUAH_CORE_VERSION ),
			array(
				'strategy'  => 'defer',
				'in_footer' => true,
			)
		);

		// Stage 5 blocks: stalls, occasions, Circle, Send Home, Market Live.
		wp_register_style( 'daguah-market', DAGUAH_CORE_URL . 'assets/market.css', array( self::HANDLE ), DAGUAH_CORE_VERSION );

		foreach ( array( 'daguah-local-time' => 'live', 'daguah-circle' => 'circle' ) as $handle => $file ) {
			$path  = DAGUAH_CORE_DIR . 'build/' . $file . '.asset.php';
			$build = is_readable( $path ) ? require $path : array();

			wp_register_script(
				$handle,
				DAGUAH_CORE_URL . 'build/' . $file . '.js',
				(array) ( $build['dependencies'] ?? array() ),
				(string) ( $build['version'] ?? DAGUAH_CORE_VERSION ),
				array(
					'strategy'  => 'defer',
					'in_footer' => true,
				)
			);
			wp_set_script_translations( $handle, 'daguah' );
		}

		// Settings are added when the script is printed, so the nonce is fresh
		// for signed in visitors and pages for everyone else stay cacheable.
		add_action(
			'wp_print_footer_scripts',
			static function (): void {
				if ( wp_script_is( self::HANDLE, 'enqueued' ) && ! wp_script_is( self::HANDLE, 'done' ) ) {
					wp_add_inline_script( self::HANDLE, 'window.daguahShop = ' . wp_json_encode( self::settings() ) . ';', 'before' );
				}
			},
			1
		);
	}

	/**
	 * Load the script on this page.
	 */
	public static function enqueue(): void {
		wp_enqueue_script( self::HANDLE );
		wp_enqueue_style( self::HANDLE );
	}

	/**
	 * Settings and wording for the script.
	 */
	public static function settings(): array {
		$logged_in = is_user_logged_in();

		return array(
			'rest'       => esc_url_raw( rest_url( 'daguah/v1/' ) ),
			'nonce'      => $logged_in ? wp_create_nonce( 'wp_rest' ) : '',
			'loggedIn'   => $logged_in,
			'rates'      => Pricing::rates(),
			'currencies' => Countries::CURRENCIES,
			'countries'  => Geo::buyer_countries(),
			'i18n'       => array(
				'demoRates'       => __( 'Demo rates', 'daguah' ),
				'fits'            => __( 'Fits you', 'daguah' ),
				'alter'           => __( 'Needs a small alteration', 'daguah' ),
				'no'              => __( 'Not your size', 'daguah' ),
				'check'           => __( 'Check the fit', 'daguah' ),
				'noOverlap'       => __( 'Add more measurements to check', 'daguah' ),
				'measurements'    => Product_Data::measurement_labels(),
				'same'            => __( 'matches you', 'daguah' ),
				/* translators: %s: a measurement difference such as "3 cm" */
				'bigger'          => __( 'the piece is %s bigger than you', 'daguah' ),
				/* translators: %s: a measurement difference such as "3 cm" */
				'smaller'         => __( 'the piece is %s smaller than you', 'daguah' ),
				'rangeCm'         => __( 'Each measurement needs to be between 20 and 250 cm.', 'daguah' ),
				'rangeIn'         => __( 'Each measurement needs to be between 8 and 98 inches.', 'daguah' ),
				'needOne'         => __( 'Add at least one measurement.', 'daguah' ),
				'savedAccount'    => __( 'Saved to your fit profile.', 'daguah' ),
				'savedDevice'     => __( 'Saved on this device. Sign in to keep it with your account.', 'daguah' ),
				'savedOffline'    => __( 'Saved on this device. Your account copy will update next time you are online.', 'daguah' ),
				'cleared'         => __( 'Your measurements are removed.', 'daguah' ),
				/* translators: %s: country name */
				'noShip'          => __( 'This piece does not ship to %s yet.', 'daguah' ),
				'item'            => __( 'Item', 'daguah' ),
				/* translators: 1: fewest days, 2: most days */
				'delivery'        => __( 'Delivery, %1$d to %2$d days', 'daguah' ),
				'duties'          => __( 'Estimated duties', 'daguah' ),
				'total'           => __( 'At your door', 'daguah' ),
				'demoNote'        => __( 'Demo rates. Duties are an estimate; customs sets the final amount.', 'daguah' ),
				'estimateNote'    => __( 'Duties are an estimate; customs sets the final amount.', 'daguah' ),
				'landedError'     => __( 'The full price did not load.', 'daguah' ),
				'retry'           => __( 'Try again', 'daguah' ),
				/* translators: 1: Dyed, Woven or Made, 2: town, 3: buyer city, 4: fewest days, 5: most days */
				'thread'          => __( '%1$s in %2$s, arriving in %3$s in %4$d to %5$d days.', 'daguah' ),
				/* translators: 1: Dyed, Woven or Made, 2: town, 3: buyer country */
				'threadNoShip'    => __( '%1$s in %2$s. This piece does not ship to %3$s yet.', 'daguah' ),
				'searchSaved'     => __( 'Search saved', 'daguah' ),
				'searchSavedNote' => __( 'We will email you when new pieces match.', 'daguah' ),
				'searchFull'      => __( 'You have 20 saved searches. Remove one from your account first.', 'daguah' ),
				'searchError'     => __( 'The search was not saved. Try again.', 'daguah' ),
			),
		);
	}
}
