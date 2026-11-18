<?php
/**
 * Shop filtering, the delivery index and saved searches.
 *
 * Filters are plain GET parameters, so the shop works without JavaScript and
 * every filtered view has a link that can be shared or saved:
 *
 *   cat[]        product category slugs
 *   occasion[]   occasion slugs
 *   origin[]     origin country slugs
 *   fabric[] condition[] size[]  attribute term slugs
 *   min max cur  price range, in the visitor's currency
 *   to           the buyer's country (ISO code)
 *   ships        1: only pieces that ship to `to`
 *   days         only pieces that arrive in `to` within this many days
 *   verified     1: only verified sellers
 *   stall        a stall's slug: only that stall's pieces
 *   fit          "bust:92,waist:76": only pieces that fit or need a small alteration
 *
 * Delivery filters need to search inside the corridor list, so each product
 * also keeps one plain meta row per zone it ships to (_daguah_days_GB = 9,
 * the slowest delivery time). The index is rebuilt whenever corridors change.
 *
 * @package Daguah\Core
 */

declare( strict_types=1 );

namespace Daguah\Core;

use WP_Query;

defined( 'ABSPATH' ) || exit;

/**
 * Shop query, delivery index, saved searches.
 */
final class Shop_Query {

	public const SAVED   = 'daguah_saved_searches';
	public const CRON    = 'daguah_saved_search_alerts';
	public const INDEXED = 'daguah_delivery_index_version';

	private const INDEX_PREFIX  = '_daguah_days_';
	private const INDEX_VERSION = 1;
	private const MAX_SAVED     = 20;

	/**
	 * Hooks.
	 */
	public static function init(): void {
		add_action( 'pre_get_posts', array( self::class, 'filter_main_query' ) );

		add_action( 'added_post_meta', array( self::class, 'on_meta_change' ), 10, 3 );
		add_action( 'updated_post_meta', array( self::class, 'on_meta_change' ), 10, 3 );
		add_action( 'admin_init', array( self::class, 'maybe_backfill' ) );

		add_action( 'init', array( self::class, 'account_endpoint' ) );
		add_filter( 'woocommerce_account_menu_items', array( self::class, 'account_menu' ) );
		add_action( 'woocommerce_account_saved-searches_endpoint', array( self::class, 'account_page' ) );
		add_action( 'admin_post_daguah_remove_saved_search', array( self::class, 'remove_from_form' ) );

		add_action( self::CRON, array( self::class, 'send_alerts' ) );
		add_action(
			'init',
			static function (): void {
				if ( ! wp_next_scheduled( self::CRON ) ) {
					wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::CRON );
				}
			}
		);
	}

	/* Delivery index --------------------------------------------------------- */

	/**
	 * Rebuild one product's delivery index.
	 *
	 * @param int $product_id Product ID.
	 */
	public static function reindex( int $product_id ): void {
		$best = array();

		foreach ( Product_Data::clean_corridors( get_post_meta( $product_id, Product_Data::CORRIDORS, true ) ) as $row ) {
			$best[ $row['to'] ] = min( $best[ $row['to'] ] ?? PHP_INT_MAX, $row['max_days'] );
		}

		foreach ( array_keys( Countries::zones() ) as $zone ) {
			if ( isset( $best[ $zone ] ) ) {
				update_post_meta( $product_id, self::INDEX_PREFIX . $zone, $best[ $zone ] );
			} else {
				delete_post_meta( $product_id, self::INDEX_PREFIX . $zone );
			}
		}
	}

	/**
	 * Reindex when a product's corridors are written.
	 *
	 * @param int    $meta_id   Meta row ID.
	 * @param int    $object_id Post ID.
	 * @param string $key       Meta key.
	 */
	public static function on_meta_change( $meta_id, $object_id, $key ): void {
		if ( Product_Data::CORRIDORS === $key && 'product' === get_post_type( (int) $object_id ) ) {
			self::reindex( (int) $object_id );
		}
	}

	/**
	 * Index every product once, for sites with products made before the index existed.
	 */
	public static function maybe_backfill(): void {
		if ( (int) get_option( self::INDEXED ) >= self::INDEX_VERSION ) {
			return;
		}

		self::reindex_all();
	}

	/**
	 * Index every product.
	 *
	 * @return int Products indexed.
	 */
	public static function reindex_all(): int {
		$ids = get_posts(
			array(
				'post_type'   => 'product',
				'post_status' => 'any',
				'numberposts' => -1,
				'fields'      => 'ids',
			)
		);

		foreach ( $ids as $id ) {
			self::reindex( (int) $id );
		}

		update_option( self::INDEXED, self::INDEX_VERSION );

		return count( $ids );
	}

	/* Filters --------------------------------------------------------------- */

	/**
	 * Products that exist for bookkeeping and must never be listed. Dokan
	 * makes a "Reverse Withdrawal Payment" product for seller fees.
	 *
	 * @return int[]
	 */
	public static function hidden_ids(): array {
		$ids = array( (int) get_option( 'dokan_reverse_withdrawal_product_id', 0 ) );

		/**
		 * Add product IDs that must never appear in DAGUAH listings.
		 *
		 * @param int[] $ids Product IDs.
		 */
		return array_values( array_filter( array_map( 'intval', (array) apply_filters( 'daguah_hidden_product_ids', $ids ) ) ) );
	}

	/**
	 * Is this the main shop listing (shop, product taxonomy or product search)?
	 *
	 * @param WP_Query $query Query.
	 */
	public static function is_shop_query( WP_Query $query ): bool {
		if ( is_admin() || ! $query->is_main_query() ) {
			return false;
		}

		if ( $query->is_post_type_archive( 'product' ) || $query->is_tax( get_object_taxonomies( 'product' ) ) ) {
			return true;
		}

		return $query->is_search() && 'product' === $query->get( 'post_type' );
	}

	/**
	 * Apply the filters in the address to the shop listing.
	 *
	 * @param WP_Query $query Query.
	 */
	public static function filter_main_query( WP_Query $query ): void {
		if ( ! self::is_shop_query( $query ) ) {
			return;
		}

		$args = self::args_from( self::params_from_request() );

		foreach ( array( 'tax_query', 'meta_query' ) as $key ) {
			if ( ! empty( $args[ $key ] ) ) {
				$existing = (array) $query->get( $key );
				$query->set( $key, array_merge( $existing ? $existing : array(), $args[ $key ] ) );
			}
		}

		foreach ( array( 'author__in', 'post__in', 'post__not_in' ) as $key ) {
			if ( isset( $args[ $key ] ) ) {
				$query->set( $key, $args[ $key ] );
			}
		}
	}

	/**
	 * Filter values from the current request, cleaned.
	 */
	public static function params_from_request(): array {
		// Read only; filters change what is shown, never what is stored.
		$get = wp_unslash( $_GET ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		return self::clean_params( is_array( $get ) ? $get : array() );
	}

	/**
	 * Keep known filters only, each cleaned to its type.
	 *
	 * @param array $raw Raw parameters.
	 */
	public static function clean_params( array $raw ): array {
		$slugs = static fn( $value ) => array_values( array_filter( array_map( 'sanitize_title', (array) $value ) ) );
		$clean = array();

		foreach ( array( 'cat', 'occasion', 'origin', 'fabric', 'condition', 'size' ) as $key ) {
			if ( ! empty( $raw[ $key ] ) ) {
				$clean[ $key ] = array_slice( $slugs( $raw[ $key ] ), 0, 20 );
			}
		}

		$currency = strtoupper( sanitize_key( (string) ( $raw['cur'] ?? '' ) ) );
		if ( Countries::is_currency( $currency ) ) {
			$clean['cur'] = $currency;
		}

		foreach ( array( 'min', 'max' ) as $key ) {
			if ( isset( $raw[ $key ] ) && '' !== $raw[ $key ] && is_numeric( $raw[ $key ] ) && (float) $raw[ $key ] >= 0 ) {
				$clean[ $key ] = (float) $raw[ $key ];
			}
		}

		$to = strtoupper( sanitize_key( (string) ( $raw['to'] ?? '' ) ) );
		if ( Countries::zone_for( $to ) ) {
			$clean['to'] = $to;
		}

		if ( ! empty( $raw['ships'] ) ) {
			$clean['ships'] = 1;
		}

		$days = (int) ( $raw['days'] ?? 0 );
		if ( $days >= 1 && $days <= 60 ) {
			$clean['days'] = $days;
		}

		if ( ! empty( $raw['verified'] ) ) {
			$clean['verified'] = 1;
		}

		$stall = sanitize_title( (string) ( is_scalar( $raw['stall'] ?? null ) ? $raw['stall'] : '' ) );
		if ( '' !== $stall ) {
			$clean['stall'] = $stall;
		}

		$fit = Fit::parse( (string) ( $raw['fit'] ?? '' ) );
		if ( $fit ) {
			$clean['fit'] = $fit;
		}

		return $clean;
	}

	/**
	 * Query arguments for a set of cleaned filters.
	 *
	 * @param array $p Cleaned parameters.
	 */
	public static function args_from( array $p ): array {
		$args = array();
		$tax  = array();

		$taxonomies = array(
			'cat'       => 'product_cat',
			'occasion'  => Taxonomies::OCCASION,
			'origin'    => Taxonomies::ORIGIN,
			'fabric'    => 'pa_fabric',
			'condition' => 'pa_condition',
			'size'      => 'pa_size',
		);

		foreach ( $taxonomies as $key => $taxonomy ) {
			if ( ! empty( $p[ $key ] ) && taxonomy_exists( $taxonomy ) ) {
				$tax[] = array(
					'taxonomy' => $taxonomy,
					'field'    => 'slug',
					'terms'    => $p[ $key ],
				);
			}
		}

		if ( $tax ) {
			$args['tax_query'] = array_merge( array( 'relation' => 'AND' ), $tax );
		}

		$meta = array();

		// Prices are entered in the visitor's currency and stored in pounds.
		$rate = (float) ( Pricing::rates()['rates'][ $p['cur'] ?? 'GBP' ] ?? 1.0 );
		$rate = $rate > 0 ? $rate : 1.0;

		if ( isset( $p['min'] ) ) {
			$meta[] = array(
				'key'     => '_price',
				'value'   => floor( $p['min'] / $rate * 100 ) / 100,
				'compare' => '>=',
				'type'    => 'DECIMAL(10,2)',
			);
		}

		if ( isset( $p['max'] ) ) {
			$meta[] = array(
				'key'     => '_price',
				'value'   => ceil( $p['max'] / $rate * 100 ) / 100,
				'compare' => '<=',
				'type'    => 'DECIMAL(10,2)',
			);
		}

		$zone = isset( $p['to'] ) ? Countries::zone_for( $p['to'] ) : null;

		if ( $zone && ! empty( $p['days'] ) ) {
			$meta[] = array(
				'key'     => self::INDEX_PREFIX . $zone,
				'value'   => $p['days'],
				'compare' => '<=',
				'type'    => 'NUMERIC',
			);
		} elseif ( $zone && ! empty( $p['ships'] ) ) {
			$meta[] = array(
				'key'     => self::INDEX_PREFIX . $zone,
				'compare' => 'EXISTS',
			);
		}

		if ( $meta ) {
			$args['meta_query'] = array_merge( array( 'relation' => 'AND' ), $meta );
		}

		if ( ! empty( $p['verified'] ) ) {
			$verified = get_users(
				array(
					'role'       => 'seller',
					'fields'     => 'ID',
					'meta_key'   => Vendor::VERIFIED, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Small set of sellers.
					'meta_value' => 1, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Small set of sellers.
					'meta_compare' => '>=',
					'meta_type'  => 'NUMERIC',
				)
			);

			$args['author__in'] = $verified ? array_map( 'intval', $verified ) : array( 0 );
		}

		if ( ! empty( $p['stall'] ) ) {
			$user  = get_user_by( 'slug', $p['stall'] );
			$stall = $user && Vendor::is_vendor( (int) $user->ID ) ? array( (int) $user->ID ) : array( 0 );

			$args['author__in'] = isset( $args['author__in'] ) ? ( array_values( array_intersect( $args['author__in'], $stall ) ) ?: array( 0 ) ) : $stall;
		}

		if ( ! empty( $p['fit'] ) ) {
			$args['post__in'] = self::fitting_ids( $p['fit'] ) ?: array( 0 );
		}

		$hidden = self::hidden_ids();
		if ( $hidden ) {
			$args['post__not_in'] = $hidden;
		}

		return $args;
	}

	/**
	 * Products that fit or need only a small alteration.
	 *
	 * Reads measurements for every published product. Fine for thousands of
	 * pieces; past that, move measurements to a lookup table.
	 *
	 * @param array $body Buyer measurements in cm.
	 * @return int[]
	 */
	public static function fitting_ids( array $body ): array {
		global $wpdb;

		// One query for every product's measurements.
		$rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				"SELECT pm.post_id, pm.meta_value FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id WHERE pm.meta_key = %s AND p.post_type = 'product' AND p.post_status = 'publish'",
				Product_Data::MEASUREMENTS
			)
		);

		$ids = array();

		foreach ( (array) $rows as $row ) {
			$piece  = Product_Data::clean_measurements( maybe_unserialize( $row->meta_value ) );
			$result = Fit::compare( $piece, $body );

			if ( $result && Fit::NO !== $result['status'] ) {
				$ids[] = (int) $row->post_id;
			}
		}

		return $ids;
	}

	/**
	 * Human labels for active filters, each with a link that removes it.
	 *
	 * @param array  $p    Cleaned parameters.
	 * @param string $base Address of the current listing without filters.
	 * @return array<int, array{label: string, url: string}>
	 */
	public static function active_filters( array $p, string $base ): array {
		$chips = array();
		$names = array(
			'cat'       => 'product_cat',
			'occasion'  => Taxonomies::OCCASION,
			'origin'    => Taxonomies::ORIGIN,
			'fabric'    => 'pa_fabric',
			'condition' => 'pa_condition',
			'size'      => 'pa_size',
		);

		$without = static function ( string $key, $value = null ) use ( $p, $base ): string {
			$next = $p;
			if ( null === $value ) {
				unset( $next[ $key ] );
			} else {
				$next[ $key ] = array_values( array_diff( (array) $next[ $key ], array( $value ) ) );
			}
			return self::url( $base, $next );
		};

		foreach ( $names as $key => $taxonomy ) {
			foreach ( $p[ $key ] ?? array() as $slug ) {
				$term    = get_term_by( 'slug', $slug, $taxonomy );
				$chips[] = array(
					'label' => $term ? $term->name : $slug,
					'url'   => $without( $key, $slug ),
				);
			}
		}

		$currency = $p['cur'] ?? 'GBP';
		if ( isset( $p['min'] ) || isset( $p['max'] ) ) {
			$chips[] = array(
				'label' => isset( $p['min'], $p['max'] )
					/* translators: 1: minimum, 2: maximum, 3: currency code */
					? sprintf( __( '%1$s to %2$s %3$s', 'daguah' ), number_format_i18n( $p['min'] ), number_format_i18n( $p['max'] ), $currency )
					: ( isset( $p['min'] )
						/* translators: 1: minimum, 2: currency code */
						? sprintf( __( 'From %1$s %2$s', 'daguah' ), number_format_i18n( $p['min'] ), $currency )
						/* translators: 1: maximum, 2: currency code */
						: sprintf( __( 'Up to %1$s %2$s', 'daguah' ), number_format_i18n( $p['max'] ), $currency ) ),
				'url'   => self::url( $base, array_diff_key( $p, array_flip( array( 'min', 'max' ) ) ) ),
			);
		}

		if ( ! empty( $p['days'] ) && isset( $p['to'] ) ) {
			$chips[] = array(
				/* translators: 1: country, 2: days */
				'label' => sprintf( __( 'In %1$s within %2$d days', 'daguah' ), Countries::name( $p['to'] ), $p['days'] ),
				'url'   => $without( 'days' ),
			);
		} elseif ( ! empty( $p['ships'] ) && isset( $p['to'] ) ) {
			$chips[] = array(
				/* translators: %s: country */
				'label' => sprintf( __( 'Ships to %s', 'daguah' ), Countries::name( $p['to'] ) ),
				'url'   => $without( 'ships' ),
			);
		}

		if ( ! empty( $p['verified'] ) ) {
			$chips[] = array(
				'label' => __( 'Verified sellers', 'daguah' ),
				'url'   => $without( 'verified' ),
			);
		}

		if ( ! empty( $p['stall'] ) ) {
			$user    = get_user_by( 'slug', $p['stall'] );
			$chips[] = array(
				/* translators: %s: stall name */
				'label' => sprintf( __( 'From %s', 'daguah' ), $user ? Vendor::store_name( (int) $user->ID ) : $p['stall'] ),
				'url'   => $without( 'stall' ),
			);
		}

		if ( ! empty( $p['fit'] ) ) {
			$chips[] = array(
				'label' => __( 'Fits me', 'daguah' ),
				'url'   => $without( 'fit' ),
			);
		}

		return $chips;
	}

	/**
	 * The current listing's address, without filters or page number.
	 */
	public static function base_url(): string {
		global $wp;

		$path = preg_replace( '#/page/\d+/?$#', '/', (string) ( $wp->request ?? '' ) );

		return trailingslashit( home_url( (string) $path ) );
	}

	/**
	 * A listing address with filters applied.
	 *
	 * @param string $base Listing address.
	 * @param array  $p    Cleaned parameters.
	 */
	public static function url( string $base, array $p ): string {
		if ( isset( $p['fit'] ) && is_array( $p['fit'] ) ) {
			$p['fit'] = implode( ',', array_map( static fn( $k, $v ) => $k . ':' . $v, array_keys( $p['fit'] ), $p['fit'] ) );
		}

		$p = array_filter( $p, static fn( $v ) => array() !== $v && '' !== $v && null !== $v );

		return $p ? add_query_arg( urlencode_deep( $p ), $base ) : $base;
	}

	/**
	 * A short description of a saved search, for lists and emails.
	 *
	 * @param array $p Cleaned parameters.
	 */
	public static function describe( array $p ): string {
		$labels = wp_list_pluck( self::active_filters( $p, home_url( '/' ) ), 'label' );

		if ( ! empty( $p['s'] ) ) {
			array_unshift( $labels, '"' . $p['s'] . '"' );
		}

		return $labels ? implode( ' · ', $labels ) : __( 'Everything in the shop', 'daguah' );
	}

	/* Saved searches ------------------------------------------------------- */

	/**
	 * A user's saved searches.
	 *
	 * @param int $user_id User ID.
	 * @return array<int, array{id: string, params: array, label: string, created: string, alerted: string}>
	 */
	public static function saved( int $user_id ): array {
		$saved = get_user_meta( $user_id, self::SAVED, true );

		return is_array( $saved ) ? array_values( $saved ) : array();
	}

	/**
	 * Save the current filters for a user.
	 *
	 * @param int    $user_id User ID.
	 * @param array  $raw     Raw parameters, as in the shop address.
	 * @param string $search  Search words, if any.
	 * @return array|null The saved search, or null when the limit is reached.
	 */
	public static function save( int $user_id, array $raw, string $search = '' ): ?array {
		$saved = self::saved( $user_id );

		if ( count( $saved ) >= self::MAX_SAVED ) {
			return null;
		}

		$params = self::clean_params( $raw );
		$search = sanitize_text_field( $search );
		if ( '' !== $search ) {
			$params['s'] = mb_substr( $search, 0, 80 );
		}

		$entry = array(
			'id'      => wp_generate_password( 12, false ),
			'params'  => $params,
			'label'   => self::describe( $params ),
			'created' => gmdate( 'c' ),
			'alerted' => gmdate( 'c' ),
		);

		$saved[] = $entry;
		update_user_meta( $user_id, self::SAVED, $saved );

		return $entry;
	}

	/**
	 * Remove a saved search.
	 *
	 * @param int    $user_id User ID.
	 * @param string $id      Saved search ID.
	 * @return bool True if one was removed.
	 */
	public static function remove( int $user_id, string $id ): bool {
		$saved = self::saved( $user_id );
		$kept  = array_values( array_filter( $saved, static fn( $s ) => ( $s['id'] ?? '' ) !== $id ) );

		if ( count( $kept ) === count( $saved ) ) {
			return false;
		}

		update_user_meta( $user_id, self::SAVED, $kept );

		return true;
	}

	/**
	 * Email each user the new pieces matching their saved searches since the
	 * last alert. Runs daily.
	 */
	public static function send_alerts(): void {
		$users = get_users(
			array(
				'meta_key' => self::SAVED, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Daily cron.
				'fields'   => array( 'ID', 'user_email', 'display_name' ),
				'number'   => 1000,
			)
		);

		foreach ( $users as $user ) {
			$saved   = self::saved( (int) $user->ID );
			$changed = false;
			$lines   = array();

			foreach ( $saved as $i => $search ) {
				$args = array_merge(
					self::args_from( (array) $search['params'] ),
					array(
						'post_type'   => 'product',
						'post_status' => 'publish',
						'fields'      => 'ids',
						'numberposts' => 10,
						'date_query'  => array( array( 'after' => $search['alerted'] ) ),
					)
				);

				if ( ! empty( $search['params']['s'] ) ) {
					$args['s'] = $search['params']['s'];
				}

				$ids = get_posts( $args );

				if ( $ids ) {
					$lines[] = sprintf(
						/* translators: 1: number of pieces, 2: search description, 3: link */
						_n( '%1$d new piece for %2$s: %3$s', '%1$d new pieces for %2$s: %3$s', count( $ids ), 'daguah' ),
						count( $ids ),
						$search['label'],
						self::url( (string) get_permalink( wc_get_page_id( 'shop' ) ), (array) $search['params'] )
					);
				}

				$saved[ $i ]['alerted'] = gmdate( 'c' );
				$changed                = true;
			}

			if ( $lines ) {
				wp_mail(
					$user->user_email,
					__( 'New on DAGUAH for your saved searches', 'daguah' ),
					/* translators: %s: name */
					sprintf( __( 'Hello %s,', 'daguah' ), $user->display_name ) . "\n\n" . implode( "\n", $lines ) . "\n\n" . __( 'You can remove saved searches from your account at any time.', 'daguah' )
				);
			}

			if ( $changed ) {
				update_user_meta( (int) $user->ID, self::SAVED, $saved );
			}
		}
	}

	/* My Account: Saved searches ------------------------------------------ */

	/**
	 * Register the account endpoint.
	 */
	public static function account_endpoint(): void {
		add_rewrite_endpoint( 'saved-searches', EP_ROOT | EP_PAGES );
	}

	/**
	 * Add the tab before Log out.
	 *
	 * @param array $items Account menu items.
	 */
	public static function account_menu( array $items ): array {
		$logout = $items['customer-logout'] ?? null;
		unset( $items['customer-logout'] );

		$items['saved-searches'] = __( 'Saved searches', 'daguah' );

		if ( null !== $logout ) {
			$items['customer-logout'] = $logout;
		}

		return $items;
	}

	/**
	 * The Saved searches tab.
	 */
	public static function account_page(): void {
		$saved = self::saved( get_current_user_id() );
		$shop  = (string) get_permalink( wc_get_page_id( 'shop' ) );

		if ( ! $saved ) {
			echo '<p class="body-3">' . esc_html__( 'You have no saved searches yet. Filter the shop and choose "Save this search" to hear when new pieces arrive.', 'daguah' ) . '</p>';
			return;
		}

		echo '<ul class="daguah-saved-searches">';

		foreach ( $saved as $search ) {
			printf(
				'<li><a class="body-2" href="%1$s">%2$s</a><form method="post" action="%3$s"><input type="hidden" name="action" value="daguah_remove_saved_search"><input type="hidden" name="id" value="%4$s">%5$s<button type="submit" class="wp-element-button is-style-outline">%6$s</button></form></li>',
				esc_url( self::url( $shop, (array) $search['params'] ) ),
				esc_html( (string) $search['label'] ),
				esc_url( admin_url( 'admin-post.php' ) ),
				esc_attr( (string) $search['id'] ),
				wp_nonce_field( 'daguah_remove_saved_search', '_daguah_nonce', true, false ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Core markup.
				esc_html__( 'Remove', 'daguah' )
			);
		}

		echo '</ul>';
	}

	/**
	 * Remove a saved search from the account form.
	 */
	public static function remove_from_form(): void {
		if ( ! is_user_logged_in() || ! isset( $_POST['_daguah_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['_daguah_nonce'] ) ), 'daguah_remove_saved_search' ) ) {
			wp_die( esc_html__( 'That link has expired. Go back to your account and try again.', 'daguah' ), '', array( 'response' => 403 ) );
		}

		self::remove( get_current_user_id(), sanitize_key( wp_unslash( $_POST['id'] ?? '' ) ) );

		if ( function_exists( 'wc_add_notice' ) ) {
			wc_add_notice( __( 'Saved search removed.', 'daguah' ) );
		}

		wp_safe_redirect( wc_get_account_endpoint_url( 'saved-searches' ) );
		exit;
	}
}
