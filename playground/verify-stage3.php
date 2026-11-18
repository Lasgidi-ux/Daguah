<?php
/**
 * Stage 3 checks, run inside WordPress by the Playground blueprint after the
 * Stage 2 checks (which leave the site freshly seeded).
 *
 * Renders the Home page the way the front end does and checks every
 * section, the calendar order, the collection endpoint, the FAQ structured
 * data, and the copy rules. Writes wp-content/daguah-results/stage3.json.
 */

use Daguah\Core\Calendar;
use Daguah\Core\Faq_Schema;
use Daguah\Core\Product_Card;
use Daguah\Core\Seeder;

$results = array();
$check   = static function ( string $name, bool $pass, $detail = '' ) use ( &$results ): void {
	$results[] = array(
		'check'  => $name,
		'pass'   => $pass,
		'detail' => $detail,
	);
};

$count = static fn( string $needle, string $haystack ): int => substr_count( $haystack, $needle );

/* Patterns and the Home page --------------------------------------------- */

$registry = WP_Block_Patterns_Registry::get_instance();
$sections = array( 'home', 'home-opening', 'home-search', 'home-calendar', 'home-benefits', 'home-occasions', 'home-stalls', 'home-live', 'home-lantern-words', 'home-hold', 'home-testimonials', 'home-faq', 'home-seller-cta', 'home-final-cta' );
$missing  = array_filter( $sections, static fn( $slug ) => ! $registry->is_registered( 'daguah/' . $slug ) );
$check( 'All 14 home patterns are registered', ! $missing, implode( ', ', $missing ) );

$blocks  = array( 'icon', 'market-word', 'lantern-row', 'calendar-ribbon', 'occasion-grid', 'featured-stalls', 'live-strip', 'lantern-words' );
$unknown = array_filter( $blocks, static fn( $name ) => ! WP_Block_Type_Registry::get_instance()->is_registered( 'daguah/' . $name ) );
$check( 'All 8 Stage 3 blocks are registered', ! $unknown, implode( ', ', $unknown ) );

$home = get_page_by_path( 'home' );
$check( 'Seeding created a Home page and made it the front page', $home && (int) get_option( 'page_on_front' ) === (int) $home->ID );

$template = get_block_template( get_stylesheet() . '//front-page' );
$check( 'The theme has a front page template', (bool) $template );

$header = do_blocks( '<!-- wp:template-part {"slug":"header","theme":"' . get_stylesheet() . '"} /-->' );
$check(
	'Header uses the seeded six link menu, not the list of every page',
	12 === substr_count( $header, 'wp-block-navigation-link' ) && ! str_contains( $header, 'wp-block-pages-list' ),
	substr_count( $header, 'wp-block-navigation-link' )
);

/* Render the Home page --------------------------------------------------- */

$GLOBALS['post'] = $home;
setup_postdata( $home );
$html = do_blocks( (string) $home->post_content );
wp_reset_postdata();

ob_start();
Faq_Schema::print();
$schema_html = (string) ob_get_clean();

$check( 'The Opening renders: cover, canopy of 11 lanterns, market word, hero heading', str_contains( $html, 'daguah-opening' ) && 11 === $count( 'class="daguah-lantern"', $html ) && str_contains( $html, 'Ọjà' ) && str_contains( $html, 'is-style-hero-heading' ) );
$check( 'Exactly one h1 on the Home page', 1 === preg_match_all( '/<h1[\s>]/', $html ) );
$check( 'Product search is limited to pieces', str_contains( $html, 'name="post_type" value="product"' ) );
$check( 'Calendar ribbon shows 8 moments with a collection of cards', 8 === $count( 'class="daguah-calendar__moment', $html ) && $count( 'daguah-product-card', $html ) >= 2 );
$check( 'Shop by occasion shows 8 tiles', 8 === $count( 'class="daguah-occasion"', $html ) );
$check( 'Featured stalls shows 4 stalls, each with its cloth', 4 === $count( 'class="daguah-stall-card"', $html ) && 4 === $count( 'daguah-stall-card__cloth', $html ) );
$check( 'Market Live strip shows the Makola session', str_contains( $html, 'wp-block-daguah-live-strip' ) && str_contains( $html, 'Makola' ) );
$check( 'Lantern words splits the tagline into 9 words', 9 === $count( 'class="daguah-lantern-words__word"', $html ) );
// WordPress appends layout classes, so match the class names, not the whole attribute.
$check( 'DAGUAH Hold shows 3 numbered steps in a list', 3 === preg_match_all( '/<li class="[^"]*\bdaguah-step\b/', $html ) && 1 === preg_match( '/<ol class="[^"]*\bdaguah-steps\b/', $html ) );
$check( 'Three testimonials as quotes', 3 === $count( 'wp-block-quote daguah-quote', $html ) );
$check( 'Eight FAQ questions', 8 === $count( '<details class="wp-block-details', $html ) );
$check( 'Final call to action repeats the hero button', 2 === $count( '>Start shopping</a>', $html ) );

$json   = preg_match( '#<script[^>]*application/ld\+json[^>]*>(.*?)</script>#s', $schema_html, $m ) ? json_decode( $m[1], true ) : null;
$check( 'FAQ structured data has the same 8 questions as the page', is_array( $json ) && 'FAQPage' === $json['@type'] && 8 === count( $json['mainEntity'] ) && 'How does DAGUAH Hold protect my money?' === $json['mainEntity'][0]['name'], is_array( $json ) ? count( $json['mainEntity'] ) : $schema_html );

/* Copy rules ------------------------------------------------------------------ */

$text = wp_strip_all_tags( (string) preg_replace( '#<(script|style|svg)\b.*?</\1>#s', ' ', $html ) );
preg_match_all( '/\p{L}-\p{L}/u', $text, $hyphens );
$check( 'No hyphens inside words anywhere in the Home page copy', ! $hyphens[0], array_slice( $hyphens[0], 0, 10 ) );

$banned = array_filter( array( 'revolutionize', 'supercharge', 'cutting edge', 'effortless', 'transform your workflow', 'take it to the next level', 'harness the power', 'world class' ), static fn( $w ) => false !== stripos( $text, $w ) );
$check( 'No banned words in the Home page copy', ! $banned, array_values( $banned ) );
$check( 'No exclamation marks in the Home page copy', ! str_contains( $text, '!' ) );

/* Calendar -------------------------------------------------------------------- */

$moments = Calendar::upcoming( 10 );
$starts  = array_column( array_filter( $moments, static fn( $m ) => ! $m['now'] ), 'starts' );
$sorted  = $starts;
sort( $sorted );
$check( 'Moments run soonest first, after any happening now', $starts === $sorted, array_column( $moments, 'title' ) );
$check( 'Every moment is today or later once rolled forward', ! array_filter( $moments, static fn( $m ) => $m['ends'] < wp_date( 'Y-m-d' ) ) );

$wedding = current( array_filter( $moments, static fn( $m ) => 'Wedding season' === $m['title'] ) );
$ids     = $wedding ? Calendar::collection_ids( $wedding ) : array();
$fabrics = array_filter( $ids, static fn( $id ) => has_term( 'fabrics', 'product_cat', $id ) );
$check( 'Wedding season collection is aso ebi pieces from Fabrics only', $ids && count( $ids ) === count( $fabrics ), count( $ids ) );

$rest = static function ( string $route ) {
	wp_set_current_user( 0 );
	$response = rest_do_request( new WP_REST_Request( 'GET', $route ) );
	return array( $response->get_status(), $response->get_data() );
};

list( $status, $body ) = $rest( '/daguah/v1/moments/' . $moments[0]['id'] . '/collection' );
$check( 'Collection endpoint returns cards for the first moment, signed out', 200 === $status && str_contains( (string) $body['html'], 'daguah-product-card' ) && '' !== $body['dates'], $status );

list( $status ) = $rest( '/daguah/v1/moments/999999/collection' );
$check( 'Collection endpoint: unknown moment is a 404', 404 === $status, $status );

/* Product card ------------------------------------------------------------ */

$skirt = get_posts( array( 'post_type' => 'product', 'meta_key' => Seeder::MARK, 'meta_value' => 'adire-wrap-skirt', 'fields' => 'ids', 'numberposts' => 1 ) )[0] ?? 0;
$card  = Product_Card::render( (int) $skirt );
$check( 'Card shows stall, town, price and the photo to come', str_contains( $card, 'Ìyá Àdìrẹ Studio, Abeokuta' ) && str_contains( $card, '64.50' ) && str_contains( $card, 'Photo to come' ) && str_contains( $card, '<svg' ) );
$check( 'An empty collection shows the composed empty state', str_contains( Product_Card::grid( array() ), 'daguah-empty' ) );

/* Write --------------------------------------------------------------------- */

$passed = count( array_filter( $results, static fn( $r ) => $r['pass'] ) );
$dir    = WP_CONTENT_DIR . '/daguah-results';
wp_mkdir_p( $dir );
file_put_contents(
	$dir . '/stage3.json',
	wp_json_encode(
		array(
			'passed'  => $passed,
			'total'   => count( $results ),
			'results' => $results,
		),
		JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
	)
);
