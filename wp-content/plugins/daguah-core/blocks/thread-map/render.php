<?php
/**
 * The Thread: a gold thread from where the piece was made to the buyer's city.
 *
 * The map is a dot grid of North America, Europe and Africa. The thread is
 * revealed by a mask that slides across it (a transform), not by animating
 * the stroke. Rendered for London by default; the shop script redraws it for
 * the visitor's country and city.
 *
 * @package Daguah\Core
 *
 * @var array     $attributes Block attributes.
 * @var \WP_Block $block      Block instance.
 */

declare( strict_types=1 );

use Daguah\Core\Countries;
use Daguah\Core\Geo;
use Daguah\Core\Product_Data;

use function Daguah\Core\block_product;

defined( 'ABSPATH' ) || exit;

$daguah_product = block_product( $block ?? null );

if ( ! $daguah_product ) {
	return;
}

$daguah_data    = Product_Data::get( $daguah_product->get_id() );
$daguah_towns   = Geo::towns();
$daguah_town    = $daguah_data['origin_town'];
$daguah_lookup  = array_change_key_case( $daguah_towns, CASE_LOWER );
$daguah_known   = $daguah_lookup[ strtolower( $daguah_town ) ] ?? null;

if ( ! $daguah_known ) {
	$daguah_capital = Geo::capital( $daguah_data['origin_country'] );
	$daguah_known   = $daguah_towns[ $daguah_capital ];
	$daguah_town    = '' !== $daguah_town ? $daguah_town : $daguah_capital;
}

// Dyed, Woven, Printed or Made, from the fabric.
$daguah_verbs = array(
	'Adire'         => __( 'Dyed', 'daguah' ),
	'Bògòlan'       => __( 'Dyed', 'daguah' ),
	'Kente'         => __( 'Woven', 'daguah' ),
	'Aso oke'       => __( 'Woven', 'daguah' ),
	'Shemma cotton' => __( 'Woven', 'daguah' ),
	'Wax print'     => __( 'Printed', 'daguah' ),
	'Leather'       => __( 'Made', 'daguah' ),
);
$daguah_verb = $daguah_verbs[ $daguah_data['fabric'][0] ?? '' ] ?? __( 'Made', 'daguah' );

$daguah_origin = Geo::project( $daguah_known[0], $daguah_known[1] ) + array(
	'town' => $daguah_town,
	'verb' => $daguah_verb,
);

$daguah_days = array();
foreach ( array_keys( Countries::zones() ) as $daguah_zone ) {
	$daguah_corridor = Product_Data::corridor_to( $daguah_product->get_id(), $daguah_zone );
	if ( $daguah_corridor ) {
		$daguah_days[ $daguah_zone ] = array( $daguah_corridor['min_days'], $daguah_corridor['max_days'] );
	}
}

$daguah_destinations = array();
foreach ( Geo::buyer_cities() as $daguah_code => $daguah_cities ) {
	$daguah_destinations[ $daguah_code ] = array(
		'name'   => Geo::buyer_countries()[ $daguah_code ]['name'],
		'zone'   => (string) Countries::zone_for( $daguah_code ),
		'cities' => array_map(
			static fn( $c ) => array( 'name' => $c[0] ) + Geo::project( $c[1], $c[2] ),
			$daguah_cities
		),
	);
}

$daguah_to      = $daguah_destinations['GB']['cities'][0];
$daguah_gb_days = $daguah_days['GB'] ?? null;
$daguah_mid_x   = (int) round( ( $daguah_origin['x'] + $daguah_to['x'] ) / 2 );
$daguah_dist    = hypot( $daguah_to['x'] - $daguah_origin['x'], $daguah_to['y'] - $daguah_origin['y'] );
$daguah_mid_y   = (int) round( ( $daguah_origin['y'] + $daguah_to['y'] ) / 2 - $daguah_dist * 0.28 );
$daguah_path    = sprintf( 'M%d %d Q%d %d %d %d', $daguah_origin['x'], $daguah_origin['y'], $daguah_mid_x, $daguah_mid_y, $daguah_to['x'], $daguah_to['y'] );
$daguah_mask    = wp_unique_id( 'daguah-thread-mask-' );

$daguah_caption = $daguah_gb_days
	/* translators: 1: Dyed, Woven or Made, 2: town, 3: buyer city, 4: fewest days, 5: most days */
	? sprintf( __( '%1$s in %2$s, arriving in %3$s in %4$d to %5$d days.', 'daguah' ), $daguah_verb, $daguah_town, $daguah_to['name'], $daguah_gb_days[0], $daguah_gb_days[1] )
	/* translators: 1: Dyed, Woven or Made, 2: town, 3: buyer country */
	: sprintf( __( '%1$s in %2$s. This piece does not ship to %3$s yet.', 'daguah' ), $daguah_verb, $daguah_town, __( 'the United Kingdom', 'daguah' ) );

$daguah_thread_data = array(
	'origin'       => $daguah_origin,
	'days'         => $daguah_days,
	'destinations' => $daguah_destinations,
);
?>
<section <?php echo get_block_wrapper_attributes( array( 'data-thread' => (string) wp_json_encode( $daguah_thread_data ), 'class' => $daguah_to['x'] < $daguah_origin['x'] ? 'is-westward' : '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by core. ?>>
	<div class="daguah-thread__head">
		<h2 class="heading-5"><?php esc_html_e( 'The Thread', 'daguah' ); ?></h2>
		<p class="daguah-thread__caption body-3"><?php echo esc_html( $daguah_caption ); ?></p>
	</div>

	<div class="daguah-thread__map">
		<svg viewBox="0 0 <?php echo (int) Geo::WIDTH; ?> <?php echo (int) Geo::HEIGHT; ?>" role="img" aria-label="<?php echo esc_attr( $daguah_caption ); ?>" focusable="false">
			<defs>
				<mask id="<?php echo esc_attr( $daguah_mask ); ?>">
					<rect class="daguah-thread__reveal" x="0" y="0" width="<?php echo (int) Geo::WIDTH; ?>" height="<?php echo (int) Geo::HEIGHT; ?>" fill="#FFFFFF"/>
				</mask>
			</defs>
			<g class="daguah-thread__dots">
				<?php foreach ( Geo::dots() as $daguah_dot ) : ?>
					<circle cx="<?php echo (int) $daguah_dot[0]; ?>" cy="<?php echo (int) $daguah_dot[1]; ?>" r="2.2"/>
				<?php endforeach; ?>
			</g>
			<path class="daguah-thread__path" d="<?php echo esc_attr( $daguah_path ); ?>" mask="url(#<?php echo esc_attr( $daguah_mask ); ?>)"/>
			<circle class="daguah-thread__from" cx="<?php echo (int) $daguah_origin['x']; ?>" cy="<?php echo (int) $daguah_origin['y']; ?>" r="5"/>
			<circle class="daguah-thread__to" cx="<?php echo (int) $daguah_to['x']; ?>" cy="<?php echo (int) $daguah_to['y']; ?>" r="5"/>
			<text class="daguah-thread__from-label" x="<?php echo (int) $daguah_origin['x']; ?>" y="<?php echo (int) $daguah_origin['y'] + 20; ?>" text-anchor="middle"><?php echo esc_html( $daguah_town ); ?></text>
			<text class="daguah-thread__to-label" x="<?php echo (int) $daguah_to['x']; ?>" y="<?php echo (int) $daguah_to['y'] - 10; ?>" text-anchor="middle"><?php echo esc_html( $daguah_to['name'] ); ?></text>
		</svg>
	</div>

	<div class="daguah-thread__controls">
		<label>
			<span class="caption-4"><?php esc_html_e( 'Country', 'daguah' ); ?></span>
			<select data-daguah-country>
				<?php foreach ( $daguah_destinations as $daguah_code => $daguah_dest ) : ?>
					<option value="<?php echo esc_attr( $daguah_code ); ?>" <?php selected( $daguah_code, 'GB' ); ?>><?php echo esc_html( $daguah_dest['name'] ); ?></option>
				<?php endforeach; ?>
			</select>
		</label>
		<label>
			<span class="caption-4"><?php esc_html_e( 'City', 'daguah' ); ?></span>
			<select data-daguah-city>
				<?php foreach ( $daguah_destinations['GB']['cities'] as $daguah_city ) : ?>
					<option value="<?php echo esc_attr( $daguah_city['name'] ); ?>"><?php echo esc_html( $daguah_city['name'] ); ?></option>
				<?php endforeach; ?>
			</select>
		</label>
	</div>
</section>
