<?php
/**
 * REST endpoints, namespace daguah/v1.
 *
 *   GET  /fit-profile              The signed in buyer's measurements.
 *   POST /fit-profile              Save some or all of them.
 *   GET  /circles/{id}/board       Who is in a circle and who has paid.
 *   GET  /currency/rates           Rates from GBP, and whether they are demo rates.
 *   GET  /landed-price             Item, delivery and duties for a country.
 *
 * Signed in requests use WordPress cookie authentication, which requires the
 * X-WP-Nonce header (a request without it is treated as signed out). Guests
 * keep their fit profile in the browser, so the fit profile routes need a
 * signed in user. Every argument has a schema, so WordPress rejects bad
 * input before the callbacks run.
 *
 * @package Daguah\Core
 */

declare( strict_types=1 );

namespace Daguah\Core;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

defined( 'ABSPATH' ) || exit;

/**
 * Route registration and callbacks.
 */
final class Rest {

	public const NS          = 'daguah/v1';
	public const FIT_PROFILE = 'daguah_fit_profile';

	/**
	 * Hooks.
	 */
	public static function init(): void {
		add_action( 'rest_api_init', array( self::class, 'routes' ) );
	}

	/**
	 * Register routes.
	 */
	public static function routes(): void {
		$measurement = array(
			'type'    => array( 'number', 'null' ),
			'minimum' => 20,
			'maximum' => 250,
		);

		register_rest_route(
			self::NS,
			'/fit-profile',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( self::class, 'get_fit_profile' ),
					'permission_callback' => array( self::class, 'signed_in' ),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( self::class, 'save_fit_profile' ),
					'permission_callback' => array( self::class, 'signed_in' ),
					'args'                => array_merge(
						array_fill_keys( array_keys( Product_Data::measurement_labels() ), $measurement ),
						array(
							'unit' => array(
								'type' => 'string',
								'enum' => array( 'cm', 'in' ),
							),
						)
					),
				),
			)
		);

		register_rest_route(
			self::NS,
			'/circles/(?P<id>\d+)/board',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( self::class, 'circle_board' ),
				'permission_callback' => array( self::class, 'can_see_circle' ),
				'args'                => array(
					'id'  => array(
						'type'     => 'integer',
						'minimum'  => 1,
						'required' => true,
					),
					'key' => array(
						'type'      => 'string',
						'maxLength' => 64,
						'pattern'   => '^[A-Za-z0-9]*$',
					),
				),
			)
		);

		register_rest_route(
			self::NS,
			'/saved-searches',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => static fn() => self::private_response( Shop_Query::saved( get_current_user_id() ) ),
					'permission_callback' => array( self::class, 'signed_in' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( self::class, 'save_search' ),
					'permission_callback' => array( self::class, 'signed_in' ),
					'args'                => array(
						'query' => array(
							'type'      => 'string',
							'maxLength' => 2000,
							'default'   => '',
						),
					),
				),
			)
		);

		register_rest_route(
			self::NS,
			'/saved-searches/(?P<id>[A-Za-z0-9]{1,32})',
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => static fn( WP_REST_Request $r ) => Shop_Query::remove( get_current_user_id(), (string) $r['id'] )
					? self::private_response( array( 'removed' => true ) )
					: new WP_Error( 'daguah_no_saved_search', __( 'That saved search was already removed.', 'daguah' ), array( 'status' => 404 ) ),
				'permission_callback' => array( self::class, 'signed_in' ),
			)
		);

		register_rest_route(
			self::NS,
			'/moments/(?P<id>\d+)/collection',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( self::class, 'moment_collection' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'id' => array(
						'type'     => 'integer',
						'minimum'  => 1,
						'required' => true,
					),
				),
			)
		);

		register_rest_route(
			self::NS,
			'/currency/rates',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => static fn() => self::cached( Pricing::rates(), 600 ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			self::NS,
			'/landed-price',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( self::class, 'landed_price' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'product'  => array(
						'type'     => 'integer',
						'minimum'  => 1,
						'required' => true,
					),
					'country'  => array(
						'type'     => 'string',
						'pattern'  => '^[A-Za-z]{2}$',
						'required' => true,
					),
					'currency' => array(
						'type' => 'string',
						'enum' => Countries::CURRENCIES,
					),
					'quantity' => array(
						'type'    => 'integer',
						'minimum' => 1,
						'maximum' => 20,
						'default' => 1,
					),
				),
			)
		);
	}

	/* Permissions ---------------------------------------------------------- */

	/**
	 * Signed in, with a valid REST nonce.
	 */
	public static function signed_in(): bool|WP_Error {
		return is_user_logged_in()
			? true
			: new WP_Error( 'daguah_signed_out', __( 'Sign in to save your fit profile to your account. It is kept on this device until then.', 'daguah' ), array( 'status' => 401 ) );
	}

	/**
	 * A circle can be seen by its host and members, by editors, and by
	 * anyone holding its invite key.
	 *
	 * @param WP_REST_Request $request Request.
	 */
	public static function can_see_circle( WP_REST_Request $request ): bool|WP_Error {
		// Same answer whether the circle exists or not, so ids cannot be probed.
		return Circles::can_see( (int) $request['id'], (string) $request['key'], get_current_user_id() )
			? true
			: new WP_Error( 'daguah_no_circle', __( 'That circle does not exist or the link has expired.', 'daguah' ), array( 'status' => 404 ) );
	}

	/* Fit profile ---------------------------------------------------------- */

	/**
	 * The signed in buyer's fit profile.
	 */
	public static function get_fit_profile(): WP_REST_Response {
		return self::private_response( self::present_fit_profile( self::read_fit_profile( get_current_user_id() ) ) );
	}

	/**
	 * Measurements as a JSON object even when empty.
	 *
	 * @param array $profile Profile from read_fit_profile().
	 */
	private static function present_fit_profile( array $profile ): array {
		$profile['measurements'] = (object) $profile['measurements'];

		return $profile;
	}

	/**
	 * Save measurements sent in the request. Measurements left out are kept;
	 * a measurement sent as null is removed.
	 *
	 * @param WP_REST_Request $request Request.
	 */
	public static function save_fit_profile( WP_REST_Request $request ): WP_REST_Response {
		$user_id = get_current_user_id();
		$profile = self::read_fit_profile( $user_id );
		$params  = $request->get_params();

		foreach ( array_keys( Product_Data::measurement_labels() ) as $key ) {
			if ( ! array_key_exists( $key, $params ) ) {
				continue;
			}

			if ( null === $params[ $key ] ) {
				unset( $profile['measurements'][ $key ] );
			} else {
				$profile['measurements'][ $key ] = round( (float) $params[ $key ], 1 );
			}
		}

		if ( isset( $params['unit'] ) ) {
			$profile['unit'] = (string) $params['unit'];
		}

		$profile['updated'] = gmdate( 'c' );

		update_user_meta( $user_id, self::FIT_PROFILE, $profile );

		$response = self::private_response( self::present_fit_profile( $profile ) );
		$response->header( 'X-Daguah-Message', rawurlencode( __( 'Saved to your fit profile.', 'daguah' ) ) );

		return $response;
	}

	/**
	 * Stored profile, cleaned. Measurements are always in centimetres; unit
	 * is only how the buyer likes to see them.
	 *
	 * @param int $user_id User ID.
	 */
	private static function read_fit_profile( int $user_id ): array {
		$stored = get_user_meta( $user_id, self::FIT_PROFILE, true );
		$stored = is_array( $stored ) ? $stored : array();

		$measurements = array();
		foreach ( array_keys( Product_Data::measurement_labels() ) as $key ) {
			$value = $stored['measurements'][ $key ] ?? null;
			if ( is_numeric( $value ) && $value >= 20 && $value <= 250 ) {
				$measurements[ $key ] = round( (float) $value, 1 );
			}
		}

		return array(
			'unit'         => 'in' === ( $stored['unit'] ?? '' ) ? 'in' : 'cm',
			'measurements' => $measurements,
			'updated'      => (string) ( $stored['updated'] ?? '' ),
		);
	}

	/* Circle board --------------------------------------------------------- */

	/**
	 * Circle members with first names only, their city and whether they have
	 * paid. The board polls this every 20 seconds while the tab is visible.
	 *
	 * @param WP_REST_Request $request Request.
	 */
	public static function circle_board( WP_REST_Request $request ): WP_REST_Response {
		return self::private_response( Circles::board( (int) $request['id'] ) );
	}

	/* Saved searches ------------------------------------------------------- */

	/**
	 * Save the shop filters from a query string.
	 *
	 * @param WP_REST_Request $request Request.
	 */
	public static function save_search( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		parse_str( (string) $request['query'], $params );
		$search = is_string( $params['s'] ?? null ) ? $params['s'] : '';

		$saved = Shop_Query::save( get_current_user_id(), $params, $search );

		if ( null === $saved ) {
			return new WP_Error( 'daguah_saved_search_full', __( 'You have 20 saved searches already. Remove one from your account to save another.', 'daguah' ), array( 'status' => 409 ) );
		}

		$response = self::private_response( $saved );
		$response->set_status( 201 );

		return $response;
	}

	/* Calendar collection -------------------------------------------------- */

	/**
	 * The product cards for one calendar moment, ready to swap in.
	 *
	 * @param WP_REST_Request $request Request.
	 */
	public static function moment_collection( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$id = (int) $request['id'];

		foreach ( Calendar::upcoming( 50 ) as $moment ) {
			if ( $moment['id'] === $id ) {
				return self::cached(
					array(
						'id'    => $id,
						'title' => $moment['title'],
						'dates' => Calendar::date_label( $moment ),
						'link'  => $moment['link'],
						'html'  => Product_Card::grid( Calendar::collection_ids( $moment ) ),
					),
					300
				);
			}
		}

		return new WP_Error( 'daguah_no_moment', __( 'That moment is not on the calendar.', 'daguah' ), array( 'status' => 404 ) );
	}

	/* Landed price --------------------------------------------------------- */

	/**
	 * Landed price for one product and one destination.
	 *
	 * @param WP_REST_Request $request Request.
	 */
	public static function landed_price( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$result = Pricing::landed(
			(int) $request['product'],
			(string) $request['country'],
			(string) ( $request['currency'] ?? '' ),
			(int) $request['quantity']
		);

		return is_wp_error( $result ) ? $result : self::cached( $result, 300 );
	}

	/* Responses ------------------------------------------------------------ */

	/**
	 * A response a shared cache may keep for a few minutes.
	 *
	 * @param array $data    Body.
	 * @param int   $seconds Max age.
	 */
	private static function cached( array $data, int $seconds ): WP_REST_Response {
		$response = new WP_REST_Response( $data );
		$response->header( 'Cache-Control', 'public, max-age=' . $seconds );

		return $response;
	}

	/**
	 * A response that must never be cached.
	 *
	 * @param array $data Body.
	 */
	private static function private_response( array $data ): WP_REST_Response {
		$response = new WP_REST_Response( $data );
		$response->header( 'Cache-Control', 'private, no-store' );

		return $response;
	}
}
