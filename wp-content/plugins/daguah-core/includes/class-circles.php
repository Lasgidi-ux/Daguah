<?php
/**
 * DAGUAH Circle: one fabric, ordered together by family in many countries.
 *
 * A signed in buyer starts a circle: the fabric, how many yards each person
 * needs, where it is delivered and when it closes. They get an invite link
 * (/circle/?c=ID&key=KEY) to send to the family. Anyone holding the link sees
 * the board and can join with their first name, city and currency; signed in
 * members are linked to their account. Each member pays their own share at
 * checkout, which arrives with the cart in Stage 6.
 *
 * Both forms post to admin-post.php, so they work without JavaScript. The
 * board refreshes from the REST board endpoint while it is open.
 *
 * Circles are private posts. Members' names are only ever shown by first
 * name, and only to people with the link, the host, members and editors.
 *
 * @package Daguah\Core
 */

declare( strict_types=1 );

namespace Daguah\Core;

use WP_Error;
use WP_Post;

defined( 'ABSPATH' ) || exit;

/**
 * Starting, joining and reading circles.
 */
final class Circles {

	public const PAGE        = 'circle';
	public const MAX_MEMBERS = 60;
	public const MAX_HOSTED  = 10;

	/**
	 * Hooks.
	 */
	public static function init(): void {
		foreach ( array( 'daguah_circle_start', 'daguah_circle_join' ) as $action ) {
			add_action( 'admin_post_' . $action, array( self::class, 'handle_' . substr( $action, 14 ) ) );
			add_action( 'admin_post_nopriv_' . $action, array( self::class, 'handle_' . substr( $action, 14 ) ) );
		}

		add_filter( 'wp_robots', array( self::class, 'robots' ) );
	}

	/* Reading ----------------------------------------------------------------- */

	/**
	 * A circle post, if it exists and is not in the bin.
	 *
	 * @param int $id Circle ID.
	 */
	public static function get( int $id ): ?WP_Post {
		$post = $id ? get_post( $id ) : null;

		return $post instanceof WP_Post && Post_Types::CIRCLE === $post->post_type && 'trash' !== $post->post_status ? $post : null;
	}

	/**
	 * Can this visitor see the circle? The invite key, the host, members and
	 * editors can.
	 *
	 * @param int    $id      Circle ID.
	 * @param string $key     Invite key from the link, or ''.
	 * @param int    $user_id Signed in user, or 0.
	 */
	public static function can_see( int $id, string $key, int $user_id ): bool {
		if ( ! self::get( $id ) ) {
			return false;
		}

		$stored = (string) get_post_meta( $id, Post_Types::INVITE_KEY, true );

		if ( '' !== $key && '' !== $stored && hash_equals( $stored, $key ) ) {
			return true;
		}

		if ( ! $user_id ) {
			return false;
		}

		if ( user_can( $user_id, 'edit_post', $id ) || (int) get_post_meta( $id, 'daguah_host', true ) === $user_id ) {
			return true;
		}

		return null !== self::member_index( $id, $user_id );
	}

	/**
	 * Position of a signed in user in the member list, or null.
	 *
	 * @param int $id      Circle ID.
	 * @param int $user_id User ID.
	 */
	public static function member_index( int $id, int $user_id ): ?int {
		if ( ! $user_id ) {
			return null;
		}

		foreach ( self::members( $id ) as $i => $member ) {
			if ( $member['user_id'] === $user_id ) {
				return $i;
			}
		}

		return null;
	}

	/**
	 * Members, cleaned.
	 *
	 * @param int $id Circle ID.
	 */
	public static function members( int $id ): array {
		return Post_Types::clean_members( get_post_meta( $id, Post_Types::MEMBERS, true ) );
	}

	/**
	 * Has the circle closed to new members? It stays open all of its closing day.
	 *
	 * @param int $id Circle ID.
	 */
	public static function is_closed( int $id ): bool {
		$closes = (string) get_post_meta( $id, 'daguah_closes', true );

		if ( '' === $closes ) {
			return false;
		}

		return wp_date( 'Y-m-d' ) > $closes;
	}

	/**
	 * The invite link.
	 *
	 * @param int $id Circle ID.
	 */
	public static function invite_url( int $id ): string {
		return add_query_arg(
			array(
				'c'   => $id,
				'key' => (string) get_post_meta( $id, Post_Types::INVITE_KEY, true ),
			),
			self::page_url()
		);
	}

	/**
	 * The Circle page.
	 */
	public static function page_url(): string {
		$page = get_page_by_path( self::PAGE );

		return $page ? (string) get_permalink( $page ) : home_url( '/' . self::PAGE . '/' );
	}

	/**
	 * The circle this request is for, when the visitor may see it.
	 *
	 * @return array{id: int, key: string, asked: bool} The circle ID (0 when
	 *         none or not allowed), the key from the link, and whether the
	 *         address asked for a circle at all.
	 */
	public static function from_request(): array {
		// Read only. The key is what grants access, checked below.
		$id  = absint( $_GET['c'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$key = preg_replace( '/[^A-Za-z0-9]/', '', (string) wp_unslash( $_GET['key'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		return array(
			'id'    => $id && self::can_see( $id, (string) $key, get_current_user_id() ) ? $id : 0,
			'key'   => (string) $key,
			'asked' => $id > 0,
		);
	}

	/**
	 * Everything the board shows. First names only.
	 *
	 * @param int $id Circle ID.
	 */
	public static function board( int $id ): array {
		$members = self::members( $id );
		$product = (int) get_post_meta( $id, 'daguah_fabric_product', true );
		$wc      = $product && function_exists( 'wc_get_product' ) ? wc_get_product( $product ) : null;
		$host    = array_values( array_filter( $members, static fn( $m ) => $m['is_host'] ) )[0] ?? null;

		return array(
			'id'               => $id,
			// The raw title: get_the_title() would add "Private:", as circles are private posts.
			'title'            => wptexturize( (string) get_post_field( 'post_title', $id ) ),
			'host'             => $host ? self::first_name( $host['name'] ) : '',
			'fabric'           => $wc ? array(
				'id'    => $product,
				'title' => $wc->get_name(),
				'price' => (float) $wc->get_price(),
				'link'  => $wc->get_permalink(),
			) : null,
			'yards_per_person' => (float) get_post_meta( $id, 'daguah_yards_per_person', true ),
			'delivery_mode'    => (string) get_post_meta( $id, 'daguah_delivery_mode', true ),
			'closes'           => (string) get_post_meta( $id, 'daguah_closes', true ),
			'closed'           => self::is_closed( $id ),
			'members'          => array_map(
				static fn( array $m ) => array(
					'name'     => self::first_name( $m['name'] ),
					'city'     => $m['city'],
					'country'  => $m['country'],
					'currency' => $m['currency'],
					'paid'     => 'paid' === $m['status'],
					'host'     => $m['is_host'],
				),
				$members
			),
			'paid'             => count( array_filter( $members, static fn( $m ) => 'paid' === $m['status'] ) ),
			'total'            => count( $members ),
			'countries'        => count( array_unique( array_filter( array_column( $members, 'country' ) ) ) ),
			'poll_seconds'     => 20,
		);
	}

	/**
	 * First word of a name.
	 *
	 * @param string $name Full name.
	 */
	public static function first_name( string $name ): string {
		$first = strtok( trim( $name ), ' ' );

		return false === $first ? trim( $name ) : $first;
	}

	/**
	 * Fabrics a circle can be started with: published pieces in the fabrics
	 * category, or every piece when the site has no such category.
	 *
	 * @return array<int, string> Product ID to name.
	 */
	public static function fabrics(): array {
		if ( ! function_exists( 'wc_get_products' ) ) {
			return array();
		}

		$args = array(
			'status'  => 'publish',
			'limit'   => 200,
			'orderby' => 'title',
			'order'   => 'ASC',
			'exclude' => Shop_Query::hidden_ids(),
		);

		if ( term_exists( 'fabrics', 'product_cat' ) ) {
			$args['category'] = array( 'fabrics' );
		}

		$list = array();
		foreach ( wc_get_products( $args ) as $product ) {
			$list[ (int) $product->get_id() ] = $product->get_name();
		}

		return $list;
	}

	/* Writing ----------------------------------------------------------------- */

	/**
	 * Start a circle. The host joins it as its first member.
	 *
	 * @param int   $user_id Host, signed in.
	 * @param array $input   title, note, fabric, yards, delivery, closes, name, city, country, currency.
	 * @return int|WP_Error Circle ID.
	 */
	public static function start( int $user_id, array $input ) {
		if ( ! Settings::flag( 'circle' ) ) {
			return new WP_Error( 'daguah_circle_off', __( 'DAGUAH Circle is not open at the moment.', 'daguah' ) );
		}

		if ( ! $user_id ) {
			return new WP_Error( 'daguah_circle_signin', __( 'Sign in to start a circle, so you can manage it later.', 'daguah' ) );
		}

		$title    = sanitize_text_field( (string) ( $input['title'] ?? '' ) );
		$note     = sanitize_textarea_field( (string) ( $input['note'] ?? '' ) );
		$fabric   = absint( $input['fabric'] ?? 0 );
		$yards    = (float) ( $input['yards'] ?? 0 );
		$delivery = (string) ( $input['delivery'] ?? 'split' );
		$closes   = (string) ( $input['closes'] ?? '' );
		$today    = wp_date( 'Y-m-d' );
		$latest   = wp_date( 'Y-m-d', time() + YEAR_IN_SECONDS );

		$errors = array();

		if ( mb_strlen( $title ) < 3 || mb_strlen( $title ) > 120 ) {
			$errors['title'] = __( 'Give the circle a name between 3 and 120 characters, for example the couple\'s names and the occasion.', 'daguah' );
		}
		if ( mb_strlen( $note ) > 1000 ) {
			$errors['note'] = __( 'Keep the note to the family under 1,000 characters.', 'daguah' );
		}
		if ( ! array_key_exists( $fabric, self::fabrics() ) ) {
			$errors['fabric'] = __( 'Choose a fabric from the list.', 'daguah' );
		}
		if ( $yards < 1 || $yards > 20 ) {
			$errors['yards'] = __( 'Each person needs between 1 and 20 yards.', 'daguah' );
		}
		if ( ! in_array( $delivery, array( 'split', 'single' ), true ) ) {
			$errors['delivery'] = __( 'Choose where the fabric is delivered.', 'daguah' );
		}
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $closes ) || $closes <= $today || $closes > $latest ) {
			$errors['closes'] = __( 'Choose a closing date after today and within a year.', 'daguah' );
		}

		$member = self::clean_member_input( $input, $errors );

		if ( $errors ) {
			return new WP_Error( 'daguah_circle_invalid', __( 'Some details need another look.', 'daguah' ), array( 'fields' => $errors ) );
		}

		if ( count( self::hosted_open( $user_id ) ) >= self::MAX_HOSTED ) {
			return new WP_Error( 'daguah_circle_limit', __( 'You are hosting 10 open circles already. Close one before starting another.', 'daguah' ) );
		}

		$id = wp_insert_post(
			array(
				'post_type'    => Post_Types::CIRCLE,
				'post_status'  => 'private',
				'post_title'   => $title,
				'post_content' => '' !== $note ? '<!-- wp:paragraph --><p>' . esc_html( $note ) . '</p><!-- /wp:paragraph -->' : '',
				'post_author'  => $user_id,
			),
			true
		);

		if ( is_wp_error( $id ) ) {
			return $id;
		}

		$member['status']  = 'waiting';
		$member['user_id'] = $user_id;
		$member['is_host'] = true;

		update_post_meta( $id, 'daguah_host', $user_id );
		update_post_meta( $id, 'daguah_fabric_product', $fabric );
		update_post_meta( $id, 'daguah_yards_per_person', $yards );
		update_post_meta( $id, 'daguah_delivery_mode', $delivery );
		update_post_meta( $id, 'daguah_closes', $closes );
		update_post_meta( $id, Post_Types::MEMBERS, array( $member ) );
		Post_Types::ensure_invite_key( (int) $id );

		/**
		 * A circle was started.
		 *
		 * @param int $id      Circle ID.
		 * @param int $user_id Host.
		 */
		do_action( 'daguah_circle_started', (int) $id, $user_id );

		return (int) $id;
	}

	/**
	 * Join a circle.
	 *
	 * @param int    $id      Circle ID.
	 * @param string $key     Invite key from the link.
	 * @param array  $input   name, city, country, currency.
	 * @param int    $user_id Signed in user, or 0.
	 * @return true|WP_Error
	 */
	public static function join( int $id, string $key, array $input, int $user_id ) {
		if ( ! Settings::flag( 'circle' ) ) {
			return new WP_Error( 'daguah_circle_off', __( 'DAGUAH Circle is not open at the moment.', 'daguah' ) );
		}

		if ( ! self::can_see( $id, $key, $user_id ) ) {
			return new WP_Error( 'daguah_no_circle', __( 'That circle does not exist or the link has expired.', 'daguah' ) );
		}

		if ( self::is_closed( $id ) ) {
			return new WP_Error( 'daguah_circle_closed', __( 'This circle has closed to new members. Ask the host about a second circle.', 'daguah' ) );
		}

		if ( null !== self::member_index( $id, $user_id ) ) {
			return new WP_Error( 'daguah_circle_member', __( 'You are already in this circle.', 'daguah' ) );
		}

		$members = self::members( $id );

		if ( count( $members ) >= self::MAX_MEMBERS ) {
			return new WP_Error( 'daguah_circle_full', __( 'This circle is full. Ask the host to start a second one.', 'daguah' ) );
		}

		// Guests joining from one connection: a few an hour is plenty for a family.
		if ( ! $user_id ) {
			$bucket = 'daguah_circle_joins_' . md5( (string) ( $_SERVER['REMOTE_ADDR'] ?? '' ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- Hashed, never shown.
			$tries  = (int) get_transient( $bucket );

			if ( $tries >= 12 ) {
				return new WP_Error( 'daguah_circle_slow', __( 'Too many people joined from this connection in the last hour. Try again later.', 'daguah' ) );
			}

			set_transient( $bucket, $tries + 1, HOUR_IN_SECONDS );
		}

		$errors = array();
		$member = self::clean_member_input( $input, $errors );

		if ( $errors ) {
			return new WP_Error( 'daguah_circle_invalid', __( 'Some details need another look.', 'daguah' ), array( 'fields' => $errors ) );
		}

		$member['status']  = 'waiting';
		$member['user_id'] = $user_id;
		$member['is_host'] = false;
		$members[]         = $member;

		update_post_meta( $id, Post_Types::MEMBERS, $members );

		/**
		 * Someone joined a circle.
		 *
		 * @param int   $id     Circle ID.
		 * @param array $member The new member.
		 */
		do_action( 'daguah_circle_joined', $id, $member );

		return true;
	}

	/**
	 * Name, city, country and currency from a form, with errors by field.
	 *
	 * @param array $input  Raw input.
	 * @param array $errors Errors, added to.
	 */
	private static function clean_member_input( array $input, array &$errors ): array {
		$name     = sanitize_text_field( (string) ( $input['name'] ?? '' ) );
		$city     = sanitize_text_field( (string) ( $input['city'] ?? '' ) );
		$country  = strtoupper( sanitize_key( (string) ( $input['country'] ?? '' ) ) );
		$currency = strtoupper( sanitize_key( (string) ( $input['currency'] ?? '' ) ) );

		if ( '' === $name || mb_strlen( $name ) > 60 ) {
			$errors['name'] = __( 'Add your name, up to 60 characters. Only your first name is shown to the circle.', 'daguah' );
		}
		if ( '' === $city || mb_strlen( $city ) > 60 ) {
			$errors['city'] = __( 'Add the city the fabric goes to.', 'daguah' );
		}
		if ( ! array_key_exists( $country, Geo::buyer_countries() ) ) {
			$errors['country'] = __( 'Choose your country.', 'daguah' );
		}
		if ( ! Countries::is_currency( $currency ) ) {
			$currency = Countries::currency_for( $country );
		}

		return array(
			'name'     => $name,
			'city'     => $city,
			'country'  => $country,
			'currency' => $currency,
		);
	}

	/**
	 * Open circles a user hosts.
	 *
	 * @param int $user_id User ID.
	 * @return int[]
	 */
	public static function hosted_open( int $user_id ): array {
		$ids = get_posts(
			array(
				'post_type'   => Post_Types::CIRCLE,
				'post_status' => array( 'private', 'draft', 'pending' ),
				'numberposts' => 50,
				'fields'      => 'ids',
				'meta_key'    => 'daguah_host', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Small post type.
				'meta_value'  => $user_id, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Small post type.
			)
		);

		return array_values( array_filter( array_map( 'intval', $ids ), static fn( $id ) => ! self::is_closed( $id ) ) );
	}

	/* Forms ------------------------------------------------------------------- */

	/**
	 * Start form posted.
	 */
	public static function handle_start(): void {
		$back = self::page_url();

		if ( ! isset( $_POST['_daguah_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['_daguah_nonce'] ) ), 'daguah_circle_start' ) ) {
			self::go( $back, array( 'circle_error' => 'expired' ), '#start' );
		}

		$input = isset( $_POST['circle'] ) && is_array( $_POST['circle'] ) ? wp_unslash( $_POST['circle'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Each field is cleaned in start().
		$id    = self::start( get_current_user_id(), $input );

		if ( is_wp_error( $id ) ) {
			self::remember( 'start', $id, $input );
			self::go( $back, array( 'circle_error' => $id->get_error_code() ), '#start' );
		}

		self::go( self::invite_url( $id ), array( 'circle_done' => 'started' ), '#board' );
	}

	/**
	 * Join form posted.
	 */
	public static function handle_join(): void {
		$id  = absint( $_POST['circle_id'] ?? 0 );
		$key = preg_replace( '/[^A-Za-z0-9]/', '', (string) wp_unslash( $_POST['key'] ?? '' ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.Missing -- Checked next.

		$back = add_query_arg(
			array(
				'c'   => $id,
				'key' => $key,
			),
			self::page_url()
		);

		if ( ! isset( $_POST['_daguah_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['_daguah_nonce'] ) ), 'daguah_circle_join_' . $id ) ) {
			self::go( $back, array( 'circle_error' => 'expired' ), '#join' );
		}

		$input  = isset( $_POST['member'] ) && is_array( $_POST['member'] ) ? wp_unslash( $_POST['member'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Each field is cleaned in join().
		$result = self::join( $id, (string) $key, $input, get_current_user_id() );

		if ( is_wp_error( $result ) ) {
			self::remember( 'join', $result, $input );
			self::go( $back, array( 'circle_error' => $result->get_error_code() ), '#join' );
		}

		self::go( $back, array( 'circle_done' => 'joined' ), '#board' );
	}

	/**
	 * Keep what was typed and what went wrong for a minute, so the form can
	 * show it again after the redirect. Keyed to this browser by a cookie.
	 *
	 * @param string   $form  start or join.
	 * @param WP_Error $error The error.
	 * @param array    $input What was typed.
	 */
	private static function remember( string $form, WP_Error $error, array $input ): void {
		$token = wp_generate_password( 20, false );
		$data  = array(
			'form'    => $form,
			'message' => $error->get_error_message(),
			'fields'  => (array) ( $error->get_error_data()['fields'] ?? array() ),
			'input'   => array_map( static fn( $v ) => is_scalar( $v ) ? sanitize_text_field( (string) $v ) : '', $input ),
		);

		set_transient( 'daguah_circle_form_' . $token, $data, MINUTE_IN_SECONDS * 5 );
		setcookie( 'daguah_circle_form', $token, time() + MINUTE_IN_SECONDS * 5, COOKIEPATH ?: '/', COOKIE_DOMAIN, is_ssl(), true );
	}

	/**
	 * What was remembered for a form, read once.
	 *
	 * @param string $form start or join.
	 * @return array{message: string, fields: array, input: array}|null
	 */
	public static function recall( string $form ): ?array {
		$token = preg_replace( '/[^A-Za-z0-9]/', '', (string) wp_unslash( $_COOKIE['daguah_circle_form'] ?? '' ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Cleaned to letters and digits.

		if ( '' === $token ) {
			return null;
		}

		$data = get_transient( 'daguah_circle_form_' . $token );

		if ( ! is_array( $data ) || ( $data['form'] ?? '' ) !== $form ) {
			return null;
		}

		delete_transient( 'daguah_circle_form_' . $token );

		return $data;
	}

	/**
	 * Redirect and stop.
	 *
	 * @param string $url   Address.
	 * @param array  $args  Query arguments to add.
	 * @param string $hash  Fragment.
	 */
	private static function go( string $url, array $args, string $hash = '' ): void {
		wp_safe_redirect( add_query_arg( $args, $url ) . $hash, 303 );
		exit;
	}

	/* Search engines ---------------------------------------------------------- */

	/**
	 * Circle boards are never indexed.
	 *
	 * @param array $robots Robots directives.
	 */
	public static function robots( array $robots ): array {
		if ( is_page( self::PAGE ) && isset( $_GET['c'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read only.
			$robots['noindex']  = true;
			$robots['nofollow'] = true;
			unset( $robots['max-image-preview'] );
		}

		return $robots;
	}
}
