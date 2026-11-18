<?php
/**
 * Stage 2 checks, run inside WordPress by the Playground blueprint.
 *
 * Not part of the plugin. Exercises the seed, the data model, the landed
 * price and every REST route (through rest_do_request, as a signed out
 * visitor and as an administrator), then reseeds to prove the command is
 * idempotent, resets, and seeds again so the site is ready for review.
 *
 * Writes results to wp-content/daguah-results/stage2.json, which the
 * blueprint mounts from .cache/results on the host.
 */

use Daguah\Core\Post_Types;
use Daguah\Core\Pricing;
use Daguah\Core\Product_Data;
use Daguah\Core\Seeder;
use Daguah\Core\Settings;
use Daguah\Core\Vendor;

$results = array();
$check   = static function ( string $name, bool $pass, $detail = '' ) use ( &$results ): void {
	$results[] = array(
		'check'  => $name,
		'pass'   => $pass,
		'detail' => $detail,
	);
};

$admin = (int) ( get_users( array( 'role' => 'administrator', 'number' => 1, 'fields' => 'ID' ) )[0] ?? 0 );

$marked = static function ( string $type, string $status = 'any' ): array {
	return get_posts(
		array(
			'post_type'   => $type,
			'post_status' => $status,
			'numberposts' => -1,
			'fields'      => 'ids',
			'meta_key'    => Seeder::MARK,
		)
	);
};

$seller_count = static fn() => count( get_users( array( 'meta_key' => Seeder::MARK, 'fields' => 'ID' ) ) );

$rest = static function ( string $method, string $route, array $params = array(), int $user = 0 ) {
	wp_set_current_user( $user );
	$request = new WP_REST_Request( $method, $route );
	foreach ( $params as $key => $value ) {
		$request->set_param( $key, $value );
	}
	$response = rest_do_request( $request );
	$server   = rest_get_server();

	// Round trip through JSON so the body reads exactly as a browser would get it.
	return array( $response->get_status(), json_decode( (string) wp_json_encode( $server->response_to_data( $response, false ) ), true ) );
};

$by_key = static function ( string $type, string $key ): int {
	$found = get_posts(
		array(
			'post_type'   => $type,
			'post_status' => 'any',
			'numberposts' => 1,
			'fields'      => 'ids',
			'meta_key'    => Seeder::MARK,
			'meta_value'  => $key,
		)
	);

	return (int) ( $found[0] ?? 0 );
};

/* Seeded content ------------------------------------------------------------ */

$check( '24 pieces seeded', 24 === count( $marked( 'product' ) ), count( $marked( 'product' ) ) );
$check( '8 stalls seeded, all Dokan sellers', 8 === $seller_count() && 8 === count( array_filter( get_users( array( 'meta_key' => Seeder::MARK, 'fields' => 'ID' ) ), static fn( $id ) => dokan_is_user_seller( (int) $id ) ) ), $seller_count() );
$check( '10 calendar moments', 10 === count( $marked( Post_Types::MOMENT ) ) );
$check( '11 occasions', 11 === (int) wp_count_terms( array( 'taxonomy' => 'occasion', 'hide_empty' => false ) ) );

$circle = $by_key( Post_Types::CIRCLE, 'sample-circle' );
$key    = (string) get_post_meta( $circle, Post_Types::INVITE_KEY, true );
$check( 'Circle is private with a 32 character invite key', 'private' === get_post_status( $circle ) && 32 === strlen( $key ), get_post_status( $circle ) );
$check( 'Live session published with 3 pieces in the tray', 3 === count( (array) get_post_meta( $by_key( Post_Types::LIVE, 'makola-live' ), 'daguah_products', true ) ) );
$check( 'Journal article published', 'publish' === get_post_status( $by_key( 'post', 'journal-kente' ) ) );

$missing_pages = array_filter( array( 'system', 'circle', 'send-home', 'live', 'occasions', 'stalls', 'open-your-stall', 'help', 'journal', 'about', 'contact', 'shipping', 'returns', 'terms', 'privacy', 'credits', 'shop' ), static fn( $slug ) => ! get_page_by_path( $slug ) );
$check( 'Every linked page exists, including the WooCommerce shop', ! $missing_pages, implode( ', ', $missing_pages ) );
$front_slug = WP_Block_Patterns_Registry::get_instance()->is_registered( 'daguah/home' ) ? 'home' : 'system';
$check( 'Front page is Home once the theme has it, the System sheet before', (int) get_option( 'page_on_front' ) === (int) get_page_by_path( $front_slug )->ID, $front_slug );
$check( 'Store currency is GBP', 'GBP' === get_woocommerce_currency() );

/* Product data ----------------------------------------------------------- */

$skirt = $by_key( 'product', 'adire-wrap-skirt' );
$data  = Product_Data::get( $skirt );
$iya   = (int) get_users( array( 'meta_key' => Seeder::MARK, 'meta_value' => 'iya-adire', 'fields' => 'ID' ) )[0];

$check( 'Skirt measurements stored in cm', array( 'waist' => 74.0, 'hip' => 102.0, 'length' => 96.0 ) == $data['measurements'], $data['measurements'] );
$check( 'Skirt has 7 delivery corridors from Nigeria', 7 === count( $data['corridors'] ) && 'NG' === $data['corridors'][0]['from'] );
$check( 'Skirt attributes: Adire, New, M', array( 'Adire' ) === $data['fabric'] && array( 'New' ) === $data['condition'] && array( 'M' ) === $data['size'], array( $data['fabric'], $data['condition'], $data['size'] ) );
$check( 'Skirt origin is NG, Abeokuta', 'NG' === $data['origin_country'] && 'Abeokuta' === $data['origin_town'] );
$check( 'Skirt is sold by Ìyá Àdìrẹ Studio', (int) get_post_field( 'post_author', $skirt ) === $iya && 'Ìyá Àdìrẹ Studio' === Vendor::store_name( $iya ) );

$agbada = Product_Data::get( $by_key( 'product', 'agbada-cream-damask' ) );
$check( 'Preloved agbada carries a 2 line Heritage Passport', 2 === count( $agbada['passport'] ) );

$aso = Product_Data::get( $by_key( 'product', 'aso-oke-gele-coral' ) );
$check( 'Aso oke set ships from Nigeria though the stall is in London', 'NG' === $aso['corridors'][0]['from'] );

$check( 'Corridor cleaning drops unknown countries and fixes max below min', array( array( 'from' => 'NG', 'to' => 'GB', 'min_days' => 9, 'max_days' => 9, 'price' => 3.5 ) ) === Product_Data::clean_corridors( array( array( 'from' => 'NG', 'to' => 'GB', 'min_days' => 9, 'max_days' => 2, 'price' => '3.499' ), array( 'from' => 'XX', 'to' => 'GB' ), array( 'from' => 'NG', 'to' => 'JP' ) ) ) );
$check( 'Video links only from allowed hosts', '' === Product_Data::clean_video_url( 'https://evil.example/x' ) && '' !== Product_Data::clean_video_url( 'https://www.youtube.com/watch?v=abc' ) && '' === Product_Data::clean_video_url( 'http://www.youtube.com/watch?v=abc' ) );

/* Stalls and cloth --------------------------------------------------------- */

$family = static fn( string $key ) => Vendor::get( (int) get_users( array( 'meta_key' => Seeder::MARK, 'meta_value' => $key, 'fields' => 'ID' ) )[0] )['cloth_family'];
$check( 'Cloth families follow region', 'adire' === $family( 'iya-adire' ) && 'kente' === $family( 'bonwire' ) && 'kuba' === $family( 'kinshasa-wax' ) && 'mix' === $family( 'gikomba' ) );

$cache = get_user_meta( $iya, Vendor::CLOTH, true );
$check( 'Stall cloth cached on creation', is_array( $cache ) && str_starts_with( (string) ( $cache['svg'] ?? '' ), '<svg' ) );

update_user_meta( $iya, 'dokan_store_name', 'Ìyá Àdìrẹ Studio Renamed' );
$renamed = Vendor::cloth_svg( $iya );
update_user_meta( $iya, 'dokan_store_name', 'Ìyá Àdìrẹ Studio' );
$check( 'Renaming a stall gives it a new cloth', $renamed !== (string) $cache['svg'] && Vendor::cloth_svg( $iya ) === (string) $cache['svg'] );

/* Landed price --------------------------------------------------------------- */

$gb = Pricing::landed( $skirt, 'GB', 'GBP' );
$check(
	'Skirt to the UK: £64.50 + £14.80 + £15.86 estimated = £95.16, 6 to 9 days',
	is_array( $gb ) && 64.5 === $gb['item'] && 14.8 === $gb['delivery']['amount'] && 15.86 === $gb['duties']['amount'] && 95.16 === $gb['total'] && 6 === $gb['delivery']['min_days'] && 9 === $gb['delivery']['max_days'],
	$gb
);

$ng = Pricing::landed( $skirt, 'NG', 'NGN' );
$check( 'Skirt within Nigeria: no duties, priced in naira', is_array( $ng ) && 0.0 === $ng['duties']['amount'] && 'NGN' === $ng['currency'] && 132225.0 === $ng['item'], $ng );

$ie = Pricing::landed( $skirt, 'IE' );
$check( 'Ireland falls in the EU zone and defaults to euros', is_array( $ie ) && 'EU' === $ie['zone'] && 'EUR' === $ie['currency'] && $ie['ships'] );

$jp = Pricing::landed( $skirt, 'JP', 'USD' );
$check( 'A country outside the launch zones does not ship', is_array( $jp ) && false === $jp['ships'] && null === $jp['total'] );

$us = Pricing::landed( $skirt, 'US', 'USD' );
$check( 'Converted lines add up to the total exactly', is_array( $us ) && abs( $us['item'] + $us['delivery']['amount'] + $us['duties']['amount'] - $us['total'] ) < 0.001, $us );

/* REST ---------------------------------------------------------------------- */

list( $status, $body ) = $rest( 'GET', '/daguah/v1/currency/rates' );
$check( 'GET rates, signed out: 200, labelled demo', 200 === $status && 'Demo rates' === $body['label'] && 2050.0 === (float) $body['rates']['NGN'], $status );

list( $status, $body ) = $rest( 'GET', '/daguah/v1/landed-price', array( 'product' => $skirt, 'country' => 'GB', 'currency' => 'GBP' ) );
$check( 'GET landed price, signed out: 200 with total 95.16', 200 === $status && 95.16 === (float) $body['total'], $status );

list( $status ) = $rest( 'GET', '/daguah/v1/landed-price', array( 'product' => $skirt, 'country' => 'GB', 'currency' => 'JPY' ) );
$check( 'Landed price rejects an unsupported currency with 400', 400 === $status, $status );

list( $status ) = $rest( 'GET', '/daguah/v1/landed-price', array( 'product' => $circle, 'country' => 'GB' ) );
$check( 'Landed price for something that is not a piece: 404', 404 === $status, $status );

list( $status ) = $rest( 'GET', '/daguah/v1/fit-profile' );
$check( 'GET fit profile, signed out: 401', 401 === $status, $status );

list( $status ) = $rest( 'POST', '/daguah/v1/fit-profile', array( 'bust' => 92 ) );
$check( 'POST fit profile, signed out: 401', 401 === $status, $status );

list( $status, $body ) = $rest( 'POST', '/daguah/v1/fit-profile', array( 'bust' => 92, 'waist' => 76.25, 'unit' => 'in' ), $admin );
$check( 'POST fit profile, signed in: saved in cm, unit kept', 200 === $status && 92.0 === (float) $body['measurements']['bust'] && 76.3 === (float) $body['measurements']['waist'] && 'in' === $body['unit'], $body );

list( $status ) = $rest( 'POST', '/daguah/v1/fit-profile', array( 'bust' => 300 ), $admin );
$check( 'POST fit profile rejects 300 cm with 400', 400 === $status, $status );

list( $status, $body ) = $rest( 'POST', '/daguah/v1/fit-profile', array( 'bust' => null ), $admin );
$check( 'POST null removes one measurement and keeps the others', 200 === $status && ! isset( $body['measurements']['bust'] ) && isset( $body['measurements']['waist'] ), $body );

list( $status ) = $rest( 'GET', "/daguah/v1/circles/{$circle}/board" );
$check( 'Circle board without a key, signed out: 404', 404 === $status, $status );

list( $status ) = $rest( 'GET', "/daguah/v1/circles/{$circle}/board", array( 'key' => str_repeat( 'a', 32 ) ) );
$check( 'Circle board with a wrong key: 404', 404 === $status, $status );

list( $status ) = $rest( 'GET', '/daguah/v1/circles/999999/board', array( 'key' => $key ) );
$check( 'Circle board for a circle that does not exist: 404', 404 === $status, $status );

list( $status, $body ) = $rest( 'GET', "/daguah/v1/circles/{$circle}/board", array( 'key' => $key ) );
$check(
	'Circle board with the invite key: 5 members, 3 paid, first names only',
	200 === $status && 5 === $body['total'] && 3 === $body['paid'] && array( 'Adaeze', 'Ifeoma', 'Ngozi', 'Amaka', 'Obiageli' ) === array_column( $body['members'], 'name' ),
	$status
);

list( $status, $body ) = $rest( 'GET', '/wp/v2/circles' );
$check( 'Core REST does not list circles to signed out visitors', 200 === $status && 0 === count( $body ), count( (array) $body ) );

wp_set_current_user( 0 );

/* Settings ------------------------------------------------------------------- */

$clean = Settings::sanitize( array( 'commission' => 80, 'auto_confirm_days' => 0, 'demo_rates' => array( 'USD' => -4 ), 'flags' => array( 'circle' => '1' ) ) );
$check( 'Settings clamp commission to 50, days to 1, refuse a negative rate, read unticked flags as off', 50.0 === $clean['commission'] && 1 === $clean['auto_confirm_days'] && 1.27 === $clean['demo_rates']['USD'] && true === $clean['flags']['circle'] && false === $clean['flags']['market_live'] );

/* Idempotency, reset, reseed ---------------------------------------------- */

$again = Seeder::run();
$check( 'Second seed run creates nothing new', ! is_wp_error( $again ) && 24 === count( $marked( 'product' ) ) && 8 === $seller_count() && 10 === count( $marked( Post_Types::MOMENT ) ) && empty( $again['stalls created'] ) && empty( $again['pages created'] ), $again );
$check( 'Circle invite key survives a reseed', $key === (string) get_post_meta( $circle, Post_Types::INVITE_KEY, true ) );

$reset = Seeder::reset();
$check( 'Reset removes all seeded pieces, stalls and moments', 0 === count( $marked( 'product' ) ) && 0 === $seller_count() && 0 === count( $marked( Post_Types::MOMENT ) ), $reset );
$check( 'Reset leaves the administrator and the WooCommerce shop page', (bool) get_userdata( $admin ) && (bool) get_page_by_path( 'shop' ) );

$final = Seeder::run();
$check( 'Seeding after a reset rebuilds everything', ! is_wp_error( $final ) && 24 === count( $marked( 'product' ) ) && 8 === $seller_count(), $final );

/* Write ---------------------------------------------------------------------- */

$passed = count( array_filter( $results, static fn( $r ) => $r['pass'] ) );
$dir    = WP_CONTENT_DIR . '/daguah-results';
wp_mkdir_p( $dir );
file_put_contents(
	$dir . '/stage2.json',
	wp_json_encode(
		array(
			'passed'  => $passed,
			'total'   => count( $results ),
			'results' => $results,
		),
		JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
	)
);
