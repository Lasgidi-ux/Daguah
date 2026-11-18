<?php
/**
 * Fit chip block, for the product page.
 *
 * @package Daguah\Core
 *
 * @var array     $attributes Block attributes.
 * @var \WP_Block $block      Block instance.
 */

declare( strict_types=1 );

use Daguah\Core\Product_Card;
use Daguah\Core\Product_Data;

use function Daguah\Core\block_product;

defined( 'ABSPATH' ) || exit;

$daguah_product = block_product( $block ?? null );
$daguah_chip    = $daguah_product ? Product_Card::fit_chip( Product_Data::get( $daguah_product->get_id() )['measurements'] ) : '';

if ( '' === $daguah_chip ) {
	return;
}

printf(
	'<div %1$s>%2$s</div>',
	get_block_wrapper_attributes(), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by core.
	$daguah_chip // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped inside Product_Card.
);
