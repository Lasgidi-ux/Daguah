<?php
/**
 * Stall pages and the stall directory.
 *
 * Dokan owns the address of each stall (/store/{slug}/) and the query behind
 * it: the main query on a stall page is already that stall's pieces. When the
 * active theme ships a block template called `stall`, it is used in place of
 * Dokan's PHP template, so the stall page is built from DAGUAH blocks like
 * every other page. Any other theme keeps Dokan's own page.
 *
 * @package Daguah\Core
 */

declare( strict_types=1 );

namespace Daguah\Core;

use WP_User;

defined( 'ABSPATH' ) || exit;

/**
 * Stall page routing, directory and per stall facts.
 */
final class Stalls {

	/**
	 * Hooks.
	 */
	public static function init(): void {
		// After Dokan's own loader, which runs at 99.
		add_filter( 'template_include', array( self::class, 'template' ), 100 );
		add_filter( 'body_class', array( self::class, 'body_class' ) );

		// Name the template in the Site Editor.
		add_filter(
			'default_template_types',
			static function ( array $types ): array {
				$types['stall'] = array(
					'title'       => __( 'Stall', 'daguah' ),
					'description' => __( 'A seller\'s stall page: their cloth, story, the checks DAGUAH has done and their pieces.', 'daguah' ),
				);
				return $types;
			}
		);
	}

	/**
	 * The theme's stall template on stall pages.
	 *
	 * @param string $template Template file chosen so far.
	 */
	public static function template( $template ) {
		if ( ! self::current() || is_404() || ! function_exists( 'locate_block_template' ) || ! wp_is_block_theme() ) {
			return $template;
		}

		if ( ! get_block_template( get_stylesheet() . '//stall' ) ) {
			return $template;
		}

		return locate_block_template( (string) $template, 'stall', array( 'stall' ) );
	}

	/**
	 * Body class for stall pages.
	 *
	 * @param string[] $classes Classes.
	 */
	public static function body_class( array $classes ): array {
		if ( self::current() ) {
			$classes[] = 'daguah-stall-page';
		}

		return $classes;
	}

	/**
	 * The stall this page belongs to, or 0.
	 *
	 * On a Dokan stall page, that stall. In the Site Editor, where the
	 * template has no stall of its own, the most verified stall, so the
	 * preview shows real data.
	 */
	public static function current(): int {
		if ( function_exists( 'dokan_is_store_page' ) && dokan_is_store_page() ) {
			$slug = (string) get_query_var( (string) dokan_get_option( 'custom_store_url', 'dokan_general', 'store' ) );
			$user = get_user_by( 'slug', $slug );

			return $user instanceof WP_User && Vendor::is_vendor( $user->ID ) ? (int) $user->ID : 0;
		}

		if ( defined( 'REST_REQUEST' ) && REST_REQUEST && current_user_can( 'edit_theme_options' ) ) {
			return self::directory()[0] ?? 0;
		}

		return 0;
	}

	/**
	 * Stall IDs, most verified first, then longest selling.
	 *
	 * @param array{from?: string, verified?: bool} $filters Ships from country, verified only.
	 * @return int[]
	 */
	public static function directory( array $filters = array() ): array {
		$args = array(
			'role'   => 'seller',
			'fields' => 'ID',
			'number' => 500,
		);

		$meta = array();

		if ( ! empty( $filters['from'] ) ) {
			$meta[] = array(
				'key'   => Vendor::SHIPS_FROM,
				'value' => strtoupper( (string) $filters['from'] ),
			);
		}

		if ( ! empty( $filters['verified'] ) ) {
			$meta[] = array(
				'key'     => Vendor::VERIFIED,
				'value'   => 1,
				'compare' => '>=',
				'type'    => 'NUMERIC',
			);
		}

		if ( $meta ) {
			$args['meta_query'] = array_merge( array( 'relation' => 'AND' ), $meta ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Small set of sellers.
		}

		$stalls = array_filter(
			array_map( static fn( $id ) => Vendor::get( (int) $id ), get_users( $args ) ),
			static fn( array $s ) => self::is_open( $s['id'] )
		);

		usort(
			$stalls,
			static fn( $a, $b ) => array( -$a['verified_level'], $a['selling_since'] ?: PHP_INT_MAX, $a['name'] ) <=> array( -$b['verified_level'], $b['selling_since'] ?: PHP_INT_MAX, $b['name'] )
		);

		return array_map( 'intval', array_column( $stalls, 'id' ) );
	}

	/**
	 * Is the stall switched on in Dokan? Stalls Dokan has disabled are not listed.
	 *
	 * @param int $user_id Vendor ID.
	 */
	public static function is_open( int $user_id ): bool {
		if ( function_exists( 'dokan_is_seller_enabled' ) ) {
			return (bool) dokan_is_seller_enabled( $user_id );
		}

		return Vendor::is_vendor( $user_id );
	}

	/**
	 * Countries stalls ship from, with how many stalls each.
	 *
	 * @return array<string, int> Country code to count, most stalls first.
	 */
	public static function countries(): array {
		$counts = array();

		foreach ( self::directory() as $id ) {
			$code = (string) get_user_meta( $id, Vendor::SHIPS_FROM, true );
			if ( '' !== $code ) {
				$counts[ $code ] = ( $counts[ $code ] ?? 0 ) + 1;
			}
		}

		arsort( $counts );

		return $counts;
	}

	/**
	 * Where a stall is: city and country.
	 *
	 * @param int $user_id Vendor ID.
	 */
	public static function place( int $user_id ): string {
		$profile = (array) get_user_meta( $user_id, 'dokan_profile_settings', true );
		$city    = (string) ( $profile['address']['city'] ?? '' );
		$country = (string) ( $profile['address']['country'] ?? get_user_meta( $user_id, Vendor::SHIPS_FROM, true ) );

		return implode( ', ', array_filter( array( $city, $country ? Countries::name( $country ) : '' ) ) );
	}

	/**
	 * The person behind the stall, by first and last name.
	 *
	 * @param int $user_id Vendor ID.
	 */
	public static function maker( int $user_id ): string {
		return trim( get_user_meta( $user_id, 'first_name', true ) . ' ' . get_user_meta( $user_id, 'last_name', true ) );
	}

	/**
	 * Published pieces on a stall.
	 *
	 * @param int $user_id Vendor ID.
	 * @return int[]
	 */
	public static function piece_ids( int $user_id ): array {
		$ids = get_posts(
			array(
				'post_type'   => 'product',
				'post_status' => 'publish',
				'author'      => $user_id,
				'numberposts' => 500,
				'fields'      => 'ids',
				'exclude'     => Shop_Query::hidden_ids(), // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude -- A handful of IDs.
			)
		);

		return array_map( 'intval', $ids );
	}

	/**
	 * The categories a stall sells in, with how many pieces each.
	 *
	 * @param int $user_id Vendor ID.
	 * @return array<int, array{name: string, slug: string, count: int}>
	 */
	public static function categories( int $user_id ): array {
		$ids = self::piece_ids( $user_id );

		if ( ! $ids || ! taxonomy_exists( 'product_cat' ) ) {
			return array();
		}

		$counts = array();

		foreach ( $ids as $id ) {
			foreach ( (array) get_the_terms( $id, 'product_cat' ) as $term ) {
				if ( $term instanceof \WP_Term && 'uncategorized' !== $term->slug ) {
					$counts[ $term->slug ] ??= array(
						'name'  => $term->name,
						'slug'  => $term->slug,
						'count' => 0,
					);
					++$counts[ $term->slug ]['count'];
				}
			}
		}

		usort( $counts, static fn( $a, $b ) => $b['count'] <=> $a['count'] );

		return array_values( $counts );
	}

	/**
	 * The stall's shop link with a stall filter, so its pieces can be
	 * filtered like the rest of the shop.
	 *
	 * @param int    $user_id  Vendor ID.
	 * @param string $category Optional category slug.
	 */
	public static function shop_url( int $user_id, string $category = '' ): string {
		$user = get_userdata( $user_id );

		if ( ! $user instanceof WP_User || ! function_exists( 'wc_get_page_permalink' ) ) {
			return '';
		}

		$params = array( 'stall' => $user->user_nicename );
		if ( '' !== $category ) {
			$params['cat'] = array( $category );
		}

		return Shop_Query::url( (string) wc_get_page_permalink( 'shop' ), $params );
	}
}
