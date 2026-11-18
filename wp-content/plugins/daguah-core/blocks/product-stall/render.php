<?php
/**
 * Product stall block: the stall that sells this piece.
 *
 * @package Daguah\Core
 *
 * @var array     $attributes Block attributes.
 * @var \WP_Block $block      Block instance.
 */

declare( strict_types=1 );

use Daguah\Core\Vendor;

use function Daguah\Core\block_product;

defined( 'ABSPATH' ) || exit;

$daguah_product = block_product( $block ?? null );
$daguah_card    = $daguah_product ? Vendor::card( (int) get_post_field( 'post_author', $daguah_product->get_id() ) ) : '';

if ( '' === $daguah_card ) {
	return;
}
?>
<section <?php echo get_block_wrapper_attributes(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by core. ?>>
	<h2 class="title-2"><?php esc_html_e( 'The stall', 'daguah' ); ?></h2>
	<?php echo $daguah_card; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped inside Vendor::card. ?>
</section>
