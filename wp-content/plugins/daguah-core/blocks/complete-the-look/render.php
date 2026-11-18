<?php
/**
 * Complete the look block.
 *
 * The piece's cross sells first; if there are too few, pieces from other
 * stalls for the same occasions fill the row.
 *
 * @package Daguah\Core
 *
 * @var array     $attributes Block attributes.
 * @var \WP_Block $block      Block instance.
 */

declare( strict_types=1 );

use Daguah\Core\Product_Card;
use Daguah\Core\Taxonomies;

use function Daguah\Core\block_product;

defined( 'ABSPATH' ) || exit;

$daguah_product = block_product( $block ?? null );

if ( ! $daguah_product ) {
	return;
}

$daguah_limit = max( 2, min( 8, (int) ( $attributes['limit'] ?? 4 ) ) );
$daguah_id    = $daguah_product->get_id();
$daguah_ids   = array_slice( array_map( 'intval', $daguah_product->get_cross_sell_ids() ), 0, $daguah_limit );

if ( count( $daguah_ids ) < $daguah_limit ) {
	$daguah_occasions = wp_get_post_terms( $daguah_id, Taxonomies::OCCASION, array( 'fields' => 'ids' ) );

	if ( $daguah_occasions && ! is_wp_error( $daguah_occasions ) ) {
		$daguah_more = get_posts(
			array(
				'post_type'      => 'product',
				'post_status'    => 'publish',
				'fields'         => 'ids',
				'numberposts'    => $daguah_limit - count( $daguah_ids ),
				'post__not_in'   => array_merge( array( $daguah_id ), $daguah_ids ),
				'author__not_in' => array( (int) get_post_field( 'post_author', $daguah_id ) ),
				'orderby'        => 'rand',
				'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Small, cached by the object cache.
					array(
						'taxonomy' => Taxonomies::OCCASION,
						'terms'    => $daguah_occasions,
					),
				),
			)
		);

		$daguah_ids = array_merge( $daguah_ids, array_map( 'intval', $daguah_more ) );
	}
}

if ( ! $daguah_ids ) {
	return;
}
?>
<section <?php echo get_block_wrapper_attributes(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by core. ?>>
	<h2 class="heading-4"><?php esc_html_e( 'Complete the look', 'daguah' ); ?></h2>
	<?php echo Product_Card::grid( $daguah_ids ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped inside Product_Card. ?>
</section>
