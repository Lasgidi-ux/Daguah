<?php
/**
 * The cultural calendar: which moments come next, and their collections.
 *
 * A recurring moment rolls forward a year at a time until it is current or
 * upcoming. A one off moment that has passed drops off the ribbon.
 *
 * @package Daguah\Core
 */

declare( strict_types=1 );

namespace Daguah\Core;

use DateTimeImmutable;
use DateTimeZone;

defined( 'ABSPATH' ) || exit;

/**
 * Calendar moments in date order.
 */
final class Calendar {

	/**
	 * Current and upcoming moments, soonest first.
	 *
	 * @param int $limit Most moments to return.
	 * @return array<int, array{id: int, title: string, excerpt: string, starts: string, ends: string, now: bool, occasion: int, category: int, limit: int, note: string, link: string}>
	 */
	public static function upcoming( int $limit = 8 ): array {
		$today = new DateTimeImmutable( 'today', wp_timezone() );
		$posts = get_posts(
			array(
				'post_type'   => Post_Types::MOMENT,
				'post_status' => 'publish',
				'numberposts' => 50,
				'orderby'     => 'menu_order',
				'order'       => 'ASC',
			)
		);

		$moments = array();

		foreach ( $posts as $post ) {
			$starts = self::date( (string) get_post_meta( $post->ID, 'daguah_starts', true ) );

			if ( ! $starts ) {
				continue;
			}

			$ends = self::date( (string) get_post_meta( $post->ID, 'daguah_ends', true ) ) ?? $starts;
			$ends = $ends < $starts ? $starts : $ends;

			if ( get_post_meta( $post->ID, 'daguah_recurring', true ) ) {
				while ( $ends < $today ) {
					$starts = $starts->modify( '+1 year' );
					$ends   = $ends->modify( '+1 year' );
				}
			} elseif ( $ends < $today ) {
				continue;
			}

			$occasion = (int) get_post_meta( $post->ID, 'daguah_occasion', true );
			$term     = $occasion ? get_term( $occasion, Taxonomies::OCCASION ) : null;

			$moments[] = array(
				'id'       => (int) $post->ID,
				'title'    => get_the_title( $post ),
				'excerpt'  => (string) $post->post_excerpt,
				'starts'   => $starts->format( 'Y-m-d' ),
				'ends'     => $ends->format( 'Y-m-d' ),
				'now'      => $starts <= $today && $today <= $ends,
				'occasion' => $occasion,
				'category' => (int) get_post_meta( $post->ID, 'daguah_category', true ),
				'limit'    => max( 2, min( 24, (int) get_post_meta( $post->ID, 'daguah_limit', true ) ?: 8 ) ),
				'note'     => (string) get_post_meta( $post->ID, 'daguah_date_note', true ),
				'link'     => $term && ! is_wp_error( $term ) ? (string) get_term_link( $term ) : (string) get_permalink( wc_get_page_id( 'shop' ) ),
			);
		}

		usort( $moments, static fn( $a, $b ) => array( ! $a['now'], $a['starts'] ) <=> array( ! $b['now'], $b['starts'] ) );

		return array_slice( $moments, 0, $limit );
	}

	/**
	 * Product IDs for a moment's collection.
	 *
	 * @param array $moment Moment from upcoming().
	 * @return int[]
	 */
	public static function collection_ids( array $moment ): array {
		$tax_query = array();

		if ( $moment['occasion'] ) {
			$tax_query[] = array(
				'taxonomy' => Taxonomies::OCCASION,
				'terms'    => array( $moment['occasion'] ),
			);
		}

		if ( $moment['category'] ) {
			$tax_query[] = array(
				'taxonomy' => 'product_cat',
				'terms'    => array( $moment['category'] ),
			);
		}

		return array_map(
			'intval',
			get_posts(
				array(
					'post_type'   => 'product',
					'post_status' => 'publish',
					'numberposts' => $moment['limit'],
					'fields'      => 'ids',
					'orderby'     => 'date',
					'order'       => 'DESC',
					'tax_query'   => $tax_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Small, cached by the object cache.
				)
			)
		);
	}

	/**
	 * A moment's dates for people: "12 Dec" or "10 Dec to 5 Jan".
	 *
	 * @param array $moment Moment from upcoming().
	 */
	public static function date_label( array $moment ): string {
		$starts = strtotime( $moment['starts'] );
		$ends   = strtotime( $moment['ends'] );

		if ( $moment['starts'] === $moment['ends'] ) {
			return wp_date( 'j M', $starts, new DateTimeZone( 'UTC' ) );
		}

		/* translators: 1: start date, 2: end date */
		return sprintf( __( '%1$s to %2$s', 'daguah' ), wp_date( 'j M', $starts, new DateTimeZone( 'UTC' ) ), wp_date( 'j M', $ends, new DateTimeZone( 'UTC' ) ) );
	}

	/**
	 * Parse Y-m-d in the site's timezone.
	 *
	 * @param string $value Date string.
	 */
	private static function date( string $value ): ?DateTimeImmutable {
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ) {
			return null;
		}

		$date = DateTimeImmutable::createFromFormat( '!Y-m-d', $value, wp_timezone() );

		return $date ?: null;
	}
}
