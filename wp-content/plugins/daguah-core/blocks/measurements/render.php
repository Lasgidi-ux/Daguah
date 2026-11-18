<?php
/**
 * Measurements block: the piece's measurements in centimetres and inches.
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
$daguah_rows    = $daguah_product ? Product_Data::get( $daguah_product->get_id() )['measurements'] : array();

if ( ! $daguah_rows ) {
	return;
}

$daguah_labels = Product_Data::measurement_labels();
?>
<div <?php echo get_block_wrapper_attributes(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by core. ?>>
	<table class="daguah-measurements__table">
		<caption class="title-4"><?php esc_html_e( 'Measurements of the piece', 'daguah' ); ?></caption>
		<thead>
			<tr>
				<th scope="col"><span class="screen-reader-text"><?php esc_html_e( 'Measurement', 'daguah' ); ?></span></th>
				<th scope="col">cm</th>
				<th scope="col">in</th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $daguah_rows as $daguah_key => $daguah_cm ) : ?>
				<tr>
					<th scope="row"><?php echo esc_html( $daguah_labels[ $daguah_key ] ); ?></th>
					<td><?php echo esc_html( rtrim( rtrim( number_format( $daguah_cm, 1, '.', '' ), '0' ), '.' ) ); ?></td>
					<td><?php echo esc_html( number_format( $daguah_cm / 2.54, 1, '.', '' ) ); ?></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	<p class="caption-5 is-muted"><?php esc_html_e( 'Measured lying flat and doubled where it goes all the way round.', 'daguah' ); ?></p>
</div>
