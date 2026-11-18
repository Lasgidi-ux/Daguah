<?php
/**
 * Market Live: the schedule, the player and calendar files.
 *
 * Sessions are stored with start and end times in UTC. Everything here works
 * in Unix time, and the pages print GMT that the view script rewrites into
 * the visitor's own time.
 *
 * When Market Live is switched off in DAGUAH settings, session pages answer
 * 404 and the blocks show a short note instead of a schedule.
 *
 * @package Daguah\Core
 */

declare( strict_types=1 );

namespace Daguah\Core;

use WP_Post;

defined( 'ABSPATH' ) || exit;

/**
 * Session schedule and helpers.
 */
final class Live {

	public const NOW   = 'now';
	public const NEXT  = 'next';
	public const LATER = 'later';
	public const PAST  = 'past';

	/**
	 * Hooks.
	 */
	public static function init(): void {
		add_action( 'template_redirect', array( self::class, 'gate' ) );
		add_action( 'template_redirect', array( self::class, 'calendar_file' ), 20 );
	}

	/**
	 * One session, read and timed.
	 *
	 * @param WP_Post|int $post Session.
	 * @param int|null    $now  Unix time to judge against.
	 * @return array{id: int, title: string, excerpt: string, link: string, start: int, end: int, status: string, stall: int, market: string, stream: string, products: int[]}|null
	 */
	public static function session( $post, ?int $now = null ): ?array {
		$post = get_post( $post );

		if ( ! $post instanceof WP_Post || Post_Types::LIVE !== $post->post_type ) {
			return null;
		}

		$start = self::stamp( (string) get_post_meta( $post->ID, 'daguah_starts_at', true ) );

		if ( ! $start ) {
			return null;
		}

		$end = self::stamp( (string) get_post_meta( $post->ID, 'daguah_ends_at', true ) );
		$end = $end > $start ? $end : $start + HOUR_IN_SECONDS;
		$now = $now ?? time();

		if ( $start <= $now && $now <= $end ) {
			$status = self::NOW;
		} elseif ( $now > $end ) {
			$status = self::PAST;
		} else {
			$status = self::LATER;
		}

		return array(
			'id'       => (int) $post->ID,
			'title'    => get_the_title( $post ),
			'excerpt'  => (string) $post->post_excerpt,
			'link'     => (string) get_permalink( $post ),
			'start'    => $start,
			'end'      => $end,
			'status'   => $status,
			'stall'    => (int) get_post_meta( $post->ID, 'daguah_stall', true ),
			'market'   => (string) get_post_meta( $post->ID, 'daguah_market', true ),
			'stream'   => (string) get_post_meta( $post->ID, 'daguah_stream_url', true ),
			'products' => array_values( array_filter( array_map( 'absint', (array) get_post_meta( $post->ID, 'daguah_products', true ) ) ) ),
		);
	}

	/**
	 * Every published session: on now first, then upcoming soonest first,
	 * then past sessions newest first. The first upcoming one is marked next.
	 *
	 * @param int $stall Only this stall's sessions, or 0 for all.
	 * @param int|null $now Unix time to judge against.
	 * @return array<int, array>
	 */
	public static function schedule( int $stall = 0, ?int $now = null ): array {
		$args = array(
			'post_type'   => Post_Types::LIVE,
			'post_status' => 'publish',
			'numberposts' => 100,
		);

		if ( $stall ) {
			$args['meta_key']   = 'daguah_stall'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Small post type.
			$args['meta_value'] = $stall; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Small post type.
		}

		$sessions = array_values( array_filter( array_map( static fn( $p ) => self::session( $p, $now ), get_posts( $args ) ) ) );
		$rank     = array(
			self::NOW   => 0,
			self::LATER => 1,
			self::PAST  => 2,
		);

		usort(
			$sessions,
			static fn( $a, $b ) => array( $rank[ $a['status'] ], self::PAST === $a['status'] ? -$a['start'] : $a['start'] )
				<=> array( $rank[ $b['status'] ], self::PAST === $b['status'] ? -$b['start'] : $b['start'] )
		);

		foreach ( $sessions as $i => $session ) {
			if ( self::LATER === $session['status'] ) {
				$sessions[ $i ]['status'] = self::NEXT;
				break;
			}
		}

		return $sessions;
	}

	/**
	 * The session on now, or the next one, or null.
	 *
	 * @param int $stall Only this stall's sessions, or 0 for all.
	 */
	public static function current_or_next( int $stall = 0 ): ?array {
		$first = self::schedule( $stall )[0] ?? null;

		return $first && self::PAST !== $first['status'] ? $first : null;
	}

	/**
	 * Unix time from a stored UTC value such as 2026-11-21T17:00.
	 *
	 * @param string $value Stored value.
	 */
	public static function stamp( string $value ): int {
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $value ) ) {
			return 0;
		}

		return (int) strtotime( $value . ':00 UTC' );
	}

	/**
	 * A time printed in GMT inside a <time> element the view script localises.
	 *
	 * @param int    $stamp  Unix time.
	 * @param string $format PHP date format.
	 */
	public static function time_tag( int $stamp, string $format = 'D j M, H:i' ): string {
		return sprintf(
			'<time datetime="%1$s" data-local-time>%2$s</time>',
			esc_attr( gmdate( 'c', $stamp ) ),
			esc_html( gmdate( $format, $stamp ) . ' GMT' )
		);
	}

	/**
	 * Address of an embeddable player for a stream link, or ''.
	 *
	 * YouTube plays from the no cookie domain. Mux plays through its hosted
	 * player. Anything else is offered as a plain link.
	 *
	 * @param string $url Stream link.
	 */
	public static function embed_url( string $url ): string {
		$host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
		$path = (string) wp_parse_url( $url, PHP_URL_PATH );

		if ( in_array( $host, array( 'youtube.com', 'www.youtube.com', 'youtu.be' ), true ) ) {
			$id = '';

			if ( 'youtu.be' === $host ) {
				$id = trim( $path, '/' );
			} elseif ( preg_match( '#^/(?:live|embed|shorts)/([A-Za-z0-9_-]{6,})#', $path, $m ) ) {
				$id = $m[1];
			} else {
				parse_str( (string) wp_parse_url( $url, PHP_URL_QUERY ), $query );
				$id = is_string( $query['v'] ?? null ) ? $query['v'] : '';
			}

			return preg_match( '/^[A-Za-z0-9_-]{6,20}$/', $id ) ? 'https://www.youtube-nocookie.com/embed/' . $id . '?rel=0' : '';
		}

		if ( 'stream.mux.com' === $host && preg_match( '#^/([A-Za-z0-9]{10,})#', $path, $m ) ) {
			return 'https://player.mux.com/' . $m[1];
		}

		if ( in_array( $host, array( 'vimeo.com', 'player.vimeo.com' ), true ) && preg_match( '#/(?:video/)?(\d{5,})#', $path, $m ) ) {
			return 'https://player.vimeo.com/video/' . $m[1];
		}

		return '';
	}

	/**
	 * Address of the calendar file for a session.
	 *
	 * @param int $id Session ID.
	 */
	public static function calendar_url( int $id ): string {
		return add_query_arg( 'calendar', '1', (string) get_permalink( $id ) );
	}

	/**
	 * Session pages answer 404 while Market Live is switched off.
	 */
	public static function gate(): void {
		if ( is_singular( Post_Types::LIVE ) && ! Settings::flag( 'market_live' ) ) {
			global $wp_query;
			$wp_query->set_404();
			status_header( 404 );
			nocache_headers();
		}
	}

	/**
	 * Send a session as an .ics file when ?calendar=1 is on its address.
	 */
	public static function calendar_file(): void {
		if ( ! is_singular( Post_Types::LIVE ) || empty( $_GET['calendar'] ) || is_404() ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read only.
			return;
		}

		$session = self::session( (int) get_queried_object_id() );

		if ( ! $session ) {
			return;
		}

		nocache_headers();
		header( 'Content-Type: text/calendar; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="daguah-live-' . $session['id'] . '.ics"' );

		echo self::ics( $session ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Calendar text, escaped for the iCalendar format inside.
		exit;
	}

	/**
	 * iCalendar text for one session.
	 *
	 * @param array $session Session from session().
	 */
	public static function ics( array $session ): string {
		$text = static fn( string $value ): string => str_replace( array( '\\', ';', ',', "\r\n", "\n" ), array( '\\\\', '\\;', '\\,', '\\n', '\\n' ), wp_strip_all_tags( $value ) );
		$host = (string) wp_parse_url( home_url(), PHP_URL_HOST );
		$where = implode( ', ', array_filter( array( $session['stall'] ? Vendor::store_name( $session['stall'] ) : '', $session['market'] ) ) );

		$lines = array(
			'BEGIN:VCALENDAR',
			'VERSION:2.0',
			'PRODID:-//DAGUAH//Market Live//EN',
			'CALSCALE:GREGORIAN',
			'METHOD:PUBLISH',
			'BEGIN:VEVENT',
			'UID:live-' . $session['id'] . '@' . $host,
			'DTSTAMP:' . gmdate( 'Ymd\THis\Z' ),
			'DTSTART:' . gmdate( 'Ymd\THis\Z', $session['start'] ),
			'DTEND:' . gmdate( 'Ymd\THis\Z', $session['end'] ),
			'SUMMARY:' . $text( sprintf( /* translators: %s: session title */ __( 'Market Live: %s', 'daguah' ), $session['title'] ) ),
			'DESCRIPTION:' . $text( trim( $session['excerpt'] . "\n" . $session['link'] ) ),
			'LOCATION:' . $text( $where ),
			'URL:' . $session['link'],
			'BEGIN:VALARM',
			'ACTION:DISPLAY',
			'DESCRIPTION:' . $text( __( 'Market Live starts in 15 minutes', 'daguah' ) ),
			'TRIGGER:-PT15M',
			'END:VALARM',
			'END:VEVENT',
			'END:VCALENDAR',
		);

		// Lines longer than 75 octets are folded, as the format requires.
		$folded = array_map(
			static function ( string $line ): string {
				$out   = '';
				$limit = 75;
				while ( strlen( $line ) > $limit ) {
					$cut = $limit;
					// Continuation lines start with a space, which counts.
					$limit = 74;
					// Do not split a multibyte character.
					while ( $cut > 0 && ( ord( $line[ $cut ] ) & 0xC0 ) === 0x80 ) {
						--$cut;
					}
					$out .= substr( $line, 0, $cut ) . "\r\n ";
					$line = substr( $line, $cut );
				}
				return $out . $line;
			},
			$lines
		);

		return implode( "\r\n", $folded ) . "\r\n";
	}
}
