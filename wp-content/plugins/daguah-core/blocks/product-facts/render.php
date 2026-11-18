<?php
/**
 * Product facts block: who made it, where, and what it is.
 *
 * @package Daguah\Core
 *
 * @var array     $attributes Block attributes.
 * @var \WP_Block $block      Block instance.
 */

declare( strict_types=1 );

use Daguah\Core\Countries;
use Daguah\Core\Product_Data;

use function Daguah\Core\block_product;

defined( 'ABSPATH' ) || exit;

$daguah_product = block_product( $block ?? null );

if ( ! $daguah_product ) {
	return;
}

$daguah_data  = Product_Data::get( $daguah_product->get_id() );
$daguah_where = implode( ', ', array_filter( array( $daguah_data['origin_town'], $daguah_data['origin_country'] ? Countries::name( $daguah_data['origin_country'] ) : '' ) ) );

$daguah_facts = array_filter(
	array(
		__( 'Made by', 'daguah' )     => $daguah_data['maker'],
		__( 'Made in', 'daguah' )     => $daguah_where,
		__( 'Fabric', 'daguah' )      => implode( ', ', $daguah_data['fabric'] ),
		__( 'Condition', 'daguah' )   => implode( ', ', $daguah_data['condition'] ),
		__( 'Size', 'daguah' )        => implode( ', ', $daguah_data['size'] ),
		__( 'How we know', 'daguah' ) => $daguah_data['authenticity'],
	)
);

if ( ! $daguah_facts ) {
	return;
}
?>
<dl <?php echo get_block_wrapper_attributes(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by core. ?>>
	<?php foreach ( $daguah_facts as $daguah_term => $daguah_value ) : ?>
		<div class="daguah-facts__row">
			<dt class="caption-4"><?php echo esc_html( $daguah_term ); ?></dt>
			<dd class="body-6"><?php echo esc_html( $daguah_value ); ?></dd>
		</div>
	<?php endforeach; ?>
</dl>
