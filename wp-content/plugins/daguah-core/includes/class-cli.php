<?php
/**
 * WP-CLI commands.
 *
 *   wp daguah seed           Create or update the demo content.
 *   wp daguah reset [--yes]  Remove only what the seed created.
 *
 * @package Daguah\Core
 */

declare( strict_types=1 );

namespace Daguah\Core;

use WP_CLI;

defined( 'ABSPATH' ) || exit;

/**
 * Seed and reset demo content for staging.
 */
final class CLI {

	/**
	 * Create the demo stalls, pieces, occasions, calendar moments, sample
	 * circle, live session, Journal article, pages and navigation.
	 *
	 * Safe to run again: it updates what it made before and never makes a
	 * second copy. Pages and navigation are only created when missing, so
	 * edits made in the Site Editor are kept.
	 *
	 * ## EXAMPLES
	 *
	 *     wp daguah seed
	 *
	 * @when after_wp_load
	 */
	public function seed(): void {
		$result = Seeder::run( array( WP_CLI::class, 'log' ) );

		if ( is_wp_error( $result ) ) {
			WP_CLI::error( $result->get_error_message() );
		}

		WP_CLI::success( self::summary( $result, 'Seeded.' ) );
	}

	/**
	 * Remove everything the seed command created, and nothing else.
	 *
	 * Settings and the fabric, condition and size attributes are kept.
	 *
	 * ## OPTIONS
	 *
	 * [--yes]
	 * : Skip the confirmation.
	 *
	 * ## EXAMPLES
	 *
	 *     wp daguah reset --yes
	 *
	 * @when after_wp_load
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Flags.
	 */
	public function reset( array $args, array $assoc_args ): void {
		WP_CLI::confirm( 'Remove all seeded stalls, pieces, moments, circles, sessions, posts, pages and terms?', $assoc_args );

		WP_CLI::success( self::summary( Seeder::reset( array( WP_CLI::class, 'log' ) ), 'Reset.' ) );
	}

	/**
	 * Rebuild the delivery index that the shop's delivery filters use.
	 *
	 * It updates itself whenever a product's corridors change; run this after
	 * importing products another way.
	 *
	 * ## EXAMPLES
	 *
	 *     wp daguah reindex
	 *
	 * @when after_wp_load
	 */
	public function reindex(): void {
		WP_CLI::success( sprintf( 'Delivery index rebuilt for %d pieces.', Shop_Query::reindex_all() ) );
	}

	/**
	 * One line of counts.
	 *
	 * @param array  $counts Counts by type.
	 * @param string $lead   First word.
	 */
	private static function summary( array $counts, string $lead ): string {
		if ( ! $counts ) {
			return $lead . ' Nothing needed changing.';
		}

		$parts = array();
		foreach ( $counts as $type => $count ) {
			$parts[] = $count . ' ' . str_replace( '_', ' ', $type );
		}

		return $lead . ' ' . implode( ', ', $parts ) . '.';
	}
}
