<?php
/**
 * Heritage Passport block.
 *
 * Shows only on preloved and vintage pieces that carry at least one story
 * line. Styled like a stamped passport page: each owner's line is a stamp.
 *
 * @package Daguah\Core
 *
 * @var array     $attributes Block attributes.
 * @var \WP_Block $block      Block instance.
 */

declare( strict_types=1 );

use Daguah\Core\Product_Data;

use function Daguah\Core\block_product;

defined( 'ABSPATH' ) || exit;

$daguah_product = block_product( $block ?? null );

if ( ! $daguah_product ) {
	return;
}

$daguah_data      = Product_Data::get( $daguah_product->get_id() );
$daguah_condition = $daguah_data['condition'][0] ?? '';
$daguah_lines     = $daguah_data['passport'];

if ( ! $daguah_lines || ! preg_match( '/^(Preloved|Vintage)/i', $daguah_condition ) ) {
	return;
}

// Stamps sit at slightly different angles, as if pressed by hand.
$daguah_tilts = array( -3, 2, -1, 3, -2 );
$daguah_title = wp_unique_id( 'daguah-passport-' );
?>
<section <?php echo get_block_wrapper_attributes( array( 'aria-labelledby' => $daguah_title ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by core. ?>>
	<header class="daguah-passport__head">
		<p class="daguah-passport__kicker"><?php esc_html_e( 'DAGUAH', 'daguah' ); ?></p>
		<h2 class="heading-5" id="<?php echo esc_attr( $daguah_title ); ?>"><?php esc_html_e( 'Heritage Passport', 'daguah' ); ?></h2>
		<p class="caption-2"><?php echo esc_html( sprintf( /* translators: %d: number of owners */ _n( 'This piece has one story so far. It travels with the piece.', 'This piece has %d stories so far. They travel with the piece.', count( $daguah_lines ), 'daguah' ), count( $daguah_lines ) ) ); ?></p>
	</header>

	<ol class="daguah-passport__stamps">
		<?php foreach ( $daguah_lines as $daguah_i => $daguah_line ) : ?>
			<li class="daguah-stamp" style="<?php echo esc_attr( '--tilt:' . $daguah_tilts[ $daguah_i % count( $daguah_tilts ) ] . 'deg' ); ?>">
				<div class="daguah-stamp__mark" aria-hidden="true">
					<span class="daguah-stamp__year"><?php echo esc_html( $daguah_line['year'] ? (string) $daguah_line['year'] : '' ); ?></span>
				</div>
				<div class="daguah-stamp__text">
					<p class="title-4"><?php echo esc_html( $daguah_line['place'] ); ?><?php echo $daguah_line['year'] ? esc_html( ', ' . $daguah_line['year'] ) : ''; ?></p>
					<p class="body-6"><?php echo esc_html( $daguah_line['story'] ); ?></p>
				</div>
			</li>
		<?php endforeach; ?>
	</ol>

	<p class="daguah-passport__next caption-2"><?php esc_html_e( 'The next line is yours. When you pass it on, your story joins these.', 'daguah' ); ?></p>
</section>
