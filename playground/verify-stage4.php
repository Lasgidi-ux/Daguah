<?php
/**
 * Stage 4 checks, run inside WordPress by the Playground blueprint after the
 * Stage 2 and 3 checks (which leave the site freshly seeded).
 *
 * Shop filters and their counts, the delivery index, fit, saved searches and
 * their email alerts, and full renders of a product page and the shop.
 * Writes wp-content/daguah-results/stage4.json.
 */

use Daguah\Core\Fit;
use Daguah\Core\Geo;
use Daguah\Core\Seeder;
use Daguah\Core\Shop_Query;

$results = array();
$check   = static function ( string $name, bool $pass, $detail = '' ) use ( &$results ): void {
	$results[] = array(
		'check'  => $name,
		'pass'   => $pass,
		'detail' => $detail,
	);
};

$by_key = static function ( string $key ): int {
	$found = get_posts( array( 'post_type' => 'product', 'post_status' => 'any', 'numberposts' => 1, 'fields' => 'ids', 'meta_key' => Seeder::MARK, 'meta_value' => $key ) );
	return (int) ( $found[0] ?? 0 );
};

$admin = (int) ( get_users( array( 'role' => 'administrator', 'number' => 1, 'fields' => 'ID' ) )[0] ?? 0 );

/* Blocks and templates ---------------------------------------------------- */

$blocks  = array( 'currency-switcher', 'fit-mirror', 'fit-chip', 'hold-badge', 'landed-price', 'thread-map', 'heritage-passport', 'measurements', 'product-media', 'product-facts', 'product-stall', 'complete-the-look', 'shop-filters', 'product-results' );
$missing = array_filter( $blocks, static fn( $b ) => ! WP_Block_Type_Registry::get_instance()->is_registered( 'daguah/' . $b ) );
$check( 'All 14 Stage 4 blocks are registered', ! $missing, array_values( $missing ) );

$templates = array( 'archive-product', 'taxonomy-product_cat', 'taxonomy-occasion', 'taxonomy-origin_country', 'product-search-results', 'single-product' );
$absent    = array_filter( $templates, static fn( $t ) => ! get_block_template( get_stylesheet() . '//' . $t ) );
$check( 'Shop, category, occasion, country, search and product templates come from the theme', ! $absent, array_values( $absent ) );
$check( 'Stall card template part exists', (bool) get_block_template( get_stylesheet() . '//stall-card', 'wp_template_part' ) );

/* Delivery index ----------------------------------------------------------- */

$skirt  = $by_key( 'adire-wrap-skirt' );
$agbada = $by_key( 'agbada-cream-damask' );
$check( 'Delivery index: skirt reaches the UK within 9 days', '9' === (string) get_post_meta( $skirt, '_daguah_days_GB', true ), get_post_meta( $skirt, '_daguah_days_GB', true ) );

$all     = get_posts( array( 'post_type' => 'product', 'post_status' => 'publish', 'numberposts' => -1, 'fields' => 'ids', 'meta_key' => Seeder::MARK ) );
$indexed = array_filter( $all, static fn( $id ) => 7 === count( array_filter( array( 'GB', 'US', 'CA', 'EU', 'NG', 'GH', 'KE' ), static fn( $z ) => '' !== (string) get_post_meta( $id, '_daguah_days_' . $z, true ) ) ) );
$check( 'Every seeded piece is indexed for all 7 zones', count( $all ) === count( $indexed ) && 24 === count( $all ), count( $indexed ) . ' of ' . count( $all ) );

/* Filters ------------------------------------------------------------------ */

$count = static function ( array $raw ): array {
	$args = array_merge(
		Shop_Query::args_from( Shop_Query::clean_params( $raw ) ),
		array(
			'post_type'   => 'product',
			'post_status' => 'publish',
			'fields'      => 'ids',
			'numberposts' => -1,
		)
	);
	$ids  = get_posts( $args );
	return array( count( $ids ), $ids );
};

$dokan_fee       = (int) get_option( 'dokan_reverse_withdrawal_product_id', 0 );
list( $n, $ids ) = $count( array() );
$check( 'Dokan\'s Reverse Withdrawal Payment product never appears in the shop', 24 === $n && ! in_array( $dokan_fee, $ids, true ) && '' === \Daguah\Core\Product_Card::render( $dokan_fee ), array( $n, $dokan_fee ) );

list( $n ) = $count( array( 'fabric' => array( 'lace' ) ) );
$check( 'Fabric: lace finds the 3 lace pieces', 3 === $n, $n );

list( $n ) = $count( array( 'fabric' => array( 'lace' ), 'occasion' => array( 'wedding-guest-aso-ebi' ) ) );
$check( 'Lace for wedding guests and aso ebi finds 2', 2 === $n, $n );

list( $n ) = $count( array( 'to' => 'GB', 'ships' => '1' ) );
$check( 'Ships to the UK: all 24', 24 === $n, $n );

list( $n, $ids ) = $count( array( 'to' => 'GB', 'days' => '5' ) );
$check( 'Arrives in the UK within 5 days: the 2 London pieces and 3 from Brussels', 5 === $n, $n );

list( $n ) = $count( array( 'min' => '50', 'max' => '100' ) );
$check( 'Price £50 to £100: 10 pieces', 10 === $n, $n );

list( $n ) = $count( array( 'min' => '102500', 'max' => '205000', 'cur' => 'NGN' ) );
$check( 'The same range in naira finds the same 10', 10 === $n, $n );

list( $n ) = $count( array( 'verified' => '1' ) );
$check( 'Verified sellers only: all 24 (every seeded stall is verified)', 24 === $n, $n );

list( $n, $ids ) = $count( array( 'fit' => 'bust:92,waist:76,hip:104,length:118' ) );
$check( 'Fits me with the wrap dress measurements finds exactly that dress', 1 === $n && $ids[0] === $by_key( 'kitenge-wrap-dress' ), $n );

list( $n ) = $count( array( 'fit' => 'bust:300' ) );
$check( 'An impossible measurement is ignored rather than breaking the filter', 24 === $n, $n );

$clean = Shop_Query::clean_params( array( 'fabric' => array( '<script>' ), 'to' => 'ZZ', 'days' => '999', 'cur' => 'BTC', 'evil' => '1' ) );
$check( 'Unknown filters, countries, currencies and day counts are dropped', ! isset( $clean['to'], $clean['days'], $clean['cur'], $clean['evil'] ), $clean );

$chips = Shop_Query::active_filters( Shop_Query::clean_params( array( 'fabric' => array( 'lace', 'kente' ), 'to' => 'GB', 'ships' => '1' ) ), home_url( '/shop/' ) );
$check( 'Active filters read as words, each removable on its own', array( 'Lace', 'Kente', 'Ships to United Kingdom' ) === array_column( $chips, 'label' ) && str_contains( $chips[0]['url'], 'kente' ) && ! str_contains( $chips[0]['url'], 'lace' ), array_column( $chips, 'label' ) );

/* Fit --------------------------------------------------------------------- */

$check( 'Fit: 2 cm fits, 5 cm alters, more does not', 'fits' === Fit::compare( array( 'waist' => 76 ), array( 'waist' => 78 ) )['status'] && 'alter' === Fit::compare( array( 'waist' => 76 ), array( 'waist' => 81 ) )['status'] && 'no' === Fit::compare( array( 'waist' => 76 ), array( 'waist' => 81.5 ) )['status'] );

/* Saved searches ------------------------------------------------------------ */

$rest = static function ( string $method, string $route, array $params = array(), int $user = 0 ) {
	wp_set_current_user( $user );
	$request = new WP_REST_Request( $method, $route );
	foreach ( $params as $k => $v ) {
		$request->set_param( $k, $v );
	}
	$response = rest_do_request( $request );
	return array( $response->get_status(), json_decode( (string) wp_json_encode( rest_get_server()->response_to_data( $response, false ) ), true ) );
};

list( $status ) = $rest( 'POST', '/daguah/v1/saved-searches', array( 'query' => 'fabric[]=lace' ) );
$check( 'Saving a search signed out: 401', 401 === $status, $status );

list( $status, $saved ) = $rest( 'POST', '/daguah/v1/saved-searches', array( 'query' => 'fabric%5B%5D=lace&to=GB&ships=1&s=royal' ), $admin );
$check( 'Saving a search signed in: 201, described in words', 201 === $status && '"royal" · Lace · Ships to United Kingdom' === $saved['label'], $saved['label'] ?? $status );

// Pretend the last alert was long ago, then run the daily alert.
$list               = Shop_Query::saved( $admin );
$list[0]['alerted'] = '2000-01-01T00:00:00+00:00';
update_user_meta( $admin, Shop_Query::SAVED, $list );

$mail    = array();
$capture = static function ( $null, $atts ) use ( &$mail ) {
	$mail[] = $atts;
	return true;
};
add_filter( 'pre_wp_mail', $capture, 10, 2 );
Shop_Query::send_alerts();
remove_filter( 'pre_wp_mail', $capture, 10 );

$check( 'Daily alert emails the new matches: 1 piece for "royal" lace that ships to the UK', 1 === count( $mail ) && str_contains( (string) $mail[0]['message'], '1 new piece for' ), $mail[0]['message'] ?? 'no email' );

$mail = array();
add_filter( 'pre_wp_mail', $capture, 10, 2 );
Shop_Query::send_alerts();
remove_filter( 'pre_wp_mail', $capture, 10 );
$check( 'The next day, nothing new means no email', 0 === count( $mail ) );

list( $status ) = $rest( 'DELETE', '/daguah/v1/saved-searches/' . $saved['id'], array(), $admin );
$check( 'Removing the saved search: 200, and the list is empty again', 200 === $status && ! Shop_Query::saved( $admin ), $status );
wp_set_current_user( 0 );

/* Render a product page ----------------------------------------------------- */

$render_product = static function ( int $id ): string {
	global $post, $wp_query;
	$post     = get_post( $id );
	$wp_query = new WP_Query( array( 'p' => $id, 'post_type' => 'product' ) );
	$wp_query->the_post();
	$template = get_block_template( get_stylesheet() . '//single-product' );
	try {
		$html = do_blocks( (string) $template->content );
	} catch ( Throwable $t ) {
		$html = 'RENDER ERROR: ' . $t->getMessage();
	}
	wp_reset_postdata();
	return $html;
};

// Not $page: WordPress keeps the page number in a global of that name and
// resets it whenever a post is set up, and these checks run in global scope.
$product_page = $render_product( $skirt );
$check( 'Product page renders', ! str_starts_with( $product_page, 'RENDER ERROR' ) && str_contains( $product_page, 'Indigo adire wrap skirt' ), substr( $product_page, 0, 200 ) );
$check( 'Product page: cloth and the photo to come, as there is no photo yet', str_contains( $product_page, 'daguah-media__placeholder' ) && str_contains( $product_page, 'Photo to come: Hands lifting the skirt' ) );
$check( 'Product page: the Thread reads "Dyed in Abeokuta, arriving in London in 6 to 9 days."', str_contains( $product_page, 'Dyed in Abeokuta, arriving in London in 6 to 9 days.' ) );
$check( 'Product page: landed price to the UK totals £95.16', str_contains( $product_page, '95.16' ) );
$check( 'Product page: measurements table with 3 rows and a fit chip', 3 === substr_count( $product_page, '<th scope="row">' ) && str_contains( $product_page, 'daguah-fit-chip' ) );
$check( 'Product page: facts name the maker', str_contains( $product_page, 'Ṣadé Adébáyọ̀' ) );
$check( 'Product page: DAGUAH Hold line and the stall card', str_contains( $product_page, 'Your payment is held until you confirm delivery.' ) && str_contains( $product_page, 'daguah-stall-card' ) );
$check( 'Product page: no Heritage Passport on a new piece', ! str_contains( $product_page, 'wp-block-daguah-heritage-passport' ) );
$check( 'Product page: add to cart form is there', str_contains( $product_page, 'add-to-cart' ) || str_contains( $product_page, 'single_add_to_cart_button' ) );
$check( 'Product page: complete the look shows other stalls for the same occasions', str_contains( $product_page, 'Complete the look' ) && substr_count( $product_page, 'daguah-product-card"' ) + substr_count( $product_page, 'daguah-product-card ' ) >= 1 );

$agbada_page = $render_product( $agbada );
$check( 'Preloved agbada page shows its Heritage Passport with 2 stamps', 2 === substr_count( $agbada_page, 'class="daguah-stamp"' ) && str_contains( $agbada_page, 'Worn to my' ) === false && str_contains( $agbada_page, 'Made for my brother' ) );

$text = wp_strip_all_tags( (string) preg_replace( '#<(script|style|svg)\b.*?</\1>#s', ' ', $product_page ) );
preg_match_all( '/\p{L}-\p{L}/u', $text, $hyphens );
$check( 'Product page copy has no hyphens inside words', ! $hyphens[0], array_values( array_unique( $hyphens[0] ) ) );

/* Render the shop -------------------------------------------------------------- */

global $wp_query;
$_GET     = array( 'fabric' => array( 'lace' ) );
$wp_query = new WP_Query( array_merge( Shop_Query::args_from( Shop_Query::clean_params( $_GET ) ), array( 'post_type' => 'product', 'posts_per_page' => 12 ) ) );
$results_html = render_block( array( 'blockName' => 'daguah/product-results', 'attrs' => array(), 'innerBlocks' => array(), 'innerHTML' => '', 'innerContent' => array() ) );
$filters_html = render_block( array( 'blockName' => 'daguah/shop-filters', 'attrs' => array(), 'innerBlocks' => array(), 'innerHTML' => '', 'innerContent' => array() ) );
$_GET     = array();

$check( 'Shop results: "3 pieces", 3 cards and a removable Lace chip', str_contains( $results_html, '3 pieces' ) && 3 === substr_count( $results_html, '<article class="daguah-card' ) && str_contains( $results_html, 'Remove filter: Lace' ) );
$check( 'Shop filters: lace is ticked, every facet is there, it submits by GET', 1 === preg_match( '/value="lace"\s+checked/', $filters_html ) && str_contains( $filters_html, 'method="get"' ) && str_contains( $filters_html, 'name="fit_toggle"' ) && str_contains( $filters_html, 'name="days"' ) );

$wp_query     = new WP_Query( array( 'post_type' => 'product', 'post__in' => array( 0 ) ) );
$empty_html   = render_block( array( 'blockName' => 'daguah/product-results', 'attrs' => array(), 'innerBlocks' => array(), 'innerHTML' => '', 'innerContent' => array() ) );
$check( 'Shop results with nothing to show: the composed empty state', str_contains( $empty_html, 'daguah-empty' ) );

/* Header ---------------------------------------------------------------------- */

// The panel is printed at the end of the page, outside the header: the
// header's blurred surface would otherwise trap the fixed panel inside it.
ob_start();
do_action( 'wp_footer' );
$footer = (string) ob_get_clean();
$check(
	'Header has the currency switcher and Fit Mirror button; the panel prints once, after the header',
	str_contains( $product_page, 'data-daguah-currency' ) && str_contains( $product_page, 'data-open-fit-mirror' ) && ! str_contains( $product_page, 'id="daguah-fit-mirror"' ) && 1 === substr_count( $footer, 'id="daguah-fit-mirror"' ),
	array(
		'currency switcher' => substr_count( $product_page, 'data-daguah-currency' ),
		'fit button'        => substr_count( $product_page, 'data-open-fit-mirror' ),
		'header'            => substr_count( $product_page, '<header' ),
		'panel in page'     => substr_count( $product_page, 'id="daguah-fit-mirror"' ),
		'panel in footer'   => substr_count( $footer, 'id="daguah-fit-mirror"' ),
		'page starts'       => substr( $product_page, 0, 300 ),
	)
);

/* Map ---------------------------------------------------------------------- */

$dots = Geo::dots();
$out  = array_filter( $dots, static fn( $d ) => $d[0] < 0 || $d[0] > Geo::WIDTH || $d[1] < 0 || $d[1] > Geo::HEIGHT );
$check( 'Map: a few hundred land dots, all inside the frame', count( $dots ) > 300 && ! $out, count( $dots ) );

/* Write --------------------------------------------------------------------- */

$passed = count( array_filter( $results, static fn( $r ) => $r['pass'] ) );
$dir    = WP_CONTENT_DIR . '/daguah-results';
wp_mkdir_p( $dir );
file_put_contents(
	$dir . '/stage4.json',
	wp_json_encode(
		array(
			'passed'  => $passed,
			'total'   => count( $results ),
			'results' => $results,
		),
		JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
	)
);
