<?php
/**
 * Stage 5 checks, run inside WordPress by the Playground blueprint after
 * seeding.
 *
 * Stall pages and the directory, the shop's stall filter, occasion pages,
 * Market Live (schedule, player links, calendar files), DAGUAH Circle
 * (starting, joining, privacy, the board), Send Home and the gift note, the
 * five Stage 5 pages, the feature flags, and the copy rules.
 * Writes wp-content/daguah-results/stage5.json.
 */

use Daguah\Core\Circles;
use Daguah\Core\Live;
use Daguah\Core\Post_Types;
use Daguah\Core\Seeder;
use Daguah\Core\Settings;
use Daguah\Core\Shop_Query;
use Daguah\Core\Stalls;
use Daguah\Core\Vendor;

$results = array();
$check   = static function ( string $name, bool $pass, $detail = '' ) use ( &$results ): void {
	$results[] = array(
		'check'  => $name,
		'pass'   => $pass,
		'detail' => $detail,
	);
};

$data   = require DAGUAH_CORE_DIR . 'includes/seed/data.php';
$block  = static fn( string $name, array $attrs = array() ): string => render_block(
	array(
		'blockName'    => 'daguah/' . $name,
		'attrs'        => $attrs,
		'innerBlocks'  => array(),
		'innerHTML'    => '',
		'innerContent' => array(),
	)
);
$seeded = static function ( string $type, string $key ): int {
	if ( 'user' === $type ) {
		$found = get_users( array( 'meta_key' => Seeder::MARK, 'meta_value' => $key, 'number' => 1, 'fields' => 'ID' ) );
	} else {
		$found = get_posts( array( 'post_type' => $type, 'post_status' => 'any', 'numberposts' => 1, 'fields' => 'ids', 'meta_key' => Seeder::MARK, 'meta_value' => $key ) );
	}
	return (int) ( $found[0] ?? 0 );
};
$text_of = static fn( string $html ): string => trim( (string) preg_replace( '/\s+/u', ' ', wp_strip_all_tags( (string) preg_replace( '#<(script|style|svg)\b.*?</\1>#s', ' ', $html ) ) ) );

/**
 * Render a whole template with the main query set up as WordPress would.
 */
$render_template = static function ( string $slug, array $query, array $vars = array() ): string {
	global $wp_query, $wp_the_query, $post;
	$wp_query = new WP_Query( $query );
	foreach ( $vars as $var => $value ) {
		$wp_query->set( $var, $value );
	}
	$wp_the_query = $wp_query;
	if ( $wp_query->have_posts() && ( $wp_query->is_singular || $wp_query->is_page ) ) {
		$wp_query->the_post();
	}
	$template = get_block_template( get_stylesheet() . '//' . $slug );
	try {
		$html = $template ? do_blocks( (string) $template->content ) : 'RENDER ERROR: no template ' . $slug;
	} catch ( Throwable $t ) {
		$html = 'RENDER ERROR: ' . $t->getMessage() . ' at ' . $t->getFile() . ':' . $t->getLine();
	}
	wp_reset_postdata();
	return $html;
};

$copy_problems = static function ( string $html ) use ( $text_of ): array {
	$text = $text_of( $html );
	preg_match_all( '/\p{L}-\p{L}/u', $text, $hyphens );
	return array_values( array_unique( array_merge( $hyphens[0], substr_count( $text, '!' ) ? array( '!' ) : array() ) ) );
};

/* Blocks, templates, patterns ------------------------------------------------- */

$blocks  = array( 'stall-header', 'stall-pieces', 'stall-directory', 'occasion-intro', 'circle-board', 'circle-start', 'live-schedule', 'live-stage', 'live-tray', 'send-home', 'gift-note' );
$missing = array_filter( $blocks, static fn( $b ) => ! WP_Block_Type_Registry::get_instance()->is_registered( 'daguah/' . $b ) );
$check( 'All 11 Stage 5 blocks are registered', ! $missing, array_values( $missing ) );

$templates = array( 'stall', 'page-stalls', 'page-occasions', 'page-circle', 'page-send-home', 'page-live', 'single-live_session', 'taxonomy-occasion' );
$absent    = array_filter( $templates, static fn( $t ) => ! get_block_template( get_stylesheet() . '//' . $t ) );
$check( 'Stall, directory, occasions, Circle, Send Home, Market Live and session templates come from the theme', ! $absent, array_values( $absent ) );
$check( 'Occasion layout pattern is registered and used by the occasion template', WP_Block_Patterns_Registry::get_instance()->is_registered( 'daguah/occasion-layout' ) && str_contains( (string) get_block_template( get_stylesheet() . '//taxonomy-occasion' )->content, 'daguah/occasion-layout' ) );

foreach ( array( 'daguah-market' => 'style', 'daguah-local-time' => 'script', 'daguah-circle' => 'script' ) as $handle => $kind ) {
	$check( "Asset {$handle} is registered", 'style' === $kind ? wp_style_is( $handle, 'registered' ) : wp_script_is( $handle, 'registered' ) );
}

/* Stalls ---------------------------------------------------------------------- */

$expected_pieces = array();
foreach ( $data['products'] as $p ) {
	$expected_pieces[ $p['vendor'] ] = ( $expected_pieces[ $p['vendor'] ] ?? 0 ) + 1;
}

$all = Stalls::directory();
$check( 'Directory lists all 8 stalls, most verified first', 8 === count( $all ) && 3 === Vendor::get( $all[0] )['verified_level'] && 1 === Vendor::get( end( $all ) )['verified_level'], array_map( static fn( $id ) => Vendor::get( $id )['verified_level'], $all ) );

$gh = Stalls::directory( array( 'from' => 'GH' ) );
$check( 'Directory filter: 2 stalls ship from Ghana', 2 === count( $gh ), count( $gh ) );
$check( 'Directory counts stalls by country', 2 === ( Stalls::countries()['GH'] ?? 0 ) && 2 === ( Stalls::countries()['NG'] ?? 0 ) && 6 === count( Stalls::countries() ), Stalls::countries() );

$iya     = $seeded( 'user', 'iya-adire' );
$iya_url = get_userdata( $iya )->user_nicename;
$check( 'Ìyá Àdìrẹ Studio has its 3 pieces', $expected_pieces['iya-adire'] === count( Stalls::piece_ids( $iya ) ), count( Stalls::piece_ids( $iya ) ) );
$check( 'Stall categories are counted', array_sum( array_column( Stalls::categories( $iya ), 'count' ) ) >= 3, Stalls::categories( $iya ) );
$check( 'Stall maker and place', 'Ṣadé Adébáyọ̀' === Stalls::maker( $iya ) && 'Abeokuta, Nigeria' === Stalls::place( $iya ), array( Stalls::maker( $iya ), Stalls::place( $iya ) ) );

$count = static function ( array $raw ): int {
	return count( get_posts( array_merge( Shop_Query::args_from( Shop_Query::clean_params( $raw ) ), array( 'post_type' => 'product', 'post_status' => 'publish', 'fields' => 'ids', 'numberposts' => -1 ) ) ) );
};
$check( 'Shop filter stall=: only that stall\'s 3 pieces', 3 === $count( array( 'stall' => $iya_url ) ), $count( array( 'stall' => $iya_url ) ) );
$check( 'Shop filter stall= with verified sellers only still finds 3', 3 === $count( array( 'stall' => $iya_url, 'verified' => '1' ) ) );
$check( 'Shop filter for a stall that does not exist finds nothing', 0 === $count( array( 'stall' => 'no-such-stall' ) ) );
$chips = wp_list_pluck( Shop_Query::active_filters( Shop_Query::clean_params( array( 'stall' => $iya_url ) ), home_url( '/shop/' ) ), 'label' );
$check( 'Stall filter chip reads "From Ìyá Àdìrẹ Studio"', in_array( 'From Ìyá Àdìrẹ Studio', $chips, true ), $chips );
$check( 'Stall shop link carries the stall and category', str_contains( Stalls::shop_url( $iya, 'women' ), 'stall=' . $iya_url ) && str_contains( Stalls::shop_url( $iya, 'women' ), 'cat%5B0%5D=women' ), Stalls::shop_url( $iya, 'women' ) );

// Routing: Dokan's stall address uses the theme's stall template.
$store_var = (string) dokan_get_option( 'custom_store_url', 'dokan_general', 'store' );
set_query_var( $store_var, $iya_url );
$routed = Stalls::template( '/dokan/store.php' );
$check( 'Stall pages use the block template (template canvas), not Dokan\'s PHP page', str_ends_with( $routed, 'template-canvas.php' ), $routed );

$stall_page = $render_template( 'stall', array( 'post_type' => 'product', 'author' => $iya, 'posts_per_page' => 12 ), array( $store_var => $iya_url ) );
$check( 'Stall page renders', ! str_starts_with( $stall_page, 'RENDER ERROR' ) && str_contains( $stall_page, 'Ìyá Àdìrẹ Studio' ), substr( $stall_page, 0, 300 ) );
$check( 'Stall page: one h1, the stall name', 1 === substr_count( $stall_page, '<h1' ) && 1 === preg_match( '#<h1[^>]*>Ìyá Àdìrẹ Studio</h1>#u', $stall_page ) );
$check( 'Stall page: the whole story, maker and place', str_contains( $stall_page, 'dried in the sun' ) && str_contains( $stall_page, 'Ṣadé Adébáyọ̀ · Abeokuta, Nigeria' ) );
$check( 'Stall page: all three checks done for a level 3 stall', 3 === substr_count( $stall_page, 'class="is-done"' ) && str_contains( $stall_page, 'What DAGUAH has checked' ) );
$check( 'Stall page: selling since, reply time, ships from and piece count', str_contains( $stall_page, '2009' ) && str_contains( $stall_page, 'Within 3 hours' ) && str_contains( $stall_page, '3 pieces' ) );
$check( 'Stall page: 3 product cards and category shortcuts into the shop', 3 === substr_count( $stall_page, '<article class="daguah-card' ) && str_contains( $stall_page, 'stall=' . $iya_url ) );
$check( 'Stall page copy: no hyphens inside words, no exclamation marks', ! $copy_problems( $stall_page ), $copy_problems( $stall_page ) );
set_query_var( $store_var, '' );

$makola      = $seeded( 'user', 'makola-lace' );
set_query_var( $store_var, get_userdata( $makola )->user_nicename );
$makola_head = $block( 'stall-header' );
set_query_var( $store_var, '' );
$check( 'A stall with a Market Live session coming up links to it', str_contains( $makola_head, 'daguah-stall-live' ) && str_contains( $makola_head, 'Lace for the wedding season' ) );

$_GET = array( 'from' => 'GH' );
$dir  = $block( 'stall-directory' );
$_GET = array();
$check( 'Directory block, Ghana: "2 stalls", 2 cards, Ghana chip active', str_contains( $dir, '2 stalls' ) && 2 === substr_count( $dir, 'class="daguah-stall-card"' ) && 1 === preg_match( '/is-active"[^>]*>\s*Ghana/', $dir ) );

/* Occasions -------------------------------------------------------------------- */

$grid  = $block( 'occasion-grid', array( 'all' => true, 'dates' => true ) );
$terms = get_terms( array( 'taxonomy' => 'occasion', 'hide_empty' => true, 'fields' => 'ids' ) );
$check( 'Occasions page grid: every occasion with pieces, with next dates', count( $terms ) === substr_count( $grid, 'class="daguah-occasion"' ) && str_contains( $grid, 'daguah-occasion__next' ), array( count( $terms ), substr_count( $grid, 'class="daguah-occasion"' ) ) );

$occasion_page = $render_template( 'taxonomy-occasion', array( 'post_type' => 'product', 'occasion' => 'wedding-guest-aso-ebi' ) );
$check( 'Occasion page renders with its intro', ! str_starts_with( $occasion_page, 'RENDER ERROR' ) && str_contains( $occasion_page, 'wp-block-daguah-occasion-intro' ), substr( $occasion_page, 0, 300 ) );
$check( 'Occasion page: one h1, the occasion', 1 === substr_count( $occasion_page, '<h1' ) && str_contains( $occasion_page, 'Wedding guest and aso ebi</h1>' ) );
$check( 'Occasion page: next date from the calendar and a way into Circle', str_contains( $occasion_page, 'Wedding season' ) && str_contains( $occasion_page, 'Start a circle' ) );
$check( 'Occasion page: filters and results still there', str_contains( $occasion_page, 'wp-block-daguah-shop-filters' ) && str_contains( $occasion_page, 'wp-block-daguah-product-results' ) );

global $wp_query;
$wp_query = new WP_Query( array( 'post_type' => 'product', 'occasion' => 'gifts-for-home' ) );
$gifts    = $block( 'occasion-intro' );
$check( 'Gifting occasion points to Send Home instead', str_contains( $gifts, 'Send something home' ) && ! str_contains( $gifts, 'Start a circle' ) );

/* Market Live ------------------------------------------------------------------- */

$schedule = Live::schedule();
$check( 'Schedule: 3 sessions, next first, then later, then past', 3 === count( $schedule ) && array( 'next', 'later', 'past' ) === array_column( $schedule, 'status' ), array_column( $schedule, 'status' ) );
$check( 'Seeded times are relative: Makola is in about 3 days', $schedule[0]['start'] > time() + 2 * DAY_IN_SECONDS && $schedule[0]['start'] < time() + 4 * DAY_IN_SECONDS && str_contains( $schedule[0]['title'], 'Makola' ) );
$check( 'Next session for a stall', 'Lace for the wedding season, live from Makola' === ( Live::current_or_next( $makola )['title'] ?? '' ) && null === Live::current_or_next( $seeded( 'user', 'bonwire' ) ) );

$on_now = Live::session( $schedule[0]['id'], $schedule[0]['start'] + 60 );
$check( 'A session is live between its start and end', 'now' === $on_now['status'] );
$check( 'Makola runs 75 minutes, start to end', 75 * MINUTE_IN_SECONDS === $schedule[0]['end'] - $schedule[0]['start'], $schedule[0]['end'] - $schedule[0]['start'] );

$embeds = array(
	'https://www.youtube.com/watch?v=abcDEF12345'  => 'https://www.youtube-nocookie.com/embed/abcDEF12345?rel=0',
	'https://youtu.be/abcDEF12345'                 => 'https://www.youtube-nocookie.com/embed/abcDEF12345?rel=0',
	'https://www.youtube.com/live/abcDEF12345?si=x' => 'https://www.youtube-nocookie.com/embed/abcDEF12345?rel=0',
	'https://stream.mux.com/AbCdEf0123456789.m3u8' => 'https://player.mux.com/AbCdEf0123456789',
	'https://vimeo.com/123456789'                  => 'https://player.vimeo.com/video/123456789',
	'https://example.com/stream'                   => '',
	'https://www.youtube.com/watch?v=<script>'     => '',
);
$wrong = array_filter( $embeds, static fn( $want, $url ) => Live::embed_url( $url ) !== $want, ARRAY_FILTER_USE_BOTH );
$check( 'Player links: YouTube (no cookie), Mux and Vimeo embed; anything else does not', ! $wrong, array_map( array( Live::class, 'embed_url' ), array_keys( $wrong ) ) );

$ics   = Live::ics( Live::session( $schedule[0]['id'] ) );
$lines = explode( "\r\n", rtrim( $ics, "\r\n" ) );
$long  = array_filter( $lines, static fn( $l ) => strlen( $l ) > 75 );
$check( 'Calendar file: one event, UTC times, a 15 minute reminder', str_starts_with( $ics, "BEGIN:VCALENDAR\r\n" ) && 1 === substr_count( $ics, 'BEGIN:VEVENT' ) && str_contains( $ics, 'DTSTART:' . gmdate( 'Ymd\THis\Z', $schedule[0]['start'] ) ) && str_contains( $ics, 'TRIGGER:-PT15M' ) );
$unfolded = str_replace( "\r\n ", '', $ics );
$check( 'Calendar file: commas escaped and lines folded at 75 octets', str_contains( $unfolded, 'SUMMARY:Market Live: Lace for the wedding season\, live from Makola' ) && ! $long, array_values( $long ) );
$check( 'Calendar link is the session address with calendar=1', str_ends_with( Live::calendar_url( $schedule[0]['id'] ), '?calendar=1' ) );

$schedule_html = $block( 'live-schedule' );
$check( 'Schedule block: next and coming up, then recent sessions', str_contains( $schedule_html, 'On now and coming up' ) && str_contains( $schedule_html, 'Recent sessions' ) && 3 === substr_count( $schedule_html, 'class="daguah-session__link"' ) && str_contains( $schedule_html, 'data-local-time' ) );

$session_page = $render_template( 'single-live_session', array( 'p' => $schedule[0]['id'], 'post_type' => Post_Types::LIVE ) );
$check( 'Session page renders: one h1, countdown, calendar link, stall card', ! str_starts_with( $session_page, 'RENDER ERROR' ) && 1 === substr_count( $session_page, '<h1' ) && str_contains( $session_page, 'data-countdown' ) && str_contains( $session_page, 'calendar=1' ) && str_contains( $session_page, 'daguah-stall-card' ), substr( $session_page, 0, 300 ) );
$check( 'Session page: the 3 pieces in the tray and the longer description', 3 === substr_count( $session_page, '<article class="daguah-card' ) && str_contains( $session_page, 'under the stall lights' ) );
$check( 'Session page without a stream loads no player', ! str_contains( $session_page, 'data-embed' ) && ! str_contains( $session_page, '<iframe' ) );

$kept_start = (string) get_post_meta( $schedule[0]['id'], 'daguah_starts_at', true );
$kept_end   = (string) get_post_meta( $schedule[0]['id'], 'daguah_ends_at', true );
update_post_meta( $schedule[0]['id'], 'daguah_stream_url', 'https://www.youtube.com/watch?v=abcDEF12345' );
update_post_meta( $schedule[0]['id'], 'daguah_starts_at', gmdate( 'Y-m-d\TH:i', time() - 600 ) );
update_post_meta( $schedule[0]['id'], 'daguah_ends_at', gmdate( 'Y-m-d\TH:i', time() + 3000 ) );
$live_page = $render_template( 'single-live_session', array( 'p' => $schedule[0]['id'], 'post_type' => Post_Types::LIVE ) );
$check( 'While live: a play button that loads the no cookie player on press, nothing loaded before', str_contains( $live_page, 'data-embed="https://www.youtube-nocookie.com/embed/abcDEF12345?rel=0"' ) && str_contains( $live_page, 'Watch live' ) && ! str_contains( $live_page, '<iframe' ) );
update_post_meta( $schedule[0]['id'], 'daguah_stream_url', '' );
update_post_meta( $schedule[0]['id'], 'daguah_starts_at', $kept_start );
update_post_meta( $schedule[0]['id'], 'daguah_ends_at', $kept_end );

$check( 'Session page copy: no hyphens inside words, no exclamation marks', ! $copy_problems( $session_page ), $copy_problems( $session_page ) );

/* Circle --------------------------------------------------------------------------- */

$host_id = wp_insert_user( array( 'user_login' => 'circle-host-check', 'user_email' => 'circle-host-check@daguah.test', 'user_pass' => wp_generate_password( 24 ), 'role' => 'customer', 'first_name' => 'Ada' ) );
$cousin  = wp_insert_user( array( 'user_login' => 'circle-cousin-check', 'user_email' => 'circle-cousin-check@daguah.test', 'user_pass' => wp_generate_password( 24 ), 'role' => 'customer' ) );
$lace    = $seeded( 'product', 'cord-lace-rose' );
$good    = array(
	'title'    => 'Amaka and Femi\'s wedding',
	'note'     => 'Rose gold for the bride\'s side.',
	'fabric'   => $lace,
	'yards'    => 5,
	'delivery' => 'split',
	'closes'   => wp_date( 'Y-m-d', time() + 30 * DAY_IN_SECONDS ),
	'name'     => 'Ada Obi',
	'city'     => 'Manchester',
	'country'  => 'GB',
	'currency' => 'GBP',
);

$check( 'Only fabrics can be chosen for a circle', array_key_exists( $lace, Circles::fabrics() ) && ! array_key_exists( $seeded( 'product', 'adire-wrap-skirt' ), Circles::fabrics() ), count( Circles::fabrics() ) );

$signed_out = Circles::start( 0, $good );
$check( 'Starting a circle needs an account', is_wp_error( $signed_out ) && 'daguah_circle_signin' === $signed_out->get_error_code() );

$bad = Circles::start( (int) $host_id, array_merge( $good, array( 'title' => 'A', 'fabric' => $seeded( 'product', 'adire-wrap-skirt' ), 'closes' => wp_date( 'Y-m-d' ), 'yards' => 40, 'country' => 'ZZ' ) ) );
$fields_of = static function ( $error ): array {
	$fields = is_wp_error( $error ) ? (array) ( $error->get_error_data()['fields'] ?? array() ) : array();
	ksort( $fields );
	return array_keys( $fields );
};
$check( 'Start form errors name each field', array( 'closes', 'country', 'fabric', 'title', 'yards' ) === $fields_of( $bad ), $fields_of( $bad ) );

$cid = Circles::start( (int) $host_id, $good );
$key = is_int( $cid ) ? (string) get_post_meta( $cid, Post_Types::INVITE_KEY, true ) : '';
$check( 'A circle starts private, with the host as its first member and a 32 character key', is_int( $cid ) && 'private' === get_post_status( $cid ) && (int) $host_id === (int) get_post_meta( $cid, 'daguah_host', true ) && 1 === count( Circles::members( $cid ) ) && Circles::members( $cid )[0]['is_host'] && 32 === strlen( $key ), is_wp_error( $cid ) ? $cid->get_error_message() : $cid );
$check( 'The host\'s note is kept, escaped', is_int( $cid ) && str_contains( (string) get_post_field( 'post_content', $cid ), 'Rose gold for the bride' ) );
$check( 'Invite link carries the circle and key', is_int( $cid ) && str_contains( Circles::invite_url( $cid ), 'c=' . $cid ) && str_contains( Circles::invite_url( $cid ), 'key=' . $key ) );

$wrong_key = Circles::join( $cid, str_repeat( 'b', 32 ), array( 'name' => 'Chi', 'city' => 'Lagos', 'country' => 'NG' ), 0 );
$check( 'Joining with a wrong key fails like a missing circle', is_wp_error( $wrong_key ) && 'daguah_no_circle' === $wrong_key->get_error_code() );

$guest = Circles::join( $cid, $key, array( 'name' => 'Chiamaka Obi', 'city' => 'Lagos', 'country' => 'NG' ), 0 );
$after = Circles::members( $cid );
$check( 'A guest with the link joins; currency follows the country', true === $guest && 2 === count( $after ) && 'NGN' === $after[1]['currency'] && 'waiting' === $after[1]['status'] && 0 === $after[1]['user_id'], $after[1] ?? $guest );

$signed = Circles::join( $cid, $key, array( 'name' => 'Tunde', 'city' => 'Houston', 'country' => 'US', 'currency' => 'USD' ), (int) $cousin );
$again  = Circles::join( $cid, $key, array( 'name' => 'Tunde', 'city' => 'Houston', 'country' => 'US' ), (int) $cousin );
$check( 'A signed in member joins once; a second try is refused', true === $signed && is_wp_error( $again ) && 'daguah_circle_member' === $again->get_error_code() );
$check( 'Members can see their circle without the key', Circles::can_see( $cid, '', (int) $cousin ) && Circles::can_see( $cid, '', (int) $host_id ) && ! Circles::can_see( $cid, '', 0 ) );

$invalid_join = Circles::join( $cid, $key, array( 'name' => '', 'city' => '', 'country' => 'XX' ), 0 );
$check( 'Join form errors name each field', array( 'city', 'country', 'name' ) === $fields_of( $invalid_join ), $fields_of( $invalid_join ) );

update_post_meta( $cid, 'daguah_closes', wp_date( 'Y-m-d', time() - DAY_IN_SECONDS ) );
$closed = Circles::join( $cid, $key, array( 'name' => 'Late', 'city' => 'Leeds', 'country' => 'GB' ), 0 );
$check( 'A closed circle refuses new members', is_wp_error( $closed ) && 'daguah_circle_closed' === $closed->get_error_code() );
update_post_meta( $cid, 'daguah_closes', $good['closes'] );

$board = Circles::board( $cid );
$check( 'Board: 3 people in 3 countries, host Ada, first names only', 3 === $board['total'] && 3 === $board['countries'] && 'Ada' === $board['host'] && ! str_contains( (string) wp_json_encode( $board ), 'Obi' ), $board );

$sample     = $seeded( Post_Types::CIRCLE, 'sample-circle' );
$sample_key = (string) get_post_meta( $sample, Post_Types::INVITE_KEY, true );

wp_set_current_user( 0 );
$_GET       = array( 'c' => (string) $sample, 'key' => $sample_key );
$board_html = $block( 'circle-board' );
$_GET       = array( 'c' => (string) $sample );
$no_key     = $block( 'circle-board' );
$_GET       = array();
$none       = $block( 'circle-board' );
$check( 'Board block with the link: title, 3 of 5 paid, 5 members, a join form', str_contains( $board_html, 'Chidinma and Tobe' ) && str_contains( $board_html, 'of 5 paid' ) && 5 === substr_count( $board_html, 'class="daguah-circle-member"' ) && str_contains( $board_html, 'name="action" value="daguah_circle_join"' ) && str_contains( $board_html, '_daguah_nonce' ) );
$check( 'Board title has no "Private:" prefix', ! str_contains( $board_html, 'Private:' ) );
$check( 'Board block shows first names only', ! str_contains( $board_html, 'Okonkwo' ) && ! str_contains( $board_html, 'Nwosu' ) && str_contains( $board_html, 'Adaeze' ) );
$check( 'Board block for a guest has no invite link box (it is for members)', ! str_contains( $board_html, 'id="daguah-circle-link"' ) );
$check( 'Board block without the key: a clear message, nothing private', str_contains( $no_key, 'does not open a circle' ) && ! str_contains( $no_key, 'Chidinma' ) && ! str_contains( $no_key, 'Adaeze' ) );
$check( 'Board block on the plain Circle page: nothing', '' === trim( $none ) );

wp_set_current_user( (int) $host_id );
$_GET      = array( 'c' => (string) $cid, 'key' => $key, 'circle_done' => 'started' );
$host_view = $block( 'circle-board' );
$_GET      = array();
$check( 'Host view: ready message, invite link box, WhatsApp link, pay button waiting for checkout', str_contains( $host_view, 'Your circle is ready' ) && str_contains( $host_view, 'id="daguah-circle-link"' ) && str_contains( $host_view, 'https://wa.me/?text=' ) && str_contains( $host_view, 'Pay your share' ) && ! str_contains( $host_view, 'value="daguah_circle_join"' ) );

$start_in = $block( 'circle-start' );
$check( 'Start form for a signed in buyer: fabrics only, name prefilled', str_contains( $start_in, 'value="daguah_circle_start"' ) && str_contains( $start_in, 'value="' . $lace . '"' ) && ! str_contains( $start_in, 'value="' . $seeded( 'product', 'adire-wrap-skirt' ) . '"' ) && str_contains( $start_in, 'value="Ada"' ) );
wp_set_current_user( 0 );
$start_out = $block( 'circle-start' );
$check( 'Start block signed out: asks to sign in, no form', str_contains( $start_out, 'Sign in to start' ) && ! str_contains( $start_out, 'daguah_circle_start' ) );

$wp_query = new WP_Query( array( 'pagename' => 'circle' ) );
$_GET     = array( 'c' => '1' );
$robots   = apply_filters( 'wp_robots', array( 'max-image-preview' => 'large' ) );
$_GET     = array();
$check( 'Circle boards are noindex, nofollow', ! empty( $robots['noindex'] ) && ! empty( $robots['nofollow'] ), $robots );

list( $status ) = ( static function () use ( $sample, $sample_key ) {
	$request = new WP_REST_Request( 'GET', "/daguah/v1/circles/{$sample}/board" );
	$request->set_query_params( array( 'key' => $sample_key ) );
	$response = rest_do_request( $request );
	return array( $response->get_status(), $response->get_data() );
} )();
$check( 'REST board still answers with the key', 200 === $status, $status );

wp_delete_post( $cid, true );
require_once ABSPATH . 'wp-admin/includes/user.php';
wp_delete_user( (int) $host_id );
wp_delete_user( (int) $cousin );

/* Send Home ------------------------------------------------------------------------- */

$_GET = array( 'to' => 'NG' );
$send = $block( 'send-home' );
$_GET = array( 'to' => 'ZZ' );
$fall = $block( 'send-home' );
$_GET = array();

$cards = substr_count( $send, '<article class="daguah-card' );
$check( 'Send Home to Nigeria: up to 8 pieces, fastest first, link to every piece that ships there', $cards > 0 && $cards <= 8 && str_contains( $send, 'fastest first' ) && str_contains( $send, 'to=NG' ) && str_contains( $send, 'ships=1' ), $cards );

preg_match_all( '#daguah-product-card__link" href="([^"]+)"#', $send, $links );
$days = array_map( static fn( $url ) => (int) get_post_meta( url_to_postid( $url ), '_daguah_days_NG', true ), $links[1] );
$sorted = $days;
sort( $sorted );
$check( 'Send Home cards are in order of delivery days to Nigeria', $days && $days === $sorted, $days );
$check( 'Send Home with an unknown country falls back to Nigeria', str_contains( $fall, 'aria-current="true">' ) && 1 === preg_match( '/is-active"[^>]*>\s*Nigeria/', $fall ) );

$note = $block( 'gift-note' );
$check( 'Gift note: form (shown by its script), 300 character note, recorder, card preview', str_contains( $note, 'class="daguah-note__fields" hidden' ) && str_contains( $note, 'maxlength="300"' ) && str_contains( $note, 'daguah-recorder' ) && str_contains( $note, 'daguah-note__card' ) && str_contains( $note, '<noscript>' ) );

/* Feature flags ---------------------------------------------------------------------- */

$saved_settings = get_option( Settings::OPTION );
Settings::update( array( 'flags' => array( 'circle' => false, 'send_home' => false, 'market_live' => false ) ) );
$off = array(
	'circle-start'  => $block( 'circle-start' ),
	'send-home'     => $block( 'send-home' ),
	'gift-note'     => $block( 'gift-note' ),
	'live-schedule' => $block( 'live-schedule' ),
);
$off_start = Circles::start( 1, $good );
Settings::update( (array) $saved_settings );
$check( 'Flags off: Circle, Send Home and Market Live each say they are resting; the gift note hides', str_contains( $off['circle-start'], 'resting' ) && str_contains( $off['send-home'], 'resting' ) && '' === trim( $off['gift-note'] ) && str_contains( $off['live-schedule'], 'resting' ) );
$check( 'Flags off: starting a circle is refused', is_wp_error( $off_start ) && 'daguah_circle_off' === $off_start->get_error_code() );
$check( 'Flags back on after the check', Settings::flag( 'circle' ) && Settings::flag( 'send_home' ) && Settings::flag( 'market_live' ) );

/* Pages ---------------------------------------------------------------------------- */

$pages = array(
	'stalls'    => array( 'page-stalls', 'wp-block-daguah-stall-directory' ),
	'occasions' => array( 'page-occasions', 'wp-block-daguah-occasion-grid' ),
	'live'      => array( 'page-live', 'wp-block-daguah-live-schedule' ),
	'circle'    => array( 'page-circle', 'wp-block-daguah-circle-start' ),
	'send-home' => array( 'page-send-home', 'wp-block-daguah-send-home' ),
);

foreach ( $pages as $slug => list( $template, $marker ) ) {
	$page    = get_page_by_path( $slug );
	$content = $page ? (string) $page->post_content : '';
	$check( "Page /{$slug}/ has its real opening, not the stage stub", $page && ! str_contains( $content, 'arrives in stage' ) && str_contains( $content, substr( $data['intros'][ $slug ], 0, 30 ) ), substr( $content, 0, 120 ) );

	$html = $render_template( $template, array( 'page_id' => $page ? $page->ID : 0 ) );
	$check( "Page /{$slug}/ renders: one h1, its intro and its block", ! str_starts_with( $html, 'RENDER ERROR' ) && 1 === substr_count( $html, '<h1' ) && str_contains( $html, $marker ) && str_contains( $html, esc_html( substr( $data['intros'][ $slug ], 0, 30 ) ) ), array( substr_count( $html, '<h1' ), substr( $html, 0, 200 ) ) );
	$check( "Page /{$slug}/ copy: no hyphens inside words, no exclamation marks", ! $copy_problems( $html ), $copy_problems( $html ) );
}

/* Write ------------------------------------------------------------------------------ */

$passed = count( array_filter( $results, static fn( $r ) => $r['pass'] ) );
$dir    = WP_CONTENT_DIR . '/daguah-results';
wp_mkdir_p( $dir );
file_put_contents(
	$dir . '/stage5.json',
	wp_json_encode(
		array(
			'passed'  => $passed,
			'total'   => count( $results ),
			'results' => $results,
		),
		JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
	)
);
